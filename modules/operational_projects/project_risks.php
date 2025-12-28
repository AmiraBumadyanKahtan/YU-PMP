<?php
// modules/operational_projects/project_risks.php

// =========================================================
// 5. RISK MANAGEMENT
// =========================================================

/**
 * جلب سجل المخاطر للمشروع
 */
function getProjectRisks($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT r.*, s.status_name 
        FROM risk_assessments r
        LEFT JOIN risk_statuses s ON s.id = r.status_id
        WHERE r.parent_type = 'project' AND r.parent_id = ?
        ORDER BY r.risk_score DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * إضافة خطر جديد
 */
function createRisk($data) {
    $db = Database::getInstance()->pdo();
    
    // ملاحظة: risk_score يحسب تلقائياً في قاعدة البيانات (Generated Column)
    $stmt = $db->prepare("
        INSERT INTO risk_assessments 
        (parent_type, parent_id, title, description, mitigation_plan, probability, impact, status_id, identified_date, created_at)
        VALUES 
        ('project', ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ");
    // status_id = 1 (Identified)

    return $stmt->execute([
        $data['project_id'], $data['title'], $data['description'], 
        $data['mitigation_plan'], $data['probability'], $data['impact']
    ]);
}

/**
 * تحديث خطر
 */
function updateRisk($risk_id, $data) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        UPDATE risk_assessments 
        SET title = ?, description = ?, mitigation_plan = ?, 
            probability = ?, impact = ?, status_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['title'], $data['description'], $data['mitigation_plan'],
        $data['probability'], $data['impact'], $data['status_id'], $risk_id
    ]);
}

/**
 * حذف خطر
 */
function deleteRisk($risk_id) {
    $db = Database::getInstance()->pdo();
    return $db->prepare("DELETE FROM risk_assessments WHERE id = ?")->execute([$risk_id]);
}

function getRiskStatuses() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM risk_statuses WHERE is_active=1")->fetchAll();
}
?>