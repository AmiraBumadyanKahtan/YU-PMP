<?php
// modules/operational_projects/permissions.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) {
    include "../../layout/header.php";
    echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-danger'>Project not found.</div></div></div>";
    exit;
}

// التحقق من الصلاحية
if (!userCanInProject($id, 'manage_project_permissions')) {
    include "../../layout/header.php";
    echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-danger'>Access Denied.</div></div></div>";
    exit;
}

$db = Database::getInstance()->pdo();
$allPerms = getProjectPermissionsList(); 
$teamMembers = getProjectTeam($id);

// --- معالجة الحفظ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user_perm'])) {
    $uId = $_POST['user_id'];
    $pId = $_POST['permission_id'];
    $action = $_POST['action']; 

    if ($action == 'reset') {
        $db->prepare("DELETE FROM project_user_permissions WHERE project_id=? AND user_id=? AND permission_id=?")
           ->execute([$id, $uId, $pId]);
    } else {
        $grant = ($action == 'grant') ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO project_user_permissions (project_id, user_id, permission_id, is_granted) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_granted=?");
        $stmt->execute([$id, $uId, $pId, $grant, $grant]);
    }
    header("Location: permissions.php?id=$id&msg=updated");
    exit;
}

// جلب البيانات
$overrides = [];
$ovStmt = $db->prepare("SELECT * FROM project_user_permissions WHERE project_id = ?");
$ovStmt->execute([$id]);
while($row = $ovStmt->fetch()) {
    $overrides[$row['user_id']][$row['permission_id']] = $row['is_granted'];
}

$roleDefaults = [];
$rdStmt = $db->query("SELECT * FROM project_role_permissions");
while($row = $rdStmt->fetch()) {
    $roleDefaults[$row['role_id']][] = $row['permission_id'];
}
?>