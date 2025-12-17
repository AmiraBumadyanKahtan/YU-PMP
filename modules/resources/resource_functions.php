<?php
// modules/resources/resource_functions.php

require_once __DIR__ . "/../../core/Database.php";

/**
 * جلب الموارد مع الفلاتر
 */
function getFilteredResources($filters = [])
{
    $db = Database::getInstance()->pdo();

    $sql = "
        SELECT *
        FROM resource_types
        WHERE 1
    ";

    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND type_name LIKE :s";
        $params[':s'] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['category'])) {
        $sql .= " AND category = :c";
        $params[':c'] = $filters['category'];
    }

    if (isset($filters['status']) && $filters['status'] !== '') {
        $sql .= " AND is_active = :st";
        $params[':st'] = $filters['status'];
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * إنشاء مورد جديد
 */
function createResource($name, $category, $desc, $active)
{
    $db = Database::getInstance()->pdo();

    // التحقق من التكرار
    $check = $db->prepare("SELECT COUNT(*) FROM resource_types WHERE type_name = ?");
    $check->execute([$name]);
    if ($check->fetchColumn() > 0) return false;

    $stmt = $db->prepare("
        INSERT INTO resource_types (type_name, category, description, is_active, created_at, updated_at)
        VALUES (:n, :c, :d, :a, NOW(), NOW())
    ");

    $result = $stmt->execute([
        ':n' => $name,
        ':c' => $category,
        ':d' => $desc,
        ':a' => $active
    ]);

    if ($result) {
        // تسجيل نشاط
        try {
            $log = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, created_at) VALUES (?, 'create', 'resource', ?, ?, NOW())");
            $log->execute([$_SESSION['user_id'] ?? 0, $db->lastInsertId(), $name]);
        } catch (Exception $e) {}
    }

    return $result;
}

/**
 * جلب مورد بواسطة المعرف
 */
function getResourceById($id)
{
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM resource_types WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * تحديث مورد
 */
function updateResource($id, $name, $category, $desc, $active)
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        UPDATE resource_types
        SET type_name = :n,
            category = :c,
            description = :d,
            is_active = :a,
            updated_at = NOW()
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':n'  => $name,
        ':c'  => $category,
        ':d'  => $desc,
        ':a'  => $active
    ]);
}

/**
 * التحقق من استخدام المورد قبل الحذف
 */
function getResourceUsageCount($id) {
    $db = Database::getInstance()->pdo();
    // التحقق من جدول work_resources إذا كان المورد مستخدماً في مشاريع أو مبادرات
    $stmt = $db->prepare("SELECT COUNT(*) FROM work_resources WHERE resource_type_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

/**
 * حذف مورد
 */
function deleteResource($id)
{
    $db = Database::getInstance()->pdo();
    
    // منع الحذف إذا كان مستخدماً
    if (getResourceUsageCount($id) > 0) {
        return false;
    }

    $stmt = $db->prepare("DELETE FROM resource_types WHERE id = ?");
    return $stmt->execute([$id]);
}
?>