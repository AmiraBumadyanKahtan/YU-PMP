<?php
// modules/operational_projects/php/team_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php"; // يحتوي على userCanInProject

// جلب الأدوار
function getProjectRolesLocal() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM project_roles ORDER BY id")->fetchAll();
}
$projectRoles = getProjectRolesLocal();

// التحقق من تسجيل الدخول
if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
// جلب بيانات المشروع
$db = Database::getInstance()->pdo();
$stmt = $db->prepare("
    SELECT p.*, 
           d.name as department_name, 
           d.manager_id as dept_manager_id, 
           m.full_name_en as manager_name,
           s.name as status_name,
           s.color as status_color
    FROM operational_projects p 
    JOIN departments d ON d.id = p.department_id 
    LEFT JOIN users m ON m.id = p.manager_id
    LEFT JOIN operational_project_statuses s ON s.id = p.status_id
    WHERE p.id = ? AND p.is_deleted = 0
");
$stmt->execute([$id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) die("Project not found");

// --- التحقق من صلاحية المشاهدة ---
$canView = userCanInProject($id, 'proj_view_dashboard');
// إذا لم يكن لديه صلاحية صريحة، نتحقق مما إذا كان المشروع عام
if (!$canView && $project['visibility'] === 'public') {
    $canView = true;
}
if (!$canView) die("Access Denied: You do not have permission to view this project.");


// --- [Unified Header Data Logic] ---
$tasksTotal = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND is_deleted=0")->fetchColumn();
$tasksDone = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND status_id=3 AND is_deleted=0")->fetchColumn();
$risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='project' AND parent_id=$id")->fetchColumn();
$progPercent = $project['progress_percentage'] ?? 0;
// --- [End Header Logic] ---


// ============================================================
// ضبط الصلاحيات (Permission Logic)
// ============================================================

// 1. هل المشروع في حالة تسمح بالتعديل؟
// الحالات المقفلة: Pending Approval (2), Rejected (4), Completed (8), On Hold (7)
// الحالات المفتوحة: Draft (1), Returned (3), In Progress (6)
$lockedStatuses = [1, 2, 4, 7, 8]; 
$isLockedStatus = !in_array($project['status_id'], $lockedStatuses);

// 2. هل يملك المستخدم صلاحية إدارة الفريق؟
// نستخدم المفتاح 'proj_manage_team' من جدول الصلاحيات
$hasManagePermission = userCanInProject($id, 'proj_manage_team');

// 3. النتيجة النهائية للصلاحية
// يجب أن يملك الصلاحية AND المشروع غير مقفل
$canManageTeam = $hasManagePermission && $isLockedStatus;


// ============================================================
// معالجة الطلبات (Add / Update / Remove)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManageTeam) {
    
    // إضافة عضو
    if (isset($_POST['add_member'])) {
        $res = addTeamMember($id, $_POST['user_id'], $_POST['role_id']);
        if ($res['ok']) { header("Location: team.php?id=$id&msg=added"); exit; }
    }
    
    // تعديل عضو
    if (isset($_POST['update_member'])) {
        $res = updateTeamMemberRole($id, $_POST['user_id'], $_POST['role_id']);
        if ($res['ok']) { header("Location: team.php?id=$id&msg=updated"); exit; }
    }
}

// حذف عضو
if (isset($_GET['remove_uid']) && $canManageTeam) {
    // منع حذف مدير المشروع (مالك المشروع) لضمان عدم وجود مشروع بدون مدير
    if ($_GET['remove_uid'] == $project['manager_id']) {
        // يمكنك توجيه رسالة خطأ هنا
        header("Location: team.php?id=$id&msg=cannot_remove_owner");
        exit;
    }
    
    removeTeamMember($id, $_GET['remove_uid']); 
    header("Location: team.php?id=$id&msg=removed"); 
    exit;
}

// جلب البيانات للعرض
$teamMembers = getProjectTeam($id);
$availableUsers = getAvailableUsersForProject($id, $project['department_id']);
?>