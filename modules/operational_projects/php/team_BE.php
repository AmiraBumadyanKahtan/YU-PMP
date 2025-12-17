<?php
// modules/operational_projects/team.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

// دوال الأدوار
if (file_exists("../project_roles/functions.php")) {
    require_once "../project_roles/functions.php"; 
} else {
    if (!function_exists('getProjectRoles')) {
        function getProjectRoles() {
            $db = Database::getInstance()->pdo();
            return $db->query("SELECT * FROM project_roles ORDER BY id")->fetchAll();
        }
    }
}

if (!Auth::can('view_project')) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// --- [Unified Header Data Logic] ---
$db = Database::getInstance()->pdo();
// 1. المهام
$tasksTotal = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND is_deleted=0")->fetchColumn();
$tasksDone = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND status_id=3 AND is_deleted=0")->fetchColumn();
// 2. المخاطر
$risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='project' AND parent_id=$id")->fetchColumn();
// 3. الوقت المتبقي
$daysLeft = 0;
if ($project['end_date']) {
    $end = new DateTime($project['end_date']);
    $now = new DateTime();
    if ($end > $now) { $daysLeft = $now->diff($end)->days; }
}
// 4. الميزانية
$budgetVal = $project['approved_budget'] ?? 0;
if($budgetVal == 0) $budgetVal = $project['budget_max'];

// 5. نسبة الإنجاز (المطلوبة في الدائرة)
$progPercent = $project['progress_percentage'] ?? 0;
// --- [End Header Logic] ---

$canManageTeam = (Auth::can('manage_project_team') || $project['manager_id'] == $_SESSION['user_id']);
$isApproved = ($project['status_id'] == 5); 

// معالجة الإضافة والحذف
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member']) && $canManageTeam) {
    $res = addTeamMember($id, $_POST['user_id'], $_POST['role_id']);
    if ($res['ok']) { header("Location: team.php?id=$id&msg=added"); exit; } else { $error = $res['error']; }
}
if (isset($_GET['remove_uid']) && $canManageTeam) {
    removeTeamMember($id, $_GET['remove_uid']); header("Location: team.php?id=$id&msg=removed"); exit;
}

$teamMembers = getProjectTeam($id);
$availableUsers = getAvailableUsersForProject($id, $project['department_id']);
$projectRoles = getProjectRoles(); 
?>