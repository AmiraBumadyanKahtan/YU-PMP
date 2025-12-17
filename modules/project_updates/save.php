<?php
require_once "../../core/init.php";
require_once "functions.php";
require_once "../../core/functions.php";

if (!Auth::can('send_progress_update')) {
    die("Access denied");
}

$projectId = (int)($_POST['project_id'] ?? 0);
$percent   = (int)($_POST['progress_percent'] ?? 0);
$desc      = trim($_POST['description'] ?? '');
$userId    = $_SESSION['user_id'];

if (!$projectId || !$desc) {
    die("Invalid data");
}

$stmt = db()->prepare("
    INSERT INTO project_updates 
    (project_id, user_id, progress_percent, description, status)
    VALUES (?, ?, ?, ?, 'pending')
");
$stmt->execute([$projectId, $userId, $percent, $desc]);

$updateId = db()->lastInsertId();

// ✅ إضافة To-Do للـ CEO
db()->prepare("
    INSERT INTO user_todos 
    (user_id, title, description, related_entity_type, related_entity_id)
    VALUES (
        (SELECT id FROM users WHERE role_key = 'ceo' LIMIT 1),
        'New Project Update',
        'A new project update requires your approval',
        'project_update',
        ?
    )
")->execute([$updateId]);

// ✅ تسجيل النشاط
log_activity($userId, 'create', 'project_update', $updateId, null, 'Progress Update Submitted');

header("Location: create.php?success=1");
exit;
