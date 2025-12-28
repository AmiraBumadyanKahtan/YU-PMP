<?php
// modules/operational_projects/php/resources_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php"; // يضمن project_resources.php

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) {
    include "../../layout/header.php";
    echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-danger'>Project not found.</div></div></div>";
    exit;
}

// ============================================================
// 1. منطق حالة المشروع (Locked Status Logic)
// ============================================================
$lockedStatuses = [1, 2, 4, 8, 7]; 
$isLockedStatus = in_array($project['status_id'], $lockedStatuses);

// ============================================================
// 2. تحديد الصلاحيات (Permissions)
// ============================================================
// التحقق من صلاحية المشاهدة
if (!userCanInProject($id, 'proj_view_dashboard')) {
    header("Location: ../../error/403.php");
    exit;
}

// صلاحية الإدارة (إضافة/حذف) تعتمد على 'presource_manage' + المشروع غير مقفل
$canManageResources = userCanInProject($id, 'presource_manage') && !$isLockedStatus;


// ============================================================
// 3. معالجة الطلبات (POST Handling)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLockedStatus) {
    die("Action Denied: Project is locked.");
}

// إضافة مورد
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource']) && $canManageResources) {
    $data = [
        'project_id' => $id,
        'resource_type_id' => $_POST['resource_type_id'],
        'name' => trim($_POST['name']),
        'qty' => max(1, intval($_POST['qty'])),
        'cost_per_unit' => floatval($_POST['cost_per_unit']),
        'assigned_to' => !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null,
        'notes' => trim($_POST['notes'])
    ];
    
    $res = addProjectResource($data);
    if ($res['ok']) {
        header("Location: resources.php?id=$id&msg=added");
        exit;
    } else {
        $error = $res['error'];
    }
}

// حذف مورد
if (isset($_GET['delete_res']) && $canManageResources) {
    deleteProjectResource($_GET['delete_res']);
    header("Location: resources.php?id=$id&msg=deleted");
    exit;
}


// ============================================================
// 4. جلب البيانات للعرض
// ============================================================
$resources = getProjectResources($id);
$resourceTypes = getResourceTypes();
$teamMembers = getProjectTeam($id); 
$totalResourcesCost = getProjectResourcesTotalCost($id);

// تجميع البيانات للرسم البياني (اختياري)
$resourcesByCategory = [];
foreach($resources as $r) {
    $cat = ucfirst($r['category']);
    if(!isset($resourcesByCategory[$cat])) $resourcesByCategory[$cat] = 0;
    $resourcesByCategory[$cat] += $r['total_cost'];
}

?>