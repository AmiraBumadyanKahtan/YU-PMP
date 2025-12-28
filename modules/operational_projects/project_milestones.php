<?php
// modules/operational_projects/project_milestones.php

// --------------------------------------------------------------------------
// دوال المساعدة والقراءة
// --------------------------------------------------------------------------

function getTaskById($id) {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM project_tasks WHERE id = $id")->fetch();
}

function getProjectMilestones($project_id) {
    $db = Database::getInstance()->pdo();
    // ترتيب حسب تاريخ البداية عشان التايم لاين يكون مرتب
    $stmt = $db->prepare("SELECT m.*, s.name as status_name FROM project_milestones m LEFT JOIN milestone_statuses s ON s.id = m.status_id WHERE m.project_id = ? AND m.is_deleted = 0 ORDER BY m.start_date ASC");
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
    $stmt = $db->prepare("SELECT t.*, u.full_name_en as assignee_name, s.name as status_name, p.label as priority_label FROM project_tasks t LEFT JOIN users u ON u.id = t.assigned_to LEFT JOIN task_statuses s ON s.id = t.status_id LEFT JOIN task_priorities p ON p.id = t.priority_id WHERE t.milestone_id = ? AND t.is_deleted = 0 ORDER BY t.due_date ASC");
    $stmt->execute([$milestone_id]);
    return $stmt->fetchAll();
}

// --------------------------------------------------------------------------
// دوال التحقق (Validation Helpers) - جديدة
// --------------------------------------------------------------------------

// التحقق من تداخل تواريخ الميل ستون
function checkMilestoneOverlap($projectId, $start, $due, $excludeId = null) {
    $db = Database::getInstance()->pdo();
    $sql = "SELECT COUNT(*) FROM project_milestones WHERE project_id = ? AND is_deleted = 0 AND ((start_date <= ? AND due_date >= ?) OR (start_date <= ? AND due_date >= ?) OR (start_date >= ? AND due_date <= ?))";
    $params = [$projectId, $due, $start, $due, $start, $start, $due];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

// التحقق أن التواريخ الفرعية تقع ضمن التواريخ الرئيسية
function isDateWithinRange($childStart, $childDue, $parentStart, $parentDue) {
    // إذا كانت التواريخ غير محددة نتجاوز التحقق
    if (!$childStart || !$childDue || !$parentStart || !$parentDue) return true;
    return ($childStart >= $parentStart && $childDue <= $parentDue);
}

// --------------------------------------------------------------------------
// دوال الكتابة والتعديل
// --------------------------------------------------------------------------

function createMilestone($data) {
    $db = Database::getInstance()->pdo();
    
    // 1. التحقق: الميل ستون داخل مدة المشروع
    $proj = $db->query("SELECT start_date, end_date FROM operational_projects WHERE id = {$data['project_id']}")->fetch();
    if ($proj) {
        if (!isDateWithinRange($data['start_date'], $data['due_date'], $proj['start_date'], $proj['end_date'])) {
            return ['ok' => false, 'error' => "Milestone dates must be within Project duration ({$proj['start_date']} to {$proj['end_date']})"];
        }
    }

    // 2. التحقق: عدم تداخل الميل ستون
    if (checkMilestoneOverlap($data['project_id'], $data['start_date'], $data['due_date'])) {
        return ['ok' => false, 'error' => "Milestone dates overlap with an existing milestone."];
    }

    $stmt = $db->prepare("INSERT INTO project_milestones (project_id, name, description, start_date, due_date, status_id, progress, cost_amount, created_at) VALUES (?, ?, ?, ?, ?, 1, 0, ?, NOW())");
    if ($stmt->execute([$data['project_id'], $data['name'], $data['description'], $data['start_date'], $data['due_date'], $data['cost_amount']])) { return ['ok' => true]; }
    return ['ok' => false, 'error' => 'Database error'];
}

function createTask($data) {
    $db = Database::getInstance()->pdo();
    $milestoneId = !empty($data['milestone_id']) ? $data['milestone_id'] : null;

    // 1. التحقق: المهمة داخل مدة الميل ستون
    if ($milestoneId) {
        $ms = $db->query("SELECT start_date, due_date, name FROM project_milestones WHERE id = $milestoneId")->fetch();
        if ($ms) {
            // نتحقق من تاريخ الاستحقاق (إلزامي) وتاريخ البدء (اختياري)
            $taskStart = $data['start_date'] ?? $ms['start_date']; // لو مافي بداية نعتبرها بداية المرحلة
            if (!isDateWithinRange($taskStart, $data['due_date'], $ms['start_date'], $ms['due_date'])) {
                return ['ok' => false, 'error' => "Task dates must be within Milestone '{$ms['name']}' duration ({$ms['start_date']} to {$ms['due_date']})"];
            }
        }
    } else {
        // إذا مهمة عامة، نتحقق أنها داخل مدة المشروع
        $proj = $db->query("SELECT start_date, end_date FROM operational_projects WHERE id = {$data['project_id']}")->fetch();
        if (!isDateWithinRange($data['start_date'] ?? $proj['start_date'], $data['due_date'], $proj['start_date'], $proj['end_date'])) {
             return ['ok' => false, 'error' => "General Task dates must be within Project duration."];
        }
    }

    $stmt = $db->prepare("INSERT INTO project_tasks (project_id, milestone_id, title, description, assigned_to, start_date, due_date, status_id, priority_id, weight, cost_estimate, cost_spent, progress, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, NOW())");
    try {
        if ($stmt->execute([$data['project_id'], $milestoneId, $data['title'], $data['description'], $data['assigned_to'], $data['start_date'], $data['due_date'], 1, $data['priority_id'], $data['weight'], $data['cost_estimate'] ?? 0])) {
            $taskId = $db->lastInsertId();
            if (!empty($data['assigned_to'])) {
                if (function_exists('notifyTaskAssignment')) { notifyTaskAssignment($taskId, $data['assigned_to'], $data['project_id']); }
                else { require_once __DIR__ . '/../../modules/todos/todo_functions.php'; addSystemTodo($data['assigned_to'], "New Task", "New task assigned.", "task_view", $taskId); }
            }
            if ($milestoneId) recalculateMilestone($milestoneId); else recalculateProject($data['project_id']);
            return ['ok' => true];
        }
    } catch (PDOException $e) { return ['ok' => false, 'error' => $e->getMessage()]; }
    return ['ok' => false, 'error' => 'Unknown Error'];
}

function updateTask($id, $data) {
    $db = Database::getInstance()->pdo();
    $oldData = $db->query("SELECT project_id, milestone_id, assigned_to FROM project_tasks WHERE id=$id")->fetch();
    $projId = $oldData['project_id']; $oldMsId = $oldData['milestone_id']; $oldAssignee = $oldData['assigned_to'];
    $newMilestoneId = !empty($data['milestone_id']) ? $data['milestone_id'] : null;
    
    // 1. التحقق: المهمة داخل مدة الميل ستون (عند التعديل)
    if ($newMilestoneId) {
        $ms = $db->query("SELECT start_date, due_date, name FROM project_milestones WHERE id = $newMilestoneId")->fetch();
        $taskStart = $data['start_date'] ?? $ms['start_date'];
        if (!isDateWithinRange($taskStart, $data['due_date'], $ms['start_date'], $ms['due_date'])) {
             return ['ok' => false, 'error' => "Task dates must be within Milestone '{$ms['name']}' duration."];
        }
    }

    if ($data['status_id'] == 3) $data['progress'] = 100; elseif ($data['status_id'] == 1) $data['progress'] = 0;
    
    $stmt = $db->prepare("UPDATE project_tasks SET milestone_id=?, title=?, description=?, assigned_to=?, start_date=?, due_date=?, status_id=?, priority_id=?, weight=?, cost_estimate=?, cost_spent=?, progress=?, updated_at=NOW() WHERE id=?");
    try {
        if ($stmt->execute([$newMilestoneId, $data['title'], $data['description'], $data['assigned_to'], $data['start_date'], $data['due_date'], $data['status_id'], $data['priority_id'], $data['weight'], $data['cost_estimate'], $data['cost_spent'], $data['progress'], $id])) {
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $oldAssignee) { if (function_exists('notifyTaskAssignment')) notifyTaskAssignment($id, $data['assigned_to'], $projId); }
            if ($oldMsId && $oldMsId != $newMilestoneId) recalculateMilestone($oldMsId);
            if ($newMilestoneId) recalculateMilestone($newMilestoneId);
            recalculateProject($projId);
            return ['ok' => true];
        }
    } catch (PDOException $e) { return ['ok' => false, 'error' => $e->getMessage()]; }
    return ['ok' => false, 'error' => 'Unknown Error'];
}

function updateTaskProgressOnly($id, $statusId, $progress, $costSpent) {
    $db = Database::getInstance()->pdo();
    $task = $db->query("SELECT project_id, milestone_id FROM project_tasks WHERE id=$id")->fetch();
    if ($statusId == 3) $progress = 100; elseif ($statusId == 1) $progress = 0;
    $stmt = $db->prepare("UPDATE project_tasks SET status_id=?, progress=?, cost_spent=?, updated_at=NOW() WHERE id=?");
    try {
        if ($stmt->execute([$statusId, $progress, $costSpent, $id])) {
            if ($task['milestone_id']) recalculateMilestone($task['milestone_id']);
            recalculateProject($task['project_id']);
            return ['ok' => true];
        }
    } catch (PDOException $e) { return ['ok' => false, 'error' => $e->getMessage()]; }
    return ['ok' => false, 'error' => 'Unknown error'];
}

// --------------------------------------------------------------------------
// دوال الحسابات (كما هي)
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
    $totalWeight = 0; $weightedSum = 0; $totalCostSpent = 0; $allCompleted = true; $anyStarted = false;
    foreach ($tasks as $t) {
        $w = $t['weight'] > 0 ? $t['weight'] : 1;
        $totalWeight += $w; $weightedSum += ($t['progress'] * $w); $totalCostSpent += $t['cost_spent'];
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
    $db->prepare("UPDATE project_milestones SET progress = ?, cost_spent = ?, status_id = ? WHERE id = ?")->execute([$milestoneProgress, $totalCostSpent, $newStatus, $milestone_id]);
    $projId = $db->query("SELECT project_id FROM project_milestones WHERE id=$milestone_id")->fetchColumn();
    if ($projId) recalculateProject($projId);
}

function recalculateProject($project_id) {
    if (!$project_id) return;
    $db = Database::getInstance()->pdo();
    $hasMilestones = $db->query("SELECT COUNT(*) FROM project_milestones WHERE project_id = $project_id AND is_deleted = 0")->fetchColumn() > 0;
    $sql = "SELECT progress, weight FROM project_tasks WHERE project_id = ? AND is_deleted = 0";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projectProgress = 0;
    if (!empty($tasks)) {
        $totalWeight = 0; $weightedSum = 0;
        foreach ($tasks as $t) {
            $w = ($t['weight'] > 0) ? $t['weight'] : 1;
            $totalWeight += $w; $weightedSum += ($t['progress'] * $w);
        }
        if ($totalWeight > 0) { $projectProgress = round($weightedSum / $totalWeight); }
    }
    if (!$hasMilestones && $projectProgress == 100) { $projectProgress = 99; }
    $totalSpent = $db->query("SELECT SUM(cost_spent) FROM project_tasks WHERE project_id = $project_id AND is_deleted = 0")->fetchColumn() ?: 0;
    $db->prepare("UPDATE operational_projects SET progress_percentage = ?, spent_budget = ? WHERE id = ?")->execute([$projectProgress, $totalSpent, $project_id]);
    if (function_exists('getProjectById') && function_exists('autoUpdateProjectStatus')) {
        $freshProjectData = getProjectById($project_id);
        if ($freshProjectData) { autoUpdateProjectStatus($freshProjectData); }
    }
}

function updateMilestoneStatusAutomatic($milestone) {
    $db = Database::getInstance()->pdo();
    $today = date('Y-m-d');
    if ($milestone['status_id'] != 3 && $milestone['status_id'] != 5 && $milestone['due_date'] < $today) {
        if ($milestone['status_id'] != 4) { $db->prepare("UPDATE project_milestones SET status_id = 4 WHERE id = ?")->execute([$milestone['id']]); }
    }
}
?>