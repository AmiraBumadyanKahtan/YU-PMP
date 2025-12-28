<?php
// modules/operational_projects/php/milestones_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

// دالة التحقق من وجود الملف الإضافي (للاحتياط)
if (file_exists("../project_roles/functions.php")) { require_once "../project_roles/functions.php"; }

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");


// ============================================================
// 1. التحقق من حالة المشروع (Locked Status Logic)
// ============================================================
// الحالات المقفلة: 2 (Pending Review), 4 (Rejected), 8 (Completed), 7 (On Hold)
$lockedStatuses = [1, 2, 4, 8, 7]; 
$isLockedStatus = !in_array($project['status_id'], $lockedStatuses);


// ============================================================
// 2. تعريف الصلاحيات الدقيقة (Fine-Grained Permissions)
// ============================================================
$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office']);

// أ) صلاحية إدارة المراحل (Milestones) - مفتاح الجدول: pmilestone_manage
$canManageMilestones = ($isManager || $isSuperAdmin || userCanInProject($id, 'pmilestone_manage')) && $isLockedStatus;

// ب) صلاحية إنشاء/حذف المهام (Tasks) - مفتاح الجدول: ptask_create / ptask_delete
$canManageTasks = ($isManager || $isSuperAdmin || userCanInProject($id, 'ptask_create')) && $isLockedStatus;

// ج) صلاحية تعديل المهام المسندة للموظف - مفتاح الجدول: ptask_edit
// (يسمح للموظف بتحديث حالة وتقدم مهامه فقط)
$canEditOwnTasks = ($isManager || $isSuperAdmin || userCanInProject($id, 'ptask_edit') || userCanInProject($id, 'ptask_update_progress')) && $isLockedStatus;

$db = Database::getInstance()->pdo();


// ---------------------------------------------------------
// معالجة النماذج (POST Actions)
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLockedStatus) {
    die("Action Denied: Project is locked.");
}

// 1. حذف المهمة (يتطلب صلاحية إدارة المهام)
if (isset($_GET['delete_task']) && $canManageTasks) {
    $taskId = $_GET['delete_task'];
    $msId = $db->query("SELECT milestone_id FROM project_tasks WHERE id=$taskId")->fetchColumn();
    
    $db->prepare("UPDATE project_tasks SET is_deleted=1 WHERE id=?")->execute([$taskId]);
    
    if($msId && function_exists('recalculateMilestone')) {
        recalculateMilestone($msId);
    } else {
        recalculateProject($id);
    }
    header("Location: milestones.php?id=$id&msg=deleted");
    exit;
}

// 2. حفظ المهمة (إضافة / تعديل)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_task'])) {
    
    $taskId = $_POST['task_id'] ?? 0;
    
    // الحالة أ: إضافة جديدة أو تعديل كامل (للمدراء ومن لديهم ptask_create)
    if ($canManageTasks) {
        $progress = 0;
        $status = $_POST['status_id'] ?? 1;
        if ($status == 3) $progress = 100;
        elseif ($status == 2) $progress = 50;

        $milestoneId = !empty($_POST['milestone_id']) ? $_POST['milestone_id'] : null;

        $data = [
            'project_id' => $id,
            'milestone_id' => $milestoneId,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'assigned_to' => !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null,
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'due_date' => $_POST['due_date'],
            'priority_id' => $_POST['priority_id'] ?? 2,
            'weight' => $_POST['weight'] ?? 1,
            'cost_estimate' => $_POST['cost_estimate'] ?? 0,
            'status_id' => $status,
            'progress' => $progress,
            'cost_spent' => $_POST['cost_spent'] ?? 0
        ];
        
        $res = ['ok' => false];
        if (!empty($taskId)) {
            $res = updateTask($taskId, $data);
        } else {
            $res = createTask($data);
        }

        if ($res['ok']) { header("Location: milestones.php?id=$id&msg=task_saved"); exit; }
        else { $error = $res['error']; }
    }
    
    // الحالة ب: تعديل جزئي (للموظف على مهامه الخاصة)
    elseif ($taskId && $canEditOwnTasks) {
        $assignedTo = $db->query("SELECT assigned_to FROM project_tasks WHERE id=$taskId")->fetchColumn();
        
        if ($assignedTo == $_SESSION['user_id']) {
            $statusId = $_POST['status_id'];
            $costSpent = $_POST['cost_spent'];
            $progress = 0;
            if ($statusId == 3) $progress = 100;
            elseif ($statusId == 2) $progress = 50;

            // دالة التحديث الجزئي
            $res = updateTaskProgressOnly($taskId, $statusId, $progress, $costSpent);
            
            if ($res['ok']) { header("Location: milestones.php?id=$id&msg=task_saved"); exit; }
            else { $error = $res['error']; }
        } else {
            $error = "Access Denied: You can only edit tasks assigned to you.";
        }
    } else {
        $error = "Access Denied: You do not have permission to manage tasks.";
    }
}

// 3. إضافة مرحلة (يتطلب صلاحية pmilestone_manage)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_milestone']) && $canManageMilestones) {
    $newStart = $_POST['start_date'];
    $newDue = $_POST['due_date'];

    // التحقق من تداخل التواريخ (اختياري)
    $checkOverlap = $db->prepare("SELECT COUNT(*) FROM project_milestones WHERE project_id = ? AND is_deleted = 0 AND (start_date <= ? AND due_date >= ?)");
    $checkOverlap->execute([$id, $newDue, $newStart]);
    
    // يمكنك تفعيل التحقق إذا أردت، حالياً سأسمح بالتداخل للمرونة
    if (false && $checkOverlap->fetchColumn() > 0) {
        $error = "Date Conflict: Dates overlap with an existing milestone.";
    } else {
        $data = [
            'project_id' => $id, 'name' => $_POST['name'], 'description' => $_POST['description'],
            'start_date' => $newStart, 'due_date' => $newDue, 'cost_amount' => $_POST['cost_amount'] ?? 0
        ];
        $result = createMilestone($data);
        if ($result['ok']) { header("Location: milestones.php?id=$id&msg=milestone_added"); exit; }
        else { $error = $result['error']; }
    }
}

// جلب البيانات للعرض
$milestones = getProjectMilestones($id);
$teamMembers = getProjectTeam($id); 

$generalTasks = $db->prepare("
    SELECT t.*, u.full_name_en as assignee_name, s.name as status_name 
    FROM project_tasks t
    LEFT JOIN users u ON u.id = t.assigned_to
    LEFT JOIN task_statuses s ON s.id = t.status_id
    WHERE t.project_id = ? AND t.milestone_id IS NULL AND t.is_deleted = 0
    ORDER BY t.due_date ASC
");
$generalTasks->execute([$id]);
$generalTasks = $generalTasks->fetchAll();

// --- [LOGIC FOR MANDATORY MILESTONE] ---
// التحقق: هل يوجد مراحل؟
$hasMilestones = (count($milestones) > 0);
?>