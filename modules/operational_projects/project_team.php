<?php
// modules/operational_projects/project_team.php

// جلب أعضاء الفريق
function getProjectTeam($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "SELECT pt.*, u.full_name_en, u.email, u.avatar, r.name AS role_name 
            FROM project_team pt
            JOIN users u ON u.id = pt.user_id
            JOIN project_roles r ON r.id = pt.role_id
            WHERE pt.project_id = ? AND pt.is_active = 1
            ORDER BY r.id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

// جلب المستخدمين المتاحين للإضافة
function getAvailableUsersForProject($project_id, $department_id) {
    $db = Database::getInstance()->pdo();
    // جلب موظفي القسم + المتعاونين
    $sql = "SELECT id, full_name_en FROM users WHERE department_id = $department_id AND is_active = 1";
    $sql .= " UNION SELECT u.id, u.full_name_en FROM collaborations c JOIN users u ON u.id = c.assigned_user_id WHERE c.parent_type = 'project' AND c.parent_id = $project_id AND c.status_id = 2 AND c.assigned_user_id IS NOT NULL";
    
    // استبعاد من هم في الفريق أصلاً
    $finalSql = "SELECT * FROM ($sql) AS potential WHERE id NOT IN (SELECT user_id FROM project_team WHERE project_id = $project_id) ORDER BY full_name_en";
    return $db->query($finalSql)->fetchAll();
}

/**
 * إضافة عضو للفريق
 * تم التعديل لإرسال إشعار باستخدام الدالة الموحدة
 */
function addTeamMember($project_id, $user_id, $role_id) {
    $db = Database::getInstance()->pdo();
    
    // التحقق من عدم التكرار
    $check = $db->prepare("SELECT id FROM project_team WHERE project_id = ? AND user_id = ?");
    $check->execute([$project_id, $user_id]);
    if ($check->fetch()) return ['ok' => false, 'error' => 'User already in team'];

    $stmt = $db->prepare("INSERT INTO project_team (project_id, user_id, role_id, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
    
    if ($stmt->execute([$project_id, $user_id, $role_id])) {
        
        // [MODIFIED] استخدام الدالة الموحدة للإشعارات
        // notifyTeamChange موجودة في project_functions.php التي تضمن هذا الملف
        if (function_exists('notifyTeamChange')) {
            notifyTeamChange($project_id, $user_id, 'added');
        } else {
            // Fallback بسيط في حال لم تكن الدالة موجودة (للحماية)
            require_once __DIR__ . '/../../modules/todos/todo_functions.php';
            $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
            addSystemTodo($user_id, "Project Assignment", "You have been added to project: $pName", "project_view", $project_id);
        }

        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Database error'];
}

// تعديل دور العضو
function updateTeamMemberRole($project_id, $user_id, $new_role_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("UPDATE project_team SET role_id = ? WHERE project_id = ? AND user_id = ?");
    if ($stmt->execute([$new_role_id, $project_id, $user_id])) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Update failed'];
}

/**
 * إزالة عضو من الفريق
 * تم التعديل لإرسال إشعار قبل الحذف
 */
function removeTeamMember($project_id, $user_id) {
    $db = Database::getInstance()->pdo();
    
    // [MODIFIED] إرسال إشعار الإزالة قبل الحذف الفعلي
    if (function_exists('notifyTeamChange')) {
        notifyTeamChange($project_id, $user_id, 'removed');
    }

    // الحذف المباشر
    return $db->prepare("DELETE FROM project_team WHERE project_id = ? AND user_id = ?")->execute([$project_id, $user_id]);
}
?>