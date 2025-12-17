<?php
// modules/operational_projects/kpis.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

// استدعاء ملفات الأدوار
if (file_exists("../project_roles/functions.php")) { require_once "../project_roles/functions.php"; }

// 1. التحقق من الصلاحيات
if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// --- استدعاء الهيدر الموحد ---
// (يحتوي على منطق حساب النسب والميزانية وعرض الهيدر والتابز)
// تأكد من وجود ملف project_header_inc.php في نفس المجلد

$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$canEdit = ($isManager || Auth::can('manage_project_kpis'));
$isApproved = ($project['status_id'] == 5);

// ---------------------------------------------------------
// معالجة النماذج (POST Actions)
// ---------------------------------------------------------

// أ) إضافة مؤشر جديد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kpi']) && $canEdit) {
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

// ب) تحديث القراءة الحالية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reading'])) {
    updateKPIReading($_POST['kpi_id'], $_POST['current_value']);
    header("Location: kpis.php?id=$id&msg=updated");
    exit;
}

// ج) حذف المؤشر
if (isset($_GET['delete_kpi']) && $canEdit) {
    deleteKPI($_GET['delete_kpi']);
    header("Location: kpis.php?id=$id&msg=deleted");
    exit;
}

// جلب البيانات
$kpis = getProjectKPIs($id);
$teamMembers = getProjectTeam($id); 

?>