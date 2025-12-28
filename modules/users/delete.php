<?php
// modules/users/delete.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php";

// ✅ التعديل: استخدام صلاحية الحذف المحددة
if (!Auth::can('sys_user_delete')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
    die("Access Denied");
}

// =========================================================
// معالجة طلب الحذف (POST via AJAX)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception("Invalid user ID");
        }

        // 1. منع حذف الحساب الشخصي
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'blocked', 'message' => 'You cannot delete your own account']);
            exit;
        }

        // 2. التحقق من الارتباطات
        if (!canDeleteUser($id)) {
            echo json_encode([
                'status' => 'blocked', 
                'message' => 'Cannot delete: User is linked to active Projects, Initiatives, or Teams. Please reassign their work first.'
            ]);
            exit;
        }

        // 3. تنفيذ الحذف الناعم
        if (softDeleteUser($id)) {
            
            // محاولة تسجيل النشاط (دون إيقاف العملية إذا فشل السجل)
            try {
                $db = Database::getInstance()->pdo();
                $logStmt = $db->prepare("
                    INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, ip_address, created_at)
                    VALUES (?, 'soft_delete', 'user', ?, 'soft deleted', ?, NOW())
                ");
                $ip = $_SERVER['REMOTE_ADDR'] ?? '::1';
                $logStmt->execute([$_SESSION['user_id'], $id, $ip]);
            } catch (Exception $e) { /* تجاهل خطأ السجل */ }

            echo json_encode(['status' => 'success']);
            exit;
        } else {
            throw new Exception("Database failed to update record.");
        }

    } catch (Exception $e) {
        // اصطياد أي خطأ وإرساله كـ JSON
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// (الجزء الخاص بـ GET بقي كما هو، لكنه نادراً ما يستخدم مع AJAX)
// ...
?>