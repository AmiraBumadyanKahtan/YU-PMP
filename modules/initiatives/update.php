<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions_progress.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db = new Database();

    $initId = !empty($_POST['initiative_id']) ? $_POST['initiative_id'] : null;
    $projId = !empty($_POST['project_id']) ? $_POST['project_id'] : null;

    $data = ['status' => $_POST['status']];

    if ($projId) {
        $db->update('projects', $data, 'id = :id', ['id' => $projId]);
        recalcProjectOrInitiative($db, "project", $projId);
        header("Location: ../project_detail.php?id=$projId&msg=updated");
    } 
    else {
        $db->update('initiatives', $data, 'id = :id', ['id' => $initId]);
        recalcProjectOrInitiative($db, "initiative", $initId);
        header("Location: ../initiative_detail.php?id=$initId&msg=updated");
    }
}
?>
