<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

if(isset($_GET['pillar_id'])) {
    $db = new Database();
    $pillarId = (int)$_GET['pillar_id'];
    
    $objectives = $db->fetchAll(
        "SELECT id, name_en, name_ar FROM strategic_objectives WHERE pillar_id = ?", 
        [$pillarId]
    );
    
    echo json_encode($objectives);
} else {
    echo json_encode([]);
}
?>