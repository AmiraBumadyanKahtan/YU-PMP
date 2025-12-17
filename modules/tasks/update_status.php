<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions_progress.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id']) && isset($_POST['status'])) {

    $db = new Database();
    $taskId = $_POST['task_id'];
    $status = $_POST['status'];

    // 1) Update task status
    $db->update('project_tasks', ['status' => $status], 'id = :id', ['id' => $taskId]);

    // 2) Fetch task info (milestone + project/initiative)
    $task = $db->fetchOne("
        SELECT milestone_id, project_id, initiative_id 
        FROM project_tasks 
        WHERE id = ?
    ", [$taskId]);

    if ($task) {
        $mid = $task['milestone_id'];

        if (!empty($mid)) {
            // 3) Recalculate milestone (progress + cost + status)
            recalcMilestone($db, $mid);
        }

        // 4) Recalculate project or initiative
        if (!empty($task['project_id'])) {
            recalcProjectOrInitiative($db, "project", $task['project_id']);
        }

        if (!empty($task['initiative_id'])) {
            recalcProjectOrInitiative($db, "initiative", $task['initiative_id']);
        }
    }

    // 5) Auto update all tasks status based on dates
    autoUpdateTaskStatus($db);

    echo json_encode(['success' => true]);
}
?>
