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
        'name_en' => $_POST['name'],
        'name_ar' => $_POST['name'],
        'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'due_date' => $_POST['due_date'],
        'cost' => !empty($_POST['cost']) ? $_POST['cost'] : 0.00,
        'status' => 'upcoming',
        'progress_percentage' => 0,
        'spent_cost' => 0
    ];

    $db->insert('milestones', $data);

    // تعيد حساب المشروع/المبادرة بسبب إضافة ميلستون جديد
    if ($projId) {
        recalcProjectOrInitiative($db, "project", $projId);
        header("Location: ../project_detail.php?id=$projId&msg=milestone_added");
    } else {
        recalcProjectOrInitiative($db, "initiative", $initId);
        header("Location: ../initiative_detail.php?id=$initId&msg=milestone_added");
    }
}
?>
