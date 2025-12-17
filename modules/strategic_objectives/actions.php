<?php

function logObjectiveAction($objective_id, $action, $oldValue = null, $newValue = null)
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        INSERT INTO activity_log 
        (user_id, action, entity_type, entity_id, old_value, new_value, ip_address)
        VALUES (?, ?, 'strategic_objective', ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $objective_id,
        $oldValue ? json_encode($oldValue, JSON_UNESCAPED_UNICODE) : null,
        $newValue ? json_encode($newValue, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
}
