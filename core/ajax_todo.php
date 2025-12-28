<?php
// ajax_todo.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/auth.php';

header('Content-Type: application/json');

if (!Auth::check() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$todoId = $data['id'] ?? null;
$userId = $_SESSION['user_id'];

if ($todoId) {
    $db = Database::getInstance()->pdo();
    // التأكد من أن المهمة تخص المستخدم الحالي قبل التحديث
    $stmt = $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$todoId, $userId]);
    
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false]);
}
?>