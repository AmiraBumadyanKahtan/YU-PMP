<?php
// modules/departments/department_functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب جميع الأقسام (مع دعم البحث)
 */
function dept_all($search = '') {
    $db = Database::getInstance()->pdo();
    
    // تعديل: استخدام Parameters للبحث لمنع أي مشاكل
    $sql = "
        SELECT d.*, u.full_name_en AS manager_name
        FROM departments d
        LEFT JOIN users u ON u.id = d.manager_id
        WHERE d.is_deleted = 0
    ";

    $params = [];
    if (!empty($search)) {
        $sql .= " AND d.name LIKE :search";
        $params[':search'] = "%$search%";
    }

    $sql .= " ORDER BY d.name ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * دالة جديدة: جلب المدراء المحتملين فقط (بدلاً من كتابة الكود في create.php)
 */
function dept_get_potential_managers() {
    $db = Database::getInstance()->pdo();
    // نفترض أن الـ role_key للمدراء هو 'department_manager' بناءً على قاعدة البيانات
    return $db->query("
        SELECT id, full_name_en
        FROM users
        WHERE primary_role_id IN (
            SELECT id FROM roles WHERE role_key = 'department_manager'
        )
        AND is_active = 1 AND is_deleted = 0
        ORDER BY full_name_en ASC
    ")->fetchAll();
}

/**
 * جلب بيانات قسم واحد
 */
function dept_get($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM departments WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * إنشاء قسم جديد
 */
function dept_create($name, $manager_id) {
    $db = Database::getInstance()->pdo();
    
    $stmt = $db->prepare("INSERT INTO departments (name, manager_id, created_at, updated_at, is_deleted) VALUES (?, ?, NOW(), NOW(), 0)");
    $stmt->execute([$name, $manager_id ?: null]);
    $newId = $db->lastInsertId();

    // تسجيل النشاط
    if ($newId) {
        log_activity('create', 'department', $newId, null, $name);
    }

    return $newId;
}

/**
 * تحديث بيانات القسم
 */
function dept_update($id, $name, $manager_id) {
    $db = Database::getInstance()->pdo();
    $old = dept_get($id); 

    $stmt = $db->prepare("UPDATE departments SET name = ?, manager_id = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$name, $manager_id ?: null, $id]);

    if ($result) {
        log_activity('update', 'department', $id, json_encode($old), json_encode(['name' => $name, 'manager' => $manager_id]));
    }

    return $result;
}

/**
 * حذف القسم (Soft Delete)
 */
function dept_delete($id) {
    $db = Database::getInstance()->pdo();
    
    // التحقق: هل يوجد موظفين مرتبطين بهذا القسم؟
    $check = $db->prepare("SELECT COUNT(*) FROM users WHERE department_id = ? AND is_deleted = 0");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        return "has_users"; // نرجع كود خطأ مخصص بدلاً من false
    }

    // التحقق: هل يوجد مشاريع مرتبطة؟ (إضافة مهمة بناء على قاعدة البيانات)
    $checkProj = $db->prepare("SELECT COUNT(*) FROM operational_projects WHERE department_id = ? AND is_deleted = 0");
    $checkProj->execute([$id]);
    if ($checkProj->fetchColumn() > 0) {
        return "has_projects";
    }

    $stmt = $db->prepare("UPDATE departments SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$id]);

    if ($result) {
        log_activity('soft_delete', 'department', $id, null, 'soft deleted');
    }

    return $result;
}

/**
 * دوال الفروع
 */
function getAllActiveBranches() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name ASC")->fetchAll();
}

function getDepartmentBranches($dept_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT branch_id FROM department_branches WHERE department_id = ?");
    $stmt->execute([$dept_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function updateDepartmentBranches($dept_id, $branch_ids) {
    $db = Database::getInstance()->pdo();
    
    // حذف القديم
    $del = $db->prepare("DELETE FROM department_branches WHERE department_id = ?");
    $del->execute([$dept_id]);

    // إضافة الجديد
    if (!empty($branch_ids) && is_array($branch_ids)) {
        $ins = $db->prepare("INSERT INTO department_branches (department_id, branch_id) VALUES (?, ?)");
        foreach ($branch_ids as $bid) {
            $ins->execute([$dept_id, (int)$bid]);
        }
    }
}
/**
 * تحقق هل اسم القسم موجود مسبقاً؟
 * $id = نستخدمه عند التعديل لاستثناء القسم الحالي من الفحص
 */
function dept_name_exists($name, $exclude_id = null) {
    $db = Database::getInstance()->pdo();
    $sql = "SELECT COUNT(*) FROM departments WHERE name = ? AND is_deleted = 0";
    $params = [$name];

    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * دالة مساعدة لتسجيل النشاط (لتنظيف الكود وتكراره)
 */
function log_activity($action, $entity_type, $entity_id, $old_value = null, $new_value = null) {
    global $db; // أو استدعي الـ Instance
    if (!$db) $db = Database::getInstance()->pdo();
    
    try {
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? 0, 
            $action, 
            $entity_type, 
            $entity_id, 
            $old_value, 
            $new_value, 
            $_SERVER['REMOTE_ADDR'] ?? '::1'
        ]);
    } catch (Exception $e) {
        // Silent fail for logging
    }
}
?>