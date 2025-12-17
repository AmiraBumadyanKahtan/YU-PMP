<?php
// modules/departments/delete.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php"; // تأكدنا من استدعاء الكلاس

// التحقق من الصلاحيات
if (!Auth::can('manage_departments')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// التأكد من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

try {
    $db = Database::getInstance()->pdo();
    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('Invalid department ID');
    }

    /* * ✅ التحقق من الارتباطات قبل الحذف 
     * تم تعديل الاستعلامات لتناسب هيكل قاعدة البيانات الصحيح
     */
    $checks = [
        'Users' => "SELECT COUNT(*) FROM users WHERE department_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)",
        'Projects' => "SELECT COUNT(*) FROM operational_projects WHERE department_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)",
        'Collaborations' => "SELECT COUNT(*) FROM collaborations WHERE department_id = ?"
    ];

    foreach ($checks as $label => $sql) {
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode([
                'status' => 'blocked',
                'message' => "Cannot delete department. It is still linked to active {$label}."
            ]);
            exit;
        }
    }

    /* ✅ Soft Delete */
    $stmt = $db->prepare("UPDATE departments SET is_deleted = 1, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    /* ✅ تسجيل النشاط يدوياً لضمان العمل */
    try {
        $logStmt = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, ip_address, created_at) VALUES (?, 'soft_delete', 'department', ?, 'soft deleted', ?, NOW())");
        $logStmt->execute([$_SESSION['user_id'], $id, $_SERVER['REMOTE_ADDR'] ?? '::1']);
    } catch (Exception $e) { /* تجاهل خطأ السجل */ }

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
?>