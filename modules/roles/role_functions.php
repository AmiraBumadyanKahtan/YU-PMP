<?php
// modules/roles/role_functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب جميع الأدوار
 */
function getRoles() {
    $db = Database::getInstance()->pdo();
    // نستثني الدور رقم 1 (super_admin) من التعديل لضمان عدم فقدان الصلاحيات بالخطأ
    // أو يمكن عرضه للعرض فقط. هنا سنجلبه.
    return $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
}

/**
 * جلب دور معين
 */
function getRoleById($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM roles WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * جلب جميع الصلاحيات مجمعة حسب الموديول (للعرض المنظم)
 */
function getAllPermissionsGrouped() {
    $db = Database::getInstance()->pdo();
    $perms = $db->query("SELECT * FROM permissions ORDER BY module, permission_key")->fetchAll();
    
    $grouped = [];
    foreach ($perms as $p) {
        $mod = $p['module'] ?: 'General';
        $grouped[$mod][] = $p;
    }
    return $grouped;
}

/**
 * جلب مصفوفة بصلاحيات دور معين (IDs only)
 * لمعرفة أي الصناديق يجب وضع علامة صح عليها
 */
function getRolePermissionIds($role_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * إنشاء دور جديد
 */
function createRole($name, $key, $desc) {
    $db = Database::getInstance()->pdo();
    
    // التحقق من التكرار
    $check = $db->prepare("SELECT COUNT(*) FROM roles WHERE role_key = ?");
    $check->execute([$key]);
    if ($check->fetchColumn() > 0) return ['ok' => false, 'error' => 'Role Key already exists'];

    $stmt = $db->prepare("INSERT INTO roles (role_name, role_key, description) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $key, $desc])) {
        return ['ok' => true, 'id' => $db->lastInsertId()];
    }
    return ['ok' => false, 'error' => 'Insert failed'];
}

/**
 * تحديث صلاحيات الدور (المصفوفة الكبرى)
 * هذه الدالة تقوم بحذف الصلاحيات القديمة وإضافة الجديدة
 */
function updateRolePermissions($role_id, array $permission_ids) {
    $db = Database::getInstance()->pdo();
    
    try {
        $db->beginTransaction();

        // 1. حذف الصلاحيات القديمة
        $del = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $del->execute([$role_id]);

        // 2. إضافة الصلاحيات الجديدة
        if (!empty($permission_ids)) {
            $insert = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permission_ids as $pid) {
                $insert->execute([$role_id, (int)$pid]);
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
 * تحديث بيانات الدور الأساسية (الاسم والوصف)
 */
function updateRoleDetails($id, $name, $desc) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("UPDATE roles SET role_name = ?, description = ? WHERE id = ?");
    return $stmt->execute([$name, $desc, $id]);
}
?>