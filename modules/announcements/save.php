<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

$db = Database::getInstance()->pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title   = trim($_POST['title']);
    $message = trim($_POST['message']);
    $type    = $_POST['type'];
    $id      = $_POST['id'] ?? null;
    $isActive = $_POST['is_active'] ?? 1; // الافتراضي نشط عند الإنشاء

    if ($id) {
        // تحديث
        if (!Auth::can('sys_ann_edit')) die("Access Denied");
        $stmt = $db->prepare("UPDATE announcements SET title=?, message=?, type=?, is_active=? WHERE id=?");
        $stmt->execute([$title, $message, $type, $isActive, $id]);
    } else {
        // إنشاء
        if (!Auth::can('sys_ann_create')) die("Access Denied");
        $stmt = $db->prepare("INSERT INTO announcements (title, message, type, created_by, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$title, $message, $type, $_SESSION['user_id']]);
    }

    header("Location: list.php?msg=" . ($id ? 'updated' : 'added'));
    exit;
}