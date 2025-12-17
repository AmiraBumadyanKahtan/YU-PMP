<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $db = new Database();
    
    $initId = !empty($_POST['initiative_id']) ? $_POST['initiative_id'] : null;
    $projId = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
    
    // استقبال Task ID
    $taskId = !empty($_POST['task_id']) ? $_POST['task_id'] : null;

    $file = $_FILES['file'];
    $fileName = time() . '_' . basename($file['name']);
    $targetDir = "../assets/uploads/";
    
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    $targetFilePath = $targetDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        $data = [
            'initiative_id' => $initId,
            'project_id' => $projId,
            'task_id' => $taskId, // هذا السطر المهم
            'title_en' => $_POST['title'] ?? $file['name'],
            'title_ar' => $_POST['title'] ?? $file['name'],
            'file_name' => $fileName,
            'file_path' => 'assets/uploads/' . $fileName,
            'file_size' => $file['size'],
            'file_type' => $file['type'],
            'uploaded_by' => $_SESSION['user_id']
        ];
        
        $db->insert('documents', $data);
        
        if ($projId) {
            header("Location: ../project_detail.php?id=" . $projId . "&msg=uploaded");
        } else {
            header("Location: ../initiative_detail.php?id=" . $initId . "&msg=uploaded");
        }
    }
}
?>