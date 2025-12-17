<?php

/**
 * ============================================================
 *  UNIVERSAL APPROVAL WORKFLOW SYSTEM
 *  Supports:
 *  - Multi-stage approval
 *  - Custom reviewers per stage
 *  - Logs
 *  - Checking access
 *  - Easy integration with any entity (pillar, initiative, etc.)
 * ============================================================
 */


// ------------------------------------------------------------
// 1) Get Flow definition (stages) for an entity
// ------------------------------------------------------------
function getFlowStages($db, string $entityType)
{
    $sql = "
        SELECT * 
        FROM approval_flow_stages 
        WHERE entity_type = ?
        ORDER BY stage_order ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$entityType]);
    return $stmt->fetchAll();
}



// ------------------------------------------------------------
// 2) Get Next Stage
// ------------------------------------------------------------
function getNextStage(array $stages, $currentStageId)
{
    if ($currentStageId === null) {
        return $stages[0]; 
    }

    foreach ($stages as $index => $stage) {
        if ($stage['id'] == $currentStageId) {
            return $stages[$index + 1] ?? null;
        }
    }

    return null;
}


// ------------------------------------------------------------
// 3) Create approval instance
// ------------------------------------------------------------
function createApprovalInstance($db, string $entityType, int $entityId, int $userId)
{
    $sql = "
        INSERT INTO approval_instances (entity_type, entity_id, created_by)
        VALUES (?, ?, ?)
    ";
    $db->query($sql, [$entityType, $entityId, $userId]);

    return $db->lastInsertId();
}


// ------------------------------------------------------------
// 4) Update approval instance stage
// ------------------------------------------------------------
function updateApprovalInstanceStage($db, int $instanceId, int $stageId)
{
    $sql = "UPDATE approval_instances SET current_stage_id = ? WHERE id = ?";
    $db->query($sql, [$stageId, $instanceId]);
}


// ------------------------------------------------------------
// 5) Mark workflow as fully approved
// ------------------------------------------------------------
function completeApprovalInstance($db, int $instanceId)
{
    $sql = "UPDATE approval_instances SET status = 'approved' WHERE id = ?";
    $db->query($sql, [$instanceId]);
}


// ------------------------------------------------------------
// 6) Reject workflow
// ------------------------------------------------------------
function rejectApprovalInstance($db, int $instanceId)
{
    $sql = "UPDATE approval_instances SET status = 'rejected' WHERE id = ?";
    $db->query($sql, [$instanceId]);
}


// ------------------------------------------------------------
// 7) Return workflow to start
// ------------------------------------------------------------
function returnApprovalInstance($db, int $instanceId)
{
    $sql = "
        UPDATE approval_instances 
        SET status = 'returned', current_stage_id = NULL 
        WHERE id = ?
    ";
    $db->query($sql, [$instanceId]);
}


// ------------------------------------------------------------
// 8) Log approval action
// ------------------------------------------------------------
function logApprovalAction($db, int $instanceId, $stageId, int $reviewerId, string $decision, $comments)
{
    $sql = "
        INSERT INTO approval_actions (instance_id, stage_id, reviewer_id, decision, comments) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $db->query($sql, [$instanceId, $stageId, $reviewerId, $decision, $comments]);
}



// ****************************************************************
//  ADDITIONAL REQUIRED FUNCTIONS (for the UI – details.php)
// ****************************************************************


// ------------------------------------------------------------
// 9) Get approval instance (if exists)
// ------------------------------------------------------------
function getApprovalInstance($db, string $entityType, int $entityId)
{
    $sql = "
        SELECT * 
        FROM approval_instances
        WHERE entity_type = ?
          AND entity_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$entityType, $entityId]);
    return $stmt->fetch();

}


// ------------------------------------------------------------
// 10) Get pending decision assigned to a specific reviewer
// ------------------------------------------------------------
function getPendingApprovalForUser($db, int $instanceId, int $userId)
{
    $sql = "
        SELECT a.*, s.reviewer_role
        FROM approval_flow_stages s
        LEFT JOIN approval_pending p ON p.stage_id = s.id
        LEFT JOIN approvals a ON a.id = p.approval_id
        WHERE p.instance_id = ?
          AND a.assigned_to = ?
          AND a.status = 'pending'
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$instanceId, $userId]);
    return $stmt->fetch();

}


// ------------------------------------------------------------
// 11) Check if user can SEND FOR APPROVAL
// ------------------------------------------------------------
function canUserSendForApproval(
    string $roleKey,
    int $objectiveCount,
    $currentStatus,
    $hasActiveWorkflow
) {
    if (!in_array($roleKey, ['super_admin', 'strategy_office', 'strategy_staff'])) {
        return false;
    }

    if ($objectiveCount == 0) return false;

    if ($currentStatus !== 'Draft') return false;

    if ($hasActiveWorkflow) return false;

    return true;
}


// ------------------------------------------------------------
// 12) Check if user can approve the current stage
// ------------------------------------------------------------
function canUserApproveStage($db, int $stageId, string $roleKey)
{
    $sql = "SELECT reviewer_role FROM approval_flow_stages WHERE id = ?";
    $stage = $db->fetch($sql, [$stageId]);

    if (!$stage) return false;

    $stmt = $db->prepare($sql);
    $stmt->execute([$stageId]);
    $stage = $stmt->fetch();
    return $stage && $stage['reviewer_role'] === $roleKey;

}


// ------------------------------------------------------------
// 13) Get workflow timeline (history)
// ------------------------------------------------------------
function getApprovalTimeline($db, int $instanceId)
{
    $sql = "
        SELECT a.*, u.full_name_en AS reviewer_name, s.stage_name
        FROM approval_actions a
        LEFT JOIN users u ON u.id = a.reviewer_id
        LEFT JOIN approval_flow_stages s ON s.id = a.stage_id
        WHERE a.instance_id = ?
        ORDER BY a.id ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$instanceId]);
    return $stmt->fetchAll();

}



