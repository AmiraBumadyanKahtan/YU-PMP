<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    $riskId = $_POST['risk_id'];
    $status = $_POST['status'];
    
    // تحديث الحالة في قاعدة البيانات
    $update = $db->update('risk_assessments', ['status' => $status], 'id = :id', ['id' => $riskId]);
    
    if ($update) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
}
?>