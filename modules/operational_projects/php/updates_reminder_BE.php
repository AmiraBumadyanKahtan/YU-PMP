<?php
// modules/operational_projects/updates_reminder.php
require_once "../../core/config.php";
require_once "../../core/auth.php";
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

// 3. التحقق من صلاحية عرض الصفحة (View Permission)
// هذه الدالة تتحقق تلقائياً من: السوبر أدمن، المدير، رئيس القسم، أو الصلاحيات الممنوحة
if (!userCanInProject($id, 'view_project_updates')) {
    include "../../layout/header.php";
    echo "<div class='main-content'><div class='page-wrapper'>";
    
    // نستدعي الهيدر حتى يرى المستخدم تفاصيل المشروع والتابز (المقفلة)
    include "project_header_inc.php"; 
    
    echo "<div class='alert alert-danger' style='margin-top:20px; padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 8px;'>";
    echo "<i class='fa-solid fa-lock'></i> <strong>Access Denied:</strong> You do not have permission to view updates for this project.";
    echo "</div>";
    
    echo "</div></div>";
    exit;
}

// 4. التحقق من صلاحية الإرسال (Submit Permission)
$canSubmit = userCanInProject($id, 'send_progress_update');

// 5. معالجة الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update']) && $canSubmit) {
    // نأخذ النسبة الحالية من النظام لضمان الدقة
    $progress = $project['progress_percentage']; 
    $desc = $_POST['description'];
    
    $res = submitProgressUpdate($id, $progress, $desc);
    if ($res['ok']) {
        header("Location: updates_reminder.php?id=$id&msg=sent");
        exit;
    }
}

// 6. جلب السجل
$db = Database::getInstance()->pdo();
$history = $db->query("SELECT * FROM project_updates WHERE project_id = $id ORDER BY created_at DESC")->fetchAll();

// متغيرات للهيدر الموحد (لضمان عمله في حال لم يتم تعريفها داخله)
// (project_header_inc.php عادةً يقوم بحسابها، لكن للاحتياط)
if (!isset($h_progPercent)) {
    $h_tasksTotal = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND is_deleted=0")->fetchColumn();
    $h_tasksDone = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND status_id=3 AND is_deleted=0")->fetchColumn();
    $h_risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='project' AND parent_id=$id")->fetchColumn();
    $h_daysLeft = 0;
    if ($project['end_date']) {
        $h_end = new DateTime($project['end_date']);
        $h_now = new DateTime();
        if ($h_end > $h_now) { $h_daysLeft = $h_now->diff($h_end)->days; }
    }
    $h_spentVal = $project['spent_budget'] ?? 0;
    $h_budgetVal = $project['approved_budget'] ?? 0;
    if($h_budgetVal == 0) $h_budgetVal = $project['budget_max'];
    $h_moneyPercent = ($h_budgetVal > 0) ? round(($h_spentVal / $h_budgetVal) * 100) : 0;
    $h_isOverBudget = ($h_spentVal > $h_budgetVal);
    $h_progPercent = $project['progress_percentage'] ?? 0;
}
?>