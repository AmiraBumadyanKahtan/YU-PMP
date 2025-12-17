<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions_progress.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db = new Database();

    $initId = !empty($_POST['initiative_id']) ? $_POST['initiative_id'] : null;
    $projId = !empty($_POST['project_id']) ? $_POST['project_id'] : null;

    $data = [
        'initiative_id' => $initId,
        'project_id' => $projId,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'milestone_id' => !empty($_POST['milestone_id']) ? $_POST['milestone_id'] : null,
        'assigned_to' => !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null,
        'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'due_date' => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
        'priority' => $_POST['priority'],
        'cost' => !empty($_POST['cost']) ? $_POST['cost'] : 0.00,
        'weight' => !empty($_POST['weight']) ? $_POST['weight'] : 1,
        'status' => 'todo',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $db->insert('project_tasks', $data);

    // 1) Auto update tasks based on date
    autoUpdateTaskStatus($db);

    // 2) Recalculate milestone
    if (!empty($_POST['milestone_id'])) {
        recalcMilestone($db, $_POST['milestone_id']);
    }

    // 3) Recalculate project or initiative
    if ($projId) {
        recalcProjectOrInitiative($db, "project", $projId);
        header("Location: ../project_detail.php?id=$projId&msg=task_added");
    } else {
        recalcProjectOrInitiative($db, "initiative", $initId);
        header("Location: ../initiative_detail.php?id=$initId&msg=task_added");
    }
}
?>
