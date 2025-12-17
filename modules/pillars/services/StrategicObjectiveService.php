<?php
// modules/pillars/services/StrategicObjectiveService.php

require_once __DIR__ . '/../../../core/Database.php';

class StrategicObjectiveService {
    
    // ✅ إضافة هدف (توليد الكود تلقائياً)
    public static function add($pillar_id, $text) {
        $db = Database::getInstance()->pdo();
        
        // 1. جلب رقم الركيزة
        $stmt = $db->prepare("SELECT pillar_number FROM pillars WHERE id = ?");
        $stmt->execute([$pillar_id]);
        $pillarNum = $stmt->fetchColumn();

        // 2. حساب التسلسل
        $stmt = $db->prepare("SELECT COUNT(*) FROM strategic_objectives WHERE pillar_id = ?");
        $stmt->execute([$pillar_id]);
        $count = $stmt->fetchColumn();
        $nextIndex = $count + 1;

        // 3. تكوين الكود
        $code = "OBJ-" . $pillarNum . "." . $nextIndex;

        // 4. الحفظ
        return $db->prepare("INSERT INTO strategic_objectives (pillar_id, objective_code, objective_text, created_at) VALUES (?, ?, ?, NOW())")
                  ->execute([$pillar_id, $code, $text]);
    }

    public static function delete($obj_id) {
        $db = Database::getInstance()->pdo();
        return $db->prepare("UPDATE strategic_objectives SET is_deleted=1 WHERE id=?")->execute([$obj_id]);
    }

    public static function getAllByPillar($pillar_id) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("SELECT * FROM strategic_objectives WHERE pillar_id = ? AND is_deleted = 0 ORDER BY objective_code");
        $stmt->execute([$pillar_id]);
        return $stmt->fetchAll();
    }
}
?>