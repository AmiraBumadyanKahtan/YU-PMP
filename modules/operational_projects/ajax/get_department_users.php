<?php
require_once "../../../core/init.php";

$deptId = (int)($_GET['department_id'] ?? 0);
if (!$deptId) {
    echo json_encode([]);
    exit;
}

$stmt = db()->prepare("
    SELECT id, full_name_en AS name 
    FROM users 
    WHERE department_id = ? 
      AND is_active = 1 
      AND is_deleted = 0
    ORDER BY full_name_en
");
$stmt->execute([$deptId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
