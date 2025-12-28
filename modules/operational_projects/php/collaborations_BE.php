<?php
// modules/operational_projects/php/collaborations_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// ============================================================
// 1. التحقق من صلاحية المشاهدة (Page Access)
// ============================================================
// نستخدم 'proj_view_dashboard' كصلاحية عامة للدخول للمشروع
$canView = userCanInProject($id, 'proj_view_dashboard');

// إذا لم يكن لديه صلاحية صريحة، نتحقق مما إذا كان المشروع عام
if (!$canView && $project['visibility'] === 'public') {
    $canView = true;
}

if (!$canView) {
    include "../../layout/header.php";
    echo "<div class='main-content'><div class='page-wrapper'>";
    // لا يمكن تضمين project_header_inc هنا لأن المستخدم قد لا يملك صلاحية رؤية البيانات
    echo "<div class='alert alert-danger' style='margin-top:20px;'><i class='fa-solid fa-lock'></i> <strong>Access Denied:</strong> You do not have permission to view collaborations for this project.</div>";
    echo "</div></div>";
    exit;
}

// ============================================================
// 2. التحقق من حالة المشروع (Status Check)
// ============================================================
// الحالات المقفلة: 2 (Pending Review), 4 (Rejected), 8 (Completed), 7 (On Hold)
$lockedStatuses = [1, 2, 4, 8, 7]; 
$isLockedStatus = !in_array($project['status_id'], $lockedStatuses);

// ============================================================
// 3. تحديد صلاحية "طلب التعاون" (Action Permission)
// ============================================================
// طلب مورد خارجي يعتبر جزءاً من إدارة الفريق ('proj_manage_team')
// يجب أن يملك الصلاحية AND المشروع قابل للتعديل
$canRequestCollab = userCanInProject($id, 'proj_manage_team') && $isLockedStatus;


// --- [Header Data Logic] ---
$db = Database::getInstance()->pdo();
$tasksTotal = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND is_deleted=0")->fetchColumn();
$tasksDone = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND status_id=3 AND is_deleted=0")->fetchColumn();
$risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='project' AND parent_id=$id")->fetchColumn();
$daysLeft = 0;
if ($project['end_date']) { $end = new DateTime($project['end_date']); $now = new DateTime(); if ($end > $now) { $daysLeft = $now->diff($end)->days; } }
$budgetVal = $project['approved_budget'] ?? $project['budget_max'];
$h_spentVal = $project['spent_budget'] ?? 0; 
$progPercent = $project['progress_percentage'] ?? 0;

$isApproved = ($project['status_id'] == 5); 

// جلب الطلبات
$collabs = $db->prepare("SELECT c.*, d.name as dept_name, u.full_name_en as assigned_user, cs.status_name, cs.id as status_id FROM collaborations c JOIN departments d ON d.id = c.department_id LEFT JOIN users u ON u.id = c.assigned_user_id LEFT JOIN collaboration_statuses cs ON cs.id = c.status_id WHERE c.parent_type = 'project' AND c.parent_id = ? ORDER BY c.created_at DESC");
$collabs->execute([$id]);
$requests = $collabs->fetchAll();
?>