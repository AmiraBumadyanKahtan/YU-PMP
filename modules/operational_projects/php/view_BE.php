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

// 3. تعريف الصلاحيات
$db = Database::getInstance()->pdo();

// هل المستخدم هو مدير المشروع؟
$isManager = ($project['manager_id'] == $_SESSION['user_id']);

// هل المستخدم سوبر أدمن أو تنفيذي؟
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo']);

// هل المستخدم عضو في الفريق؟
$isTeamMember = $db->query("SELECT 1 FROM project_team WHERE project_id=$id AND user_id={$_SESSION['user_id']}")->fetchColumn();

// --- [إضافة جديدة] هل المستخدم هو رئيس القسم الذي يتبع له المشروع؟ ---
$deptManagerId = $db->query("SELECT manager_id FROM departments WHERE id = {$project['department_id']}")->fetchColumn();
$isDeptHead = ($deptManagerId == $_SESSION['user_id']);


// هل لديه صلاحية الدخول؟ (تمت إضافة $isDeptHead)
$hasAccess = ($isManager || $isSuperAdmin || $isTeamMember || $isDeptHead || $project['visibility'] == 'public');

if (!$hasAccess) {
    include "../../layout/header.php"; include "../../layout/sidebar.php";
    echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-danger'><i class='fa-solid fa-lock'></i> Access Denied.</div></div></div>";
    exit;
}

// تعريف صلاحية التعديل (يمكنك إضافة $isDeptHead هنا أيضاً إذا كنت تريد السماح لرئيس القسم بالتعديل)
$canEdit = ($isManager || $isSuperAdmin || Auth::can('edit_project'));

// --- معالجة النماذج ---

// 1. إضافة هدف استراتيجي
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_objective']) && $canEdit) {
    addProjectObjective($id, $_POST['new_objective']);
    header("Location: view.php?id=$id&msg=objective_added");
    exit;
}

// 2. رفع ملف داعم
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_support_doc']) && $canEdit) {
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $data = [
            'project_id' => $id,
            'title' => $_POST['doc_title'],
            'description' => 'Supporting Document for Approval',
            'parent_type' => 'project',
            'parent_id' => $id
        ];
        $res = uploadProjectDocument($data, $_FILES['file']);
        if($res['ok']) { header("Location: view.php?id=$id&msg=doc_uploaded"); exit; }
        else { $error = $res['error']; }
    } else {
        $error = "Please select a valid file.";
    }
}

// 3. حذف ملف
if (isset($_GET['delete_doc']) && $canEdit) {
    deleteDocument($_GET['delete_doc']);
    header("Location: view.php?id=$id&msg=doc_deleted");
    exit;
}

// 4. إرسال للموافقة (تم التعديل: التحقق من وجود ملفات)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_approval']) && $canEdit) {
    // التحقق: هل يوجد ملفات مرفقة؟
    $docCount = $db->query("SELECT COUNT(*) FROM documents WHERE parent_type='project' AND parent_id=$id AND is_deleted=0")->fetchColumn();
    
    if ($docCount > 0) {
        // يوجد ملفات، تابع الإرسال
        $res = submitProjectForApproval($id, $_SESSION['user_id']);
        if ($res['ok']) { header("Location: view.php?id=$id&msg=submitted"); exit; }
        else { $error = $res['error']; }
    } else {
        // لا يوجد ملفات، أظهر خطأ
        $error = "Submission Failed: You must upload at least one supporting document before submitting for approval.";
    }
}

// --- جلب البيانات للعرض ---
$objectives = getProjectObjectives($id);
$tracker = getProjectWorkflowTracker($id);
// جلب المستندات
$supportDocs = $db->query("SELECT * FROM documents WHERE parent_type='project' AND parent_id=$id AND is_deleted=0 ORDER BY created_at DESC")->fetchAll();

$statusId = $project['status_id'];
$isApproved = ($statusId == 5); 
$isDraft = ($statusId == 1);
$isReturned = ($statusId == 3);

// متغير للتحقق في الواجهة
$hasDocuments = (count($supportDocs) > 0);
?>