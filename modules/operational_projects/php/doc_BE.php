<?php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";
require_once "../../core/common_functions.php"; // لدالة formatSizeUnits

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// استدعاء الهيدر الموحد (يحتوي على منطق الحسابات والعرض)
// تأكد من وجود ملف project_header_inc.php في نفس المجلد

$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office']);
$canEdit = ($isManager || $isSuperAdmin || Auth::can('manage_project_documents'));
$isApproved = ($project['status_id'] == 5);

// --- معالجة الرفع ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc']) && $canEdit) {
    $parentType = $_POST['parent_type'];
    $parentId = ($parentType == 'project') ? $id : $_POST['parent_id'];

    $data = [
        'project_id' => $id,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'parent_type' => $parentType, 
        'parent_id' => $parentId 
    ];

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $res = uploadProjectDocument($data, $_FILES['file']);
        if($res['ok']) {
            header("Location: docs.php?id=$id&msg=uploaded");
            exit;
        } else {
            $error = $res['error'];
        }
    } else {
        $error = "Please select a file.";
    }
}

// --- معالجة الحذف ---
if (isset($_GET['delete_doc']) && $canEdit) {
    deleteDocument($_GET['delete_doc']);
    header("Location: docs.php?id=$id&msg=deleted");
    exit;
}

// جلب البيانات
$docs = getProjectDocuments($id); 
$milestones = getProjectMilestones($id);
$tasks = getAllProjectTasks($id);
$risks = getProjectRisks($id);

?>