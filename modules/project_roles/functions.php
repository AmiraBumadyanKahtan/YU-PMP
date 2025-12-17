<?php
// modules/project_roles/functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب جميع أدوار المشاريع
 */
function getProjectRoles() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM project_roles ORDER BY id ASC")->fetchAll();
}

/**
 * جلب دور معين
 */
function getProjectRoleById($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM project_roles WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * ✅ (تم التصحيح) جلب صلاحيات المشاريع وتجميعها يدوياً
 * لتجنب مشاكل PDO::FETCH_GROUP واختفاء الـ ID
 */
function getProjectRelatedPermissions() {
    $db = Database::getInstance()->pdo();
    
    // 1. جلب الصلاحيات كمصفوفة ترابطية عادية
    $sql = "SELECT * FROM permissions 
            WHERE module IN ('projects', 'initiatives', 'documents', 'risks') 
            ORDER BY module, permission_key";
            
    $allPerms = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. التجميع اليدوي
    $grouped = [];
    foreach ($allPerms as $p) {
        // إذا لم يكن هناك موديول، نضعها تحت General
        $mod = $p['module'] ? ucfirst($p['module']) : 'General';
        $grouped[$mod][] = $p;
    }
    
    return $grouped;
}

/**
 * جلب الصلاحيات المفعلة لدور معين
 */
function getProjectRolePermissions($role_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT permission_id FROM project_role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * تحديث صلاحيات الدور
 */
function updateProjectRolePermissions($role_id, $permissions) {
    $db = Database::getInstance()->pdo();
    try {
        $db->beginTransaction();
        
        // حذف القديم
        $db->prepare("DELETE FROM project_role_permissions WHERE role_id = ?")->execute([$role_id]);
        
        // إضافة الجديد
        if (!empty($permissions)) {
            $stmt = $db->prepare("INSERT INTO project_role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permissions as $perm_id) {
                $stmt->execute([$role_id, $perm_id]);
            }
        }
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * إنشاء دور مشروع جديد
 */
function createProjectRole($name, $desc) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("INSERT INTO project_roles (name, description, created_at) VALUES (?, ?, NOW())");
    if ($stmt->execute([$name, $desc])) {
        return $db->lastInsertId();
    }
    return false;
}

////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * جلب أعضاء فريق المشروع
 */
/*function getProjectTeam($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT pt.*, 
               u.full_name_en, 
               u.email, 
               u.avatar, 
               u.job_title,
               r.name AS role_name,
               r.description AS role_desc
        FROM project_team pt
        JOIN users u ON u.id = pt.user_id
        JOIN project_roles r ON r.id = pt.role_id
        WHERE pt.project_id = ? AND pt.is_active = 1
        ORDER BY r.id ASC, u.full_name_en ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}*/

/**
 * إضافة عضو للفريق
 */
/*function addTeamMember($project_id, $user_id, $role_id) {
    $db = Database::getInstance()->pdo();
    
    // التحقق من التكرار
    $check = $db->prepare("SELECT id FROM project_team WHERE project_id = ? AND user_id = ?");
    $check->execute([$project_id, $user_id]);
    if ($check->fetch()) {
        return ['ok' => false, 'error' => 'User is already in the team'];
    }

    $stmt = $db->prepare("INSERT INTO project_team (project_id, user_id, role_id, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
    if ($stmt->execute([$project_id, $user_id, $role_id])) {

        // داخل addTeamMember في project_functions.php
        // استدعاء ملف التودو
        require_once __DIR__ . '/../../modules/todos/todo_functions.php';
        
        // جلب اسم المشروع
        $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
        
        addSystemTodo(
            $user_id, 
            "Welcome to Project: $pName", 
            "You have been added to the team. Check your tasks.", 
            "project", 
            $project_id
        );
        
        // إشعار المستخدم
        // require_once ... todo_functions ...
        // addSystemTodo($user_id, "New Project Assignment", "You have been added to project team.", "project", $project_id);
        
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Database error'];
}*/

/**
 * إزالة عضو من الفريق
 */
/*function removeTeamMember($project_id, $user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("DELETE FROM project_team WHERE project_id = ? AND user_id = ?");
    return $stmt->execute([$project_id, $user_id]);
}*/

/**
 * جلب الموظفين المتاحين للإضافة (من نفس القسم + المتعاونين الموافق عليهم)
 */
/*function getAvailableUsersForProject($project_id, $department_id) {
    $db = Database::getInstance()->pdo();
    
    // 1. موظفي القسم
    $sql = "
        SELECT id, full_name_en 
        FROM users 
        WHERE department_id = ? AND is_active = 1
    ";

    // 2. الموظفين من أقسام أخرى (تمت الموافقة على تعاونهم لهذا المشروع)
    $sql .= "
        UNION
        SELECT u.id, u.full_name_en
        FROM collaborations c
        JOIN users u ON u.id = c.assigned_user_id
        WHERE c.parent_type = 'project' 
          AND c.parent_id = ? 
          AND c.status_id = 2 -- Approved
          AND c.assigned_user_id IS NOT NULL
    ";

    // استثناء الموجودين بالفعل في الفريق
    $finalSql = "SELECT * FROM ($sql) AS combined 
                 WHERE id NOT IN (SELECT user_id FROM project_team WHERE project_id = ?)
                 ORDER BY full_name_en";

    $stmt = $db->prepare($finalSql);
    $stmt->execute([$department_id, $project_id, $project_id]);
    return $stmt->fetchAll();
}*/

?>