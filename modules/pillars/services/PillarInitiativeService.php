<?php
// modules/pillars/services/PillarInitiativeService.php

require_once __DIR__ . '/../../../core/Database.php';

class PillarInitiativeService {
    public static function getAll($pillar_id) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            SELECT i.*, u.full_name_en as owner_name, s.name as status_name, s.color as status_color
            FROM initiatives i
            LEFT JOIN users u ON u.id = i.owner_user_id
            LEFT JOIN initiative_statuses s ON s.id = i.status_id
            WHERE i.pillar_id = ? AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
            ORDER BY i.start_date DESC
        ");
        $stmt->execute([$pillar_id]);
        return $stmt->fetchAll();
    }
}
?>