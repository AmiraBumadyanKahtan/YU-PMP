<?php
// modules/operational_projects/php/risks_BE.php
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

// ============================================================
// 1. التحقق من حالة المشروع (Locked Status Logic)
// ============================================================
// الحالات المقفلة: 2 (Pending Review), 4 (Rejected), 8 (Completed), 7 (On Hold)
$lockedStatuses = [1 ,2, 4, 8, 7]; 
$isLockedStatus = in_array($project['status_id'], $lockedStatuses);


// ============================================================
// 2. تعريف الصلاحيات (Permission Logic)
// ============================================================
$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office']);

// الصلاحية: المدير أو السوبر أو لديه صلاحية 'prisk_manage' + المشروع غير مقفل
// تأكدنا من استخدام المفتاح الصحيح من الجدول (prisk_manage)
$canEdit = ($isManager || $isSuperAdmin || userCanInProject($id, 'prisk_manage')) && !$isLockedStatus; 

$isApproved = ($project['status_id'] == 5); 


// ---------------------------------------------------------
// معالجة النماذج (POST Actions)
// ---------------------------------------------------------

// حماية إضافية: منع أي عملية POST إذا كان المشروع مقفلاً
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLockedStatus) {
    die("Action Denied: Project is locked.");
}

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