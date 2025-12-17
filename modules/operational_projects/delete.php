<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

session_start(); // تأكد من بدء الجلسة للتحقق من الدور

// SECURITY CHECK: SUPER ADMIN ONLY
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    die("Unauthorized Access: Only Super Admin can delete records.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $id = (int)$_POST['id'];
    $type = $_POST['type']; // strategic or operational
    
    // Determine Table and Key
    $mainTable = ($type === 'strategic') ? 'initiatives' : 'projects';
    
    // Delete Main Record 
    // (Foreign keys should cascade delete resources, tasks, etc. if configured in DB,
    // but for safety we can rely on DB constraints or delete manually if needed)
    
    // Note: If your DB has ON DELETE CASCADE, deleting the main record is enough.
    // If not, you should delete from child tables first. 
    // Assuming we set up Foreign Keys properly or want to be safe:
    
    $key = ($type === 'strategic') ? 'initiative_id' : 'project_id';
    
    $db->delete('project_resources', "$key = :id", ['id' => $id]);
    $db->delete('team_assignments', "$key = :id", ['id' => $id]);
    $db->delete('project_collaborations', "$key = :id", ['id' => $id]);
    $db->delete('project_tasks', "$key = :id", ['id' => $id]);
    $db->delete('milestones', "$key = :id", ['id' => $id]);
    $db->delete('risk_assessments', "$key = :id", ['id' => $id]);
    
    // Finally delete the project itself
    $db->delete($mainTable, 'id = :id', ['id' => $id]);
    
    header("Location: ../index.php?msg=deleted");
}
?>