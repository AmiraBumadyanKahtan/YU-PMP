<?php
// modules/operational_projects/milestones.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

// استدعاء ملفات الأدوار إذا وجدت
if (file_exists("../project_roles/functions.php")) { require_once "../project_roles/functions.php"; }

// 1. التحقق من الوصول
if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// --- الصلاحيات ---
$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office']);

// صلاحية الإدارة الكاملة (مدير، سوبر، أو لديه إذن إدارة المهام)
$canManage = ($isManager || $isSuperAdmin || userCanInProject($id, 'manage_project_tasks'));

// صلاحية التعديل للمهام الموكلة (للموظفين)
$canEditAssigned = ($isManager || $isSuperAdmin || userCanInProject($id, 'edit_assigned_tasks'));

$isApproved = ($project['status_id'] == 5); 

$db = Database::getInstance()->pdo();

// ---------------------------------------------------------
// معالجة النماذج (POST Actions)
// ---------------------------------------------------------

// أ) حذف المهمة (فقط لمن يملك صلاحية الإدارة)
if (isset($_GET['delete_task']) && $canManage) {
    $taskId = $_GET['delete_task'];
    $msId = $db->query("SELECT milestone_id FROM project_tasks WHERE id=$taskId")->fetchColumn();
    
    $db->prepare("UPDATE project_tasks SET is_deleted=1 WHERE id=?")->execute([$taskId]);
    
    if($msId && function_exists('recalculateMilestone')) {
        recalculateMilestone($msId);
    }
    header("Location: milestones.php?id=$id&msg=deleted");
    exit;
}

// ب) حفظ المهمة (إضافة / تعديل)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_task'])) {
    
    $taskId = $_POST['task_id'] ?? 0;
    $hasPermission = false;

    // 1. التحقق من الصلاحية
    if ($canManage) {
        $hasPermission = true; // المدير يعدل ويضيف أي شيء
    } elseif ($taskId) {
        // إذا كانت مهمة موجودة (تعديل)، نتحقق هل المستخدم هو الموكل إليه؟
        $assignedTo = $db->query("SELECT assigned_to FROM project_tasks WHERE id=$taskId")->fetchColumn();
        if ($canEditAssigned && $assignedTo == $_SESSION['user_id']) {
            $hasPermission = true;
        }
    }

    if (!$hasPermission) {
        $error = "Access Denied: You do not have permission to perform this action.";
    } else {
        // التجهيز للحفظ
        $progress = 0;
        $status = $_POST['status_id'] ?? 1;
        
        // منطق النسبة التلقائي
        if ($status == 3) $progress = 100;
        elseif ($status == 2) $progress = 50;
        
        // معالجة الـ Milestone ID
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
            // فقط المدير يضيف مهام جديدة
            if ($canManage) {
                $res = createTask($data);
            } else {
                $res = ['ok' => false, 'error' => 'Only managers can create new tasks.'];
            }
        }
        
        if ($res['ok']) { 
            if($milestoneId && function_exists('recalculateMilestone')) recalculateMilestone($milestoneId);
            header("Location: milestones.php?id=$id&msg=task_saved"); 
            exit; 
        } else { 
            $error = $res['error']; 
        }
    }
}

// ج) إضافة مرحلة (Milestone) - للمدراء فقط
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_milestone']) && $canManage) {
    $newStart = $_POST['start_date'];
    $newDue = $_POST['due_date'];

    $checkOverlap = $db->prepare("
        SELECT COUNT(*) FROM project_milestones 
        WHERE project_id = ? AND is_deleted = 0
        AND (start_date <= ? AND due_date >= ?)
    ");
    $checkOverlap->execute([$id, $newDue, $newStart]);
    
    if ($checkOverlap->fetchColumn() > 0) {
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

// ---------------------------------------------------------
// جلب البيانات للعرض
// ---------------------------------------------------------
$milestones = getProjectMilestones($id);
$teamMembers = getProjectTeam($id); 

// جلب المهام العامة
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

?>