<?php
require_once "../../core/init.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

$id = (int)($_GET['id'] ?? 0);
$userId = Auth::id();

// 1. تأكد أن المبادرة موجودة
$stmt = $db->prepare("SELECT * FROM initiatives WHERE id = ?");
$stmt->execute([$id]);
$initiative = $stmt->fetch();

if (!$initiative) die("Initiative not found");

// 2. تأكد أنه لا يوجد approval سابق نشط
$check = $db->prepare("
    SELECT id FROM approval_instances 
    WHERE entity_type = 'initiative' 
    AND entity_id = ? 
    AND status = 'in_progress'
");
$check->execute([$id]);

if ($check->fetch()) {
    header("Location: view.php?id=$id&already_sent=1");
    exit;
}

// 3. جلب أول مرحلة (Strategy Office)
$stageStmt = $db->prepare("
    SELECT * FROM approval_flow_stages 
    WHERE entity_type = 'initiative' 
    ORDER BY stage_order ASC 
    LIMIT 1
");
$stageStmt->execute();
$firstStage = $stageStmt->fetch();

// 4. إنشاء approval_instance
$insert = $db->prepare("
    INSERT INTO approval_instances 
    (entity_type, entity_id, current_stage_id, status, created_by)
    VALUES ('initiative', ?, ?, 'in_progress', ?)
");
$insert->execute([$id, $firstStage['id'], $userId]);

$instanceId = $db->lastInsertId();

// 5. تغيير حالة المبادرة إلى Strategy Review
$db->prepare("UPDATE initiatives SET status_id = 2 WHERE id = ?")->execute([$id]);

// 6. إرسال إشعار لمكتب الاستراتيجية
$notify = $db->prepare("
    INSERT INTO notifications (user_id, title, message, type, related_entity_type, related_entity_id)
    SELECT 
        u.id,
        'Initiative requires review',
        CONCAT('Initiative: ', ?),
        'approval',
        'initiative',
        ?
    FROM users u
    JOIN system_roles r ON r.id = u.role_id
    WHERE r.role_key = 'strategy_office'
");

$notify->execute([$initiative['name'], $id]);

header("Location: view.php?id=$id&sent=1");
exit;
