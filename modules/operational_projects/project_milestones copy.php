<?php
// modules/operational_projects/project_milestones.php

// --------------------------------------------------------------------------
// دوال المساعدة (Helpers)
// --------------------------------------------------------------------------

function getTaskById($id) {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM project_tasks WHERE id = $id")->fetch();
}

if (!function_exists('validateDates')) {
    function validateDates($parentStart, $parentEnd, $childStart, $childDue) {
        return true; 
    }
}

// --------------------------------------------------------------------------
// دوال قراءة البيانات
// --------------------------------------------------------------------------

function getProjectMilestones($project_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        SELECT m.*, s.name as status_name 
        FROM project_milestones m 
        LEFT JOIN milestone_statuses s ON s.id = m.status_id
        WHERE m.project_id = ? AND m.is_deleted = 0 
        ORDER BY m.start_date ASC
    ");
    $stmt->execute([$project_id]);
    $milestones = $stmt->fetchAll();

    foreach ($milestones as &$m) {
        updateMilestoneStatusAutomatic($m);
        $m['tasks'] = getMilestoneTasks($m['id']);
    }
    return $milestones;
}

function getMilestoneTasks($milestone_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        SELECT t.*, u.full_name_en as assignee_name, s.name as status_name, p.label as priority_label
        FROM project_tasks t
        LEFT JOIN users u ON u.id = t.assigned_to
        LEFT JOIN task_statuses s ON s.id = t.status_id
        LEFT JOIN task_priorities p ON p.id = t.priority_id
        WHERE t.milestone_id = ? AND t.is_deleted = 0
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$milestone_id]);
    return $stmt->fetchAll();
}

// --------------------------------------------------------------------------
// دوال الكتابة والتعديل
// --------------------------------------------------------------------------

function createMilestone($data) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        INSERT INTO project_milestones (project_id, name, description, start_date, due_date, status_id, progress, cost_amount, created_at)
        VALUES (?, ?, ?, ?, ?, 1, 0, ?, NOW())
    ");
    if ($stmt->execute([
        $data['project_id'], $data['name'], $data['description'], 
        $data['start_date'], $data['due_date'], $data['cost_amount']
    ])) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Database error'];
}

function createTask($data) {
    $db = Database::getInstance()->pdo();
    $milestoneId = !empty($data['milestone_id']) ? $data['milestone_id'] : null;

    $stmt = $db->prepare("
        INSERT INTO project_tasks 
        (project_id, milestone_id, title, description, assigned_to, start_date, due_date, status_id, priority_id, weight, cost_estimate, cost_spent, progress, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())
    ");
    
    try {
        if ($stmt->execute([
            $data['project_id'], $milestoneId, $data['title'], $data['description'],
            $data['assigned_to'], $data['start_date'], $data['due_date'], 
            1, $data['priority_id'], $data['weight'], $data['cost_estimate'] ?? 0
        ])) {
            $taskId = $db->lastInsertId();

            // [MODIFIED] إرسال إشعار للموظف عند تعيين مهمة جديدة
            if (!empty($data['assigned_to'])) {
                if (function_exists('notifyTaskAssignment')) {
                    notifyTaskAssignment($taskId, $data['assigned_to'], $data['project_id']);
                } else {
                    // Fallback
                    require_once __DIR__ . '/../../modules/todos/todo_functions.php';
                    addSystemTodo($data['assigned_to'], "New Task: " . substr($data['title'],0,20), "You have a new task.", "task_view", $taskId);
                }
            }

            if ($milestoneId) recalculateMilestone($milestoneId);
            else recalculateProject($data['project_id']);
            
            return ['ok' => true];
        }
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Database Error: ' . $e->getMessage()];
    }
    return ['ok' => false, 'error' => 'Unknown Database Error'];
}

// دالة التعديل الكامل (للمدراء)
function updateTask($id, $data) {
    $db = Database::getInstance()->pdo();
    $oldData = $db->query("SELECT project_id, milestone_id, assigned_to FROM project_tasks WHERE id=$id")->fetch();
    $projId = $oldData['project_id'];
    $oldMsId = $oldData['milestone_id'];
    $oldAssignee = $oldData['assigned_to'];
    
    $newMilestoneId = !empty($data['milestone_id']) ? $data['milestone_id'] : null;

    if ($data['status_id'] == 3) $data['progress'] = 100;
    elseif ($data['status_id'] == 1) $data['progress'] = 0;

    $stmt = $db->prepare("
        UPDATE project_tasks SET 
            milestone_id = ?, title = ?, description = ?, assigned_to = ?, 
            start_date = ?, due_date = ?, status_id = ?, priority_id = ?, 
            weight = ?, cost_estimate = ?, cost_spent = ?, progress = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    try {
        if ($stmt->execute([
            $newMilestoneId, $data['title'], $data['description'], $data['assigned_to'],
            $data['start_date'], $data['due_date'], $data['status_id'], $data['priority_id'],
            $data['weight'], $data['cost_estimate'], $data['cost_spent'], $data['progress'], $id
        ])) {
            
            // [MODIFIED] إشعار عند تغيير المسؤول عن المهمة
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $oldAssignee) {
                if (function_exists('notifyTaskAssignment')) {
                    notifyTaskAssignment($id, $data['assigned_to'], $projId);
                }
            }

            if ($oldMsId && $oldMsId != $newMilestoneId) recalculateMilestone($oldMsId);
            if ($newMilestoneId) recalculateMilestone($newMilestoneId);
            recalculateProject($projId);
            return ['ok' => true];
        }
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Database Error: ' . $e->getMessage()];
    }
    return ['ok' => false, 'error' => 'Unknown Database Error'];
}

// ✅ دالة تحديث التقدم والحالة فقط (للموظفين)
function updateTaskProgressOnly($id, $statusId, $progress, $costSpent) {
    $db = Database::getInstance()->pdo();
    $task = $db->query("SELECT project_id, milestone_id FROM project_tasks WHERE id=$id")->fetch();
    
    if ($statusId == 3) $progress = 100;
    elseif ($statusId == 1) $progress = 0;

    $stmt = $db->prepare("
        UPDATE project_tasks SET 
            status_id = ?, progress = ?, cost_spent = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    try {
        if ($stmt->execute([$statusId, $progress, $costSpent, $id])) {
            if ($task['milestone_id']) recalculateMilestone($task['milestone_id']);
            recalculateProject($task['project_id']);
            return ['ok' => true];
        }
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
    return ['ok' => false, 'error' => 'Unknown error'];
}

// --------------------------------------------------------------------------
// دوال الحسابات
// --------------------------------------------------------------------------

function recalculateMilestone($milestone_id) {
    if (!$milestone_id) return;
    $db = Database::getInstance()->pdo();
    $tasks = $db->query("SELECT * FROM project_tasks WHERE milestone_id = $milestone_id AND is_deleted = 0")->fetchAll();
    
    if (empty($tasks)) {
        $db->prepare("UPDATE project_milestones SET progress = 0, cost_spent = 0 WHERE id = ?")->execute([$milestone_id]);
        $projId = $db->query("SELECT project_id FROM project_milestones WHERE id=$milestone_id")->fetchColumn();
        if ($projId) recalculateProject($projId);
        return;
    }

    $totalWeight = 0;
    $weightedSum = 0;
    $totalCostSpent = 0;
    $allCompleted = true;
    $anyStarted = false;

    foreach ($tasks as $t) {
        $w = $t['weight'] > 0 ? $t['weight'] : 1;
        $totalWeight += $w;
        $weightedSum += ($t['progress'] * $w);
        $totalCostSpent += $t['cost_spent'];
        if ($t['status_id'] != 3) $allCompleted = false;
        if ($t['status_id'] != 1) $anyStarted = true;
    }

    $milestoneProgress = ($totalWeight > 0) ? round($weightedSum / $totalWeight) : 0;
    
    $currStatus = $db->query("SELECT status_id FROM project_milestones WHERE id=$milestone_id")->fetchColumn();
    $newStatus = $currStatus;
    if ($currStatus != 5 && $currStatus != 4) { 
        if ($allCompleted && count($tasks) > 0) $newStatus = 3;
        elseif ($anyStarted || $milestoneProgress > 0) $newStatus = 2;
        else $newStatus = 1;
    }

    $db->prepare("UPDATE project_milestones SET progress = ?, cost_spent = ?, status_id = ? WHERE id = ?")
       ->execute([$milestoneProgress, $totalCostSpent, $newStatus, $milestone_id]);

    $projId = $db->query("SELECT project_id FROM project_milestones WHERE id=$milestone_id")->fetchColumn();
    if ($projId) recalculateProject($projId);
}

function recalculateProject($project_id) {
    if (!$project_id) return;
    $db = Database::getInstance()->pdo();

    // 1. حساب النسبة المئوية
    $sql = "SELECT progress, weight FROM project_tasks WHERE project_id = ? AND is_deleted = 0";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $projectProgress = 0;
    if (!empty($tasks)) {
        $totalWeight = 0;
        $weightedSum = 0;
        foreach ($tasks as $t) {
            $w = ($t['weight'] > 0) ? $t['weight'] : 1;
            $totalWeight += $w;
            $weightedSum += ($t['progress'] * $w);
        }
        if ($totalWeight > 0) {
            $projectProgress = round($weightedSum / $totalWeight);
        }
    }

    // 2. حساب الميزانية المصروفة
    $totalSpent = $db->query("SELECT SUM(cost_spent) FROM project_tasks WHERE project_id = $project_id AND is_deleted = 0")->fetchColumn();
    $totalSpent = $totalSpent ?: 0;

    // 3. تحديث الجدول
    $db->prepare("UPDATE operational_projects SET progress_percentage = ?, spent_budget = ? WHERE id = ?")
       ->execute([$projectProgress, $totalSpent, $project_id]);

    // 4. استدعاء التحديث التلقائي للحالة (مع الإشعارات)
    if (function_exists('getProjectById') && function_exists('autoUpdateProjectStatus')) {
        $freshProjectData = getProjectById($project_id);
        if ($freshProjectData) {
            autoUpdateProjectStatus($freshProjectData);
        }
    }
}

function updateMilestoneStatusAutomatic($milestone) {
    $db = Database::getInstance()->pdo();
    $today = date('Y-m-d');
    if ($milestone['status_id'] != 3 && $milestone['status_id'] != 5 && $milestone['due_date'] < $today) {
        if ($milestone['status_id'] != 4) {
            $db->prepare("UPDATE project_milestones SET status_id = 4 WHERE id = ?")->execute([$milestone['id']]);
        }
    }
}

// دالة مساعدة للتحقق من الصلاحيات (Fallback)
if (!function_exists('userCanInProject')) {
    function userCanInProject($projectId, $permissionKey) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            SELECT is_granted FROM project_user_permissions 
            WHERE project_id = ? AND user_id = ? 
            AND permission_id = (SELECT id FROM permissions WHERE permission_key = ?)
        ");
        $stmt->execute([$projectId, $_SESSION['user_id'], $permissionKey]);
        $res = $stmt->fetchColumn();
        return ($res === 1 || $res === '1');
    }
}
?>