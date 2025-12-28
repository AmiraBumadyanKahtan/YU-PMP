<?php
// modules/operational_projects/php/kpis_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// ============================================================
// 1. التحقق من حالة المشروع (Locked Status Logic)
// ============================================================
// الحالات المقفلة: 2 (Pending Review), 4 (Rejected), 8 (Completed), 7 (On Hold)
$lockedStatuses = [1, 2, 4, 8, 7]; 
$isLockedStatus = in_array($project['status_id'], $lockedStatuses);


// ============================================================
// 2. تعريف الصلاحيات (Permission Logic)
// ============================================================
$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office']);

// أ) صلاحية إدارة المؤشرات (إضافة/حذف) - مفتاح الجدول: pkpi_manage
$canManageKPIs = ($isManager || $isSuperAdmin || userCanInProject($id, 'pkpi_manage')) && !$isLockedStatus;

// ب) صلاحية تحديث القراءة (Update Reading) - مفتاح الجدول: pkpi_update_reading
// هذه الصلاحية عامة، لكن في التحديث الفعلي سنتحقق أيضاً من المالك (Owner)
$canUpdateReadingGeneric = ($isManager || $isSuperAdmin || userCanInProject($id, 'pkpi_update_reading')) && !$isLockedStatus;

$db = Database::getInstance()->pdo();


// ---------------------------------------------------------
// معالجة النماذج (POST Actions)
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLockedStatus) {
    die("Action Denied: Project is locked.");
}

// 1. إضافة مؤشر جديد (يتطلب صلاحية الإدارة)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kpi']) && $canManageKPIs) {
    $data = [
        'project_id' => $id,
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'owner_id' => $_POST['owner_id'],
        'target_value' => $_POST['target_value'],
        'baseline_value' => $_POST['baseline_value'] ?? 0,
        'data_source' => $_POST['data_source'] ?? '',
        'unit' => $_POST['unit'],
        'frequency' => $_POST['frequency']
    ];
    
    $res = createKPI($data);
    if($res['ok']) {
        header("Location: kpis.php?id=$id&msg=added");
        exit;
    }
}

// 2. تحديث القراءة الحالية (مسموح للمدير، من لديه الصلاحية، أو المالك)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reading']) && !$isLockedStatus) {
    $kpiId = $_POST['kpi_id'];
    
    // التحقق من ملكية المؤشر
    $kpiOwner = $db->query("SELECT owner_id FROM kpis WHERE id = $kpiId")->fetchColumn();
    $isOwner = ($kpiOwner == $_SESSION['user_id']);

    if ($canUpdateReadingGeneric || $isOwner) {
        updateKPIReading($kpiId, $_POST['current_value']);
        header("Location: kpis.php?id=$id&msg=updated");
        exit;
    } else {
        die("Access Denied: You are not authorized to update this KPI.");
    }
}

// 3. حذف المؤشر (يتطلب صلاحية الإدارة)
if (isset($_GET['delete_kpi']) && $canManageKPIs) {
    deleteKPI($_GET['delete_kpi']);
    header("Location: kpis.php?id=$id&msg=deleted");
    exit;
}

// جلب البيانات للعرض
$kpis = getProjectKPIs($id);
$teamMembers = getProjectTeam($id);
?>