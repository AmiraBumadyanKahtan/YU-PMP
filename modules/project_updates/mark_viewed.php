<?php
require_once "../../core/init.php";

if (!in_array('view_project_updates_ceo', $_SESSION['permissions'])) {
    die("Access Denied");
}

$id = $_POST['id'] ?? null;
$db = Database::getInstance()->pdo();

$stmt = $db->prepare("UPDATE project_updates SET status = 'viewed' WHERE id = ?");
$stmt->execute([$id]);

header("Location: project_updates_ceo.php");
exit;
