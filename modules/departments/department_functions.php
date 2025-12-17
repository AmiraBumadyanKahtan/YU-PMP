<?php
// modules/departments/department_functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب جميع الأقسام (مع دعم البحث)
 */
function dept_all($search = '') {
    $db = Database::getInstance()->pdo();
    
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
 * جلب قائمة المستخدمين (لملء قائمة المدراء)
 */
function dept_all_users() {
    $db = Database::getInstance()->pdo();
    return $db->query("
        SELECT id, full_name_en
        FROM users
        WHERE is_active = 1 AND (is_deleted = 0 OR is_deleted IS NULL)
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
    try {
        $log = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, ip_address) VALUES (?, 'create', 'department', ?, ?, ?)");
        $log->execute([$_SESSION['user_id'] ?? 0, $newId, $name, $_SERVER['REMOTE_ADDR'] ?? '::1']);
    } catch (Exception $e) {}

    return $newId;
}

/**
 * تحديث بيانات القسم
 */
function dept_update($id, $name, $manager_id) {
    $db = Database::getInstance()->pdo();
    $old = dept_get($id); // للنشاط (اختياري)

    $stmt = $db->prepare("UPDATE departments SET name = ?, manager_id = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$name, $manager_id ?: null, $id]);

    // تسجيل النشاط
    try {
        $log = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, old_value, new_value, ip_address) VALUES (?, 'update', 'department', ?, ?, ?, ?)");
        $log->execute([
            $_SESSION['user_id'] ?? 0, 
            $id, 
            json_encode($old), 
            json_encode(['name' => $name, 'manager' => $manager_id]), 
            $_SERVER['REMOTE_ADDR'] ?? '::1'
        ]);
    } catch (Exception $e) {}

    return $result;
}

/**
 * حذف القسم (Soft Delete)
 */
function dept_delete($id) {
    $db = Database::getInstance()->pdo();
    
    // التحقق من الارتباطات (اختياري ولكنه مفضل)
    // مثلاً هل يوجد موظفين في هذا القسم؟
    $check = $db->prepare("SELECT COUNT(*) FROM users WHERE department_id = ? AND is_deleted = 0");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        return false; // لا يمكن الحذف لوجود موظفين
    }

    $stmt = $db->prepare("UPDATE departments SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$id]);

    // تسجيل النشاط
    try {
        $log = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, ip_address) VALUES (?, 'soft_delete', 'department', ?, 'soft deleted', ?)");
        $log->execute([$_SESSION['user_id'] ?? 0, $id, $_SERVER['REMOTE_ADDR'] ?? '::1']);
    } catch (Exception $e) {}

    return $result;
}
/**
 * جلب جميع الفروع النشطة (للقوائم)
 */
function getAllActiveBranches() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name ASC")->fetchAll();
}

/**
 * جلب الفروع المرتبطة بقسم معين
 */
function getDepartmentBranches($dept_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT branch_id FROM department_branches WHERE department_id = ?");
    $stmt->execute([$dept_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * تحديث فروع القسم (حذف القديم وإضافة الجديد)
 */
function updateDepartmentBranches($dept_id, $branch_ids) {
    $db = Database::getInstance()->pdo();
    
    // 1. حذف الارتباطات القديمة
    $del = $db->prepare("DELETE FROM department_branches WHERE department_id = ?");
    $del->execute([$dept_id]);

    // 2. إضافة الارتباطات الجديدة
    if (!empty($branch_ids) && is_array($branch_ids)) {
        $ins = $db->prepare("INSERT INTO department_branches (department_id, branch_id) VALUES (?, ?)");
        foreach ($branch_ids as $bid) {
            $ins->execute([$dept_id, (int)$bid]);
        }
    }
}
?>