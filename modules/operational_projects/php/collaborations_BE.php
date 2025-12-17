<?php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// --- الحماية: فقط للمصرح لهم ---
if (!userCanInProject($id, 'view_project_collaborations')) {
    include "../../layout/header.php";
    // إظهار رسالة خطأ داخلية جميلة بدلاً من صفحة بيضاء
    echo "<div class='main-content'><div class='page-wrapper'>";
    include "project_header_inc.php"; // لإظهار الهيدر حتى لو لم يكن لديه صلاحية المحتوى
    echo "<div class='alert alert-danger' style='margin-top:20px;'><i class='fa-solid fa-lock'></i> <strong>Access Denied:</strong> You do not have permission to view collaborations for this project.</div>";
    echo "</div></div>";
    exit;
}

// --- [Header Data Logic] ---
$db = Database::getInstance()->pdo();
$tasksTotal = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND is_deleted=0")->fetchColumn();
$tasksDone = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND status_id=3 AND is_deleted=0")->fetchColumn();
$risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='project' AND parent_id=$id")->fetchColumn();
$daysLeft = 0;
if ($project['end_date']) { $end = new DateTime($project['end_date']); $now = new DateTime(); if ($end > $now) { $daysLeft = $now->diff($end)->days; } }
$budgetVal = $project['approved_budget'] ?? $project['budget_max'];
$h_spentVal = $project['spent_budget'] ?? 0; // تأكد من تعريفه للهيدر
$progPercent = $project['progress_percentage'] ?? 0;

$isApproved = ($project['status_id'] == 5); 

// جلب الطلبات
$collabs = $db->prepare("SELECT c.*, d.name as dept_name, u.full_name_en as assigned_user, cs.status_name, cs.id as status_id FROM collaborations c JOIN departments d ON d.id = c.department_id LEFT JOIN users u ON u.id = c.assigned_user_id LEFT JOIN collaboration_statuses cs ON cs.id = c.status_id WHERE c.parent_type = 'project' AND c.parent_id = ? ORDER BY c.created_at DESC");
$collabs->execute([$id]);
$requests = $collabs->fetchAll();
?>