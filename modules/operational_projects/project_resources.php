<?php
// modules/operational_projects/project_resources.php

// =========================================================
// 8. RESOURCES MANAGEMENT
// =========================================================

/**
 * جلب جميع موارد المشروع
 */
function getProjectResources($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT wr.*, rt.type_name, rt.category, u.full_name_en as assigned_user_name
        FROM work_resources wr
        JOIN resource_types rt ON rt.id = wr.resource_type_id
        LEFT JOIN users u ON u.id = wr.assigned_to
        WHERE wr.parent_type = 'project' AND wr.parent_id = ?
        ORDER BY wr.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * إضافة مورد جديد
 */
function addProjectResource($data) {
    $db = Database::getInstance()->pdo();
    
    // total_cost is generated automatically in DB
    $stmt = $db->prepare("
        INSERT INTO work_resources 
        (parent_type, parent_id, resource_type_id, name, qty, cost_per_unit, assigned_to, notes, created_at)
        VALUES 
        ('project', ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([
        $data['project_id'],
        $data['resource_type_id'],
        $data['name'],
        $data['qty'],
        $data['cost_per_unit'],
        $data['assigned_to'] ?: null, // If empty string, set to NULL
        $data['notes']
    ])) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Database error while adding resource'];
}

/**
 * حذف مورد
 */
function deleteProjectResource($resource_id) {
    $db = Database::getInstance()->pdo();
    // Hard delete since resources table doesn't have is_deleted (based on your schema)
    // If you prefer soft delete, alter table first. For now, hard delete:
    return $db->prepare("DELETE FROM work_resources WHERE id = ?")->execute([$resource_id]);
}

/**
 * جلب أنواع الموارد (للقائمة المنسدلة)
 */
function getResourceTypes() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM resource_types WHERE is_active = 1 ORDER BY type_name ASC")->fetchAll();
}

/**
 * حساب إجمالي تكلفة الموارد للمشروع
 */
function getProjectResourcesTotalCost($project_id) {
    $db = Database::getInstance()->pdo();
    // total_cost is a generated column
    $stmt = $db->prepare("SELECT SUM(total_cost) FROM work_resources WHERE parent_type = 'project' AND parent_id = ?");
    $stmt->execute([$project_id]);
    return $stmt->fetchColumn() ?: 0;
}
?>