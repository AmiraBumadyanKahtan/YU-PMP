<?php

function getPendingProjectUpdates()
{
    return db()->query("
        SELECT pu.*, p.name AS project_name, u.full_name_en AS sender
        FROM project_updates pu
        JOIN operational_projects p ON p.id = pu.project_id
        JOIN users u ON u.id = pu.user_id
        WHERE pu.status = 'pending'
        ORDER BY pu.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

function getPendingProjectUpdatesForCEOCount()
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM project_updates 
        WHERE status = 'pending'
    ");

    $stmt->execute();
    return (int) $stmt->fetchColumn();
}
