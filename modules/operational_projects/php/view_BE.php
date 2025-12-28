<?php
// modules/operational_projects/php/view_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/common_functions.php";
require_once "project_functions.php";

// 1. التحقق من تسجيل الدخول
if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);

// 2. التحقق من وجود المشروع
if (!$project) {
    include "../../layout/header.php"; 
    echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-danger'>Project not found.</div></div></div>";
    exit;
}

// 3. التحقق من صلاحية "المشاهدة" الأساسية (View Dashboard)
// نستخدم userCanInProject للتحقق مما إذا كان المستخدم يملك صلاحية العرض في سياق هذا المشروع
$canView = userCanInProject($id, 'proj_view_dashboard');

// إذا لم يكن لديه صلاحية صريحة، نتحقق مما إذا كان المشروع عام (Public)
if (!$canView && $project['visibility'] === 'public') {
    $canView = true;
}

if (!$canView) {
    include "../../layout/header.php"; include "../../layout/sidebar.php";
    echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-danger'><i class='fa-solid fa-lock'></i> Access Denied. You do not have permission to view this project.</div></div></div>";
    exit;
}

// 4. تعريف الصلاحيات الدقيقة للإجراءات (Action Permissions)
// نستخدم مفاتيح الصلاحيات الموجودة في جدول permissions
$canEditBasic   = userCanInProject($id, 'proj_edit_basic');      // تعديل البيانات الأساسية والأهداف
$canManageDocs  = userCanInProject($id, 'pdoc_manage');          // رفع وحذف الملفات
$canSubmit      = userCanInProject($id, 'proj_submit_approval'); // إرسال للموافقة

$db = Database::getInstance()->pdo();

// التحقق من حالة المشروع (لتقييد الإجراءات حسب الحالة)
$statusId = $project['status_id'];
$isDraft = ($statusId == 1);
$isReturned = ($statusId == 3);
$isEditableStatus = ($isDraft || $isReturned); // التعديل مسموح فقط في هذه الحالات

// --- معالجة النماذج (POST Requests) ---

// 1. إضافة هدف استراتيجي (يتطلب صلاحية التعديل + حالة قابلة للتعديل)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_objective']) && $canEditBasic && $isEditableStatus) {
    addProjectObjective($id, $_POST['new_objective']);
    header("Location: view.php?id=$id&msg=objective_added");
    exit;
}

// 2. رفع ملف داعم (يتطلب صلاحية إدارة المستندات + حالة قابلة للتعديل)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_support_doc']) && $canManageDocs && $isEditableStatus) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $data = [
            'project_id' => $id,
            'title' => $_POST['doc_title'],
            'description' => 'Supporting Document for Approval',
            'parent_type' => 'project',
            'parent_id' => $id
        ];
        // دالة الرفع موجودة في project_doc.php المضمنة داخل project_functions.php
        $res = uploadProjectDocument($data, $_FILES['file']);
        if($res['ok']) { header("Location: view.php?id=$id&msg=doc_uploaded"); exit; }
        else { $error = $res['error']; }
    } else {
        $error = "Please select a valid file.";
    }
}

// 3. حذف ملف (يتطلب صلاحية إدارة المستندات + حالة قابلة للتعديل)
if (isset($_GET['delete_doc']) && $canManageDocs && $isEditableStatus) {
    // يجب التحقق من أن المستند تابع لهذا المشروع قبل الحذف (أمان إضافي)
    $docId = $_GET['delete_doc'];
    $verifyDoc = $db->query("SELECT 1 FROM documents WHERE id=$docId AND parent_type='project' AND parent_id=$id")->fetch();
    
    if ($verifyDoc) {
        deleteDocument($docId);
        header("Location: view.php?id=$id&msg=doc_deleted");
        exit;
    }
}

// 4. إرسال للموافقة (يتطلب صلاحية الإرسال + حالة قابلة للتعديل)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_approval']) && $canSubmit && $isEditableStatus) {
    // التحقق: هل يوجد ملفات مرفقة؟
    $docCount = $db->query("SELECT COUNT(*) FROM documents WHERE parent_type='project' AND parent_id=$id AND is_deleted=0")->fetchColumn();
    
    if ($docCount > 0) {
        // يوجد ملفات، تابع الإرسال
        $res = submitProjectForApproval($id, $_SESSION['user_id']);
        if ($res['ok']) { header("Location: view.php?id=$id&msg=submitted"); exit; }
        else { $error = $res['error']; }
    } else {
        $error = "Submission Failed: You must upload at least one supporting document before submitting for approval.";
    }
}

// --- جلب البيانات للعرض ---
$objectives = getProjectObjectives($id);
$tracker = getProjectWorkflowTracker($id); // دالة التتبع (Approvals)
// جلب المستندات
$supportDocs = $db->query("SELECT * FROM documents WHERE parent_type='project' AND parent_id=$id AND is_deleted=0 ORDER BY created_at DESC")->fetchAll();

// متغير للتحقق في الواجهة (هل يوجد مستندات؟)
$hasDocuments = (count($supportDocs) > 0);

?>