<?php
// modules/workflows/workflow_functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب جميع المسارات
 */
function getAllWorkflows() {
    $db = Database::getInstance()->pdo();
    return $db->query("
        SELECT w.*, e.entity_name 
        FROM approval_workflows w
        JOIN approval_entity_types e ON e.id = w.entity_type_id
        ORDER BY w.entity_type_id, w.id
    ")->fetchAll();
}

/**
 * جلب أنواع الكيانات (ركيزة، مشروع، مبادرة...)
 */
function getEntityTypes() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM approval_entity_types ORDER BY entity_name")->fetchAll();
}

/**
 * جلب مسار واحد مع مراحله
 */
function getWorkflowFull($id) {
    $db = Database::getInstance()->pdo();
    
    // جلب الرأس
    $workflow = $db->prepare("SELECT * FROM approval_workflows WHERE id = ?");
    $workflow->execute([$id]);
    $w = $workflow->fetch();
    
    if (!$w) return null;

    // جلب المراحل مرتبة
    $stages = $db->prepare("
        SELECT s.*, r.role_name 
        FROM approval_workflow_stages s
        LEFT JOIN roles r ON r.id = s.stage_role_id
        WHERE s.workflow_id = ?
        ORDER BY s.stage_order ASC
    ");
    $stages->execute([$id]);
    $w['stages'] = $stages->fetchAll();

    return $w;
}

/**
 * إنشاء مسار جديد
 */
function createWorkflow($name, $entity_type_id, $is_active) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("INSERT INTO approval_workflows (workflow_name, entity_type_id, is_active) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $entity_type_id, $is_active])) {
        return $db->lastInsertId();
    }
    return false;
}

/**
 * تحديث مسار ومراحله
 */
function updateWorkflow($id, $name, $is_active, $stagesData) {
    $db = Database::getInstance()->pdo();
    
    try {
        $db->beginTransaction();

        // 1. تحديث البيانات الأساسية
        $stmt = $db->prepare("UPDATE approval_workflows SET workflow_name = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$name, $is_active, $id]);

        // 2. تحديث المراحل (الاستراتيجية الأسهل: حذف الكل وإعادة الإدخال)
        $del = $db->prepare("DELETE FROM approval_workflow_stages WHERE workflow_id = ?");
        $del->execute([$id]);

        // 3. إدخال المراحل الجديدة
        if (!empty($stagesData)) {
            $ins = $db->prepare("
                INSERT INTO approval_workflow_stages 
                (workflow_id, stage_order, stage_name, assignee_type, stage_role_id, is_final)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $order = 1;
            foreach ($stagesData as $st) {
                // التأكد من نوع المعين
                $assigneeType = $st['assignee_type']; 
                $roleId = ($assigneeType == 'system_role') ? $st['role_id'] : null;
                $isFinal = isset($st['is_final']) ? 1 : 0;

                $ins->execute([
                    $id,
                    $order,
                    $st['stage_name'],
                    $assigneeType,
                    $roleId,
                    $isFinal
                ]);
                $order++;
            }
        }

        $db->commit();
        return true;

    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * حذف مسار
 */
function deleteWorkflow($id) {
    $db = Database::getInstance()->pdo();
    // يجب التحقق مما إذا كان هناك عمليات موافقة جارية مرتبطة بهذا المسار قبل الحذف (اختياري)
    $stmt = $db->prepare("DELETE FROM approval_workflows WHERE id = ?");
    return $stmt->execute([$id]);
}
?>