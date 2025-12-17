<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

header('Content-Type: application/json');

if (isset($_GET['code'])) {
    $db = new Database();
    $code = trim($_GET['code']);
    
    // نفحص في جدول المبادرات
    $exists = $db->fetchOne("SELECT id FROM initiatives WHERE initiative_number = ?", [$code]);
    
    echo json_encode(['exists' => (bool)$exists]);
}
?>