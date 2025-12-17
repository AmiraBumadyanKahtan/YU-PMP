<?php
// modules/pillars/services/PillarTeamService.php

require_once __DIR__ . '/../../../core/Database.php';

class PillarTeamService {

    public static function addMember($pillar_id, $user_id, $role_id) {
        $db = Database::getInstance()->pdo();
        // Check duplicate
        $check = $db->prepare("SELECT id FROM pillar_team WHERE pillar_id=? AND user_id=?");
        $check->execute([$pillar_id, $user_id]);
        if ($check->fetch()) return false;

        return $db->prepare("INSERT INTO pillar_team (pillar_id, user_id, role_id) VALUES (?, ?, ?)")
                  ->execute([$pillar_id, $user_id, $role_id]);
    }

    public static function removeMember($id) {
        $db = Database::getInstance()->pdo();
        return $db->prepare("DELETE FROM pillar_team WHERE id=?")->execute([$id]);
    }

    public static function getTeam($pillar_id) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            SELECT pt.*, u.full_name_en, u.avatar, pr.name as role_name
            FROM pillar_team pt 
            JOIN users u ON u.id = pt.user_id 
            LEFT JOIN pillar_roles pr ON pr.id = pt.role_id
            WHERE pt.pillar_id = ?
            ORDER BY pr.id ASC
        ");
        $stmt->execute([$pillar_id]);
        return $stmt->fetchAll();
    }

    public static function getRoles() {
        $db = Database::getInstance()->pdo();
        return $db->query("SELECT * FROM pillar_roles ORDER BY id ASC")->fetchAll();
    }
}
?>