<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::can('sys_ann_delete')) {
    die("Access Denied");
}

$id = (int)$_GET['id'];
$db = Database::getInstance()->pdo();

// Soft Delete (Archive)
$stmt = $db->prepare("UPDATE announcements SET is_active = 0 WHERE id = ?");
$stmt->execute([$id]);

header("Location: list.php?msg=deleted");
exit;