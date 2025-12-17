<?php
// modules/todos/todo_functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب مهام المستخدم
 * $filter: 'all', 'pending', 'completed'
 */
function getUserTodos($user_id, $filter = 'all') {
    $db = Database::getInstance()->pdo();
    
    $sql = "SELECT * FROM user_todos WHERE user_id = ?";
    
    if ($filter === 'pending') {
        $sql .= " AND is_completed = 0";
    } elseif ($filter === 'completed') {
        $sql .= " AND is_completed = 1";
    }
    
    $sql .= " ORDER BY is_completed ASC, due_date ASC, created_at DESC"; // غير المكتمل أولاً، ثم حسب التاريخ
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

/**
 * إضافة مهمة يدوية (شخصية)
 */
function addPersonalTodo($user_id, $title, $description, $due_date) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        INSERT INTO user_todos (user_id, title, description, due_date, is_system_generated, is_completed, created_at)
        VALUES (?, ?, ?, ?, 0, 0, NOW())
    ");
    return $stmt->execute([$user_id, $title, $description, $due_date ?: null]);
}

/**
 * إضافة مهمة نظام (تلقائية)
 * تستخدم هذه الدالة من صفحات أخرى (مثلاً عند تعيين مدير لمشروع)
 */
function addSystemTodo($user_id, $title, $description, $entity_type, $entity_id, $due_date = null) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        INSERT INTO user_todos (user_id, title, description, due_date, is_system_generated, related_entity_type, related_entity_id, is_completed, created_at)
        VALUES (?, ?, ?, ?, 1, ?, ?, 0, NOW())
    ");
    return $stmt->execute([$user_id, $title, $description, $due_date, $entity_type, $entity_id]);
}

/**
 * تغيير حالة المهمة (Check/Uncheck)
 */
function toggleTodoStatus($id, $user_id) {
    $db = Database::getInstance()->pdo();
    // نعكس الحالة الحالية (إذا 0 تصير 1 والعكس)
    $stmt = $db->prepare("
        UPDATE user_todos 
        SET is_completed = NOT is_completed 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$id, $user_id]);
}

/**
 * حذف مهمة
 */
function deleteTodo($id, $user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("DELETE FROM user_todos WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}

/**
 * عداد المهام غير المكتملة (للهيدر)
 */
function countPendingTodos($user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_todos WHERE user_id = ? AND is_completed = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>