<?php
// modules/operational_projects/php/doc_BE.php

// =========================================================
// DOCUMENTS MANAGEMENT BACKEND
// =========================================================

/**
 * دالة لجلب جميع المستندات المرتبطة بالمشروع.
 */
function getProjectDocuments($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT d.*, u.full_name_en as uploader_name 
        FROM documents d
        JOIN users u ON u.id = d.uploaded_by
        WHERE d.is_deleted = 0 
        AND (
            (d.parent_type = 'project' AND d.parent_id = ?) OR
            (d.parent_type = 'milestone' AND d.parent_id IN (SELECT id FROM project_milestones WHERE project_id = ?)) OR
            (d.parent_type = 'task' AND d.parent_id IN (SELECT id FROM project_tasks WHERE project_id = ?)) OR
            (d.parent_type = 'risk' AND d.parent_id IN (SELECT id FROM risk_assessments WHERE parent_type='project' AND parent_id = ?))
        )
        ORDER BY d.uploaded_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id, $project_id, $project_id, $project_id]);
    return $stmt->fetchAll();
}


/**
 * دالة لحذف مستند (حذف ناعم - Soft Delete).
 */
function deleteDocument($doc_id) {
   $db = Database::getInstance()->pdo();
   return $db->prepare("UPDATE documents SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$doc_id]);
}


/**
 * دالة لجلب جميع مهام المشروع (للقائمة المنسدلة).
 */
function getAllProjectTasks($project_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT id, title FROM project_tasks WHERE project_id = ? AND is_deleted = 0 ORDER BY title ASC");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * دالة لجلب المهام الخاصة بمستخدم معين.
 */
function getUserProjectTasks($project_id, $user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT id, title FROM project_tasks WHERE project_id = ? AND assigned_to = ? AND is_deleted = 0 ORDER BY title ASC");
    $stmt->execute([$project_id, $user_id]);
    return $stmt->fetchAll();
}
?>