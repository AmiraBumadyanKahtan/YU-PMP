<?php
// modules/operational_projects/risks.php
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

$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$canEdit = ($isManager || Auth::can('manage_project_risks')); 
$isApproved = ($project['status_id'] == 5); 

// --- معالجة الحذف ---
if (isset($_GET['delete_risk']) && $canEdit) {
    deleteRisk($_GET['delete_risk']);
    header("Location: risks.php?id=$id&msg=deleted");
    exit;
}

// --- معالجة الإضافة/التحديث ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $data = [
        'project_id' => $id,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'mitigation_plan' => $_POST['mitigation_plan'],
        'probability' => $_POST['probability'],
        'impact' => $_POST['impact'],
        'status_id' => $_POST['status_id'] ?? 1
    ];

    if (!empty($_POST['risk_id'])) {
        updateRisk($_POST['risk_id'], $data);
        $msg = 'updated';
    } else {
        createRisk($data);
        $msg = 'added';
    }
    header("Location: risks.php?id=$id&msg=$msg");
    exit;
}

$risks = getProjectRisks($id);
$statuses = getRiskStatuses();

?>