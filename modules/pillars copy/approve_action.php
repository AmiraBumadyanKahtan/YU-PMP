<?php
require_once '../../includes/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/session.php';
require_once '../../functions/approval_workflow.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

$db = new Database();

$instanceId = $_POST['instance_id'] ?? null;
$decision   = $_POST['decision'] ?? null;     // approved | rejected | returned
$comments   = $_POST['comments'] ?? '';
$userId     = $_SESSION['user_id'];

if (!$instanceId || !$decision) {
    $_SESSION['toast_error'] = "Invalid approval submission.";
    header("Location: ../pillars/list.php");
    exit;
}

/* ----------------------------------------------------
   Load Instance
---------------------------------------------------- */
$instance = $db->fetch("SELECT * FROM approval_instances WHERE id = ?", [$instanceId]);

if (!$instance) {
    $_SESSION['toast_error'] = "Approval instance not found.";
    header("Location: ../pillars/list.php");
    exit;
}

// Prevent decision on closed workflows
if (in_array($instance['status'], ['approved', 'rejected'])) {
    $_SESSION['toast_error'] = "This approval workflow is already completed.";
    header("Location: ../pillars/view.php?id=".$instance['entity_id']);
    exit;
}

$entityId   = $instance['entity_id'];
$entityType = $instance['entity_type'];

$stages = getFlowStages($db, $entityType);

$currentStage = null;
foreach ($stages as $s) {
    if ($s['id'] == $instance['current_stage_id']) {
        $currentStage = $s;
        break;
    }
}

if (!$currentStage) {
    $_SESSION['toast_error'] = "Approval stage mismatch.";
    header("Location: ../pillars/view.php?id=".$entityId);
    exit;
}

/* ----------------------------------------------------
   Ensure this reviewer is allowed to decide
---------------------------------------------------- */
// 1) get pending entry
$pending = $db->fetch(
    "SELECT * FROM approval_pending 
     WHERE instance_id = ? AND stage_id = ? LIMIT 1",
    [$instanceId, $currentStage['id']]
);

if (!$pending) {
    $_SESSION['toast_error'] = "No pending approval for this stage.";
    header("Location: ../pillars/view.php?id=".$entityId);
    exit;
}

// 2) check permissions
if ($pending['assigned_to_user']) {
    if ($pending['assigned_to_user'] != $userId) {
        $_SESSION['toast_error'] = "You are not assigned to review this item.";
        header("Location: ../pillars/view.php?id=".$entityId);
        exit;
    }
} else {
    // role-based approval
    if ($_SESSION['role_key'] != $pending['reviewer_role']) {
        $_SESSION['toast_error'] = "You are not permitted to approve this stage.";
        header("Location: ../pillars/view.php?id=".$entityId);
        exit;
    }
}

/* ----------------------------------------------------
   Log the decision
---------------------------------------------------- */
logApprovalAction(
    $db,
    $instanceId,
    $currentStage['id'],
    $userId,
    $decision,
    $comments
);

// Remove the pending entry
$db->query("DELETE FROM approval_pending WHERE id = ?", [$pending['id']]);

/* ----------------------------------------------------
   Handle Returned
---------------------------------------------------- */
if ($decision === 'returned') {
    returnApprovalInstance($db, $instanceId);

    // pillar returns to Draft
    $db->query("UPDATE pillars SET status_id = 1 WHERE id = ?", [$entityId]); // Draft

    $_SESSION['toast_success'] = "Pillar has been returned for modification.";
    header("Location: ../pillars/view.php?id=".$entityId);
    exit;
}

/* ----------------------------------------------------
   Handle Rejected
---------------------------------------------------- */
if ($decision === 'rejected') {
    rejectApprovalInstance($db, $instanceId);

    $db->query("UPDATE pillars SET status_id = 6 WHERE id = ?", [$entityId]); // Rejected

    $_SESSION['toast_success'] = "Pillar has been rejected.";
    header("Location: ../pillars/view.php?id=".$entityId);
    exit;
}

/* ----------------------------------------------------
   APPROVED — continue workflow
---------------------------------------------------- */
$nextStage = getNextStage($stages, $currentStage['id']);

if (!$nextStage) {
    /* LAST STAGE → FINAL APPROVAL */
    completeApprovalInstance($db, $instanceId);

    // pillar final approval (status = Approved)
    $db->query("UPDATE pillars SET status_id = 11 WHERE id = ?", [$entityId]);

    $_SESSION['toast_success'] = "Pillar fully approved.";
    header("Location: ../pillars/view.php?id=".$entityId);
    exit;
}

/* ----------------------------------------------------
   Move to next stage
---------------------------------------------------- */
updateApprovalInstanceStage($db, $instanceId, $nextStage['id']);

/* Create pending decision for the next reviewer */
$db->query(
    "INSERT INTO approval_pending (instance_id, stage_id, reviewer_role, assigned_to_user)
     VALUES (?, ?, ?, NULL)",
    [
        $instanceId,
        $nextStage['id'],
        $nextStage['reviewer_role']
    ]
);

/* Update pillar status depending on stage */
if ($nextStage['reviewer_role'] === 'strategy_director') {
    $db->query("UPDATE pillars SET status_id = 9 WHERE id = ?", [$entityId]); // Pending Review
}
elseif ($nextStage['reviewer_role'] === 'ceo') {
    $db->query("UPDATE pillars SET status_id = 10 WHERE id = ?", [$entityId]); // Waiting CEO Approval
}

$_SESSION['toast_success'] = "Approval recorded successfully.";
header("Location: ../pillars/view.php?id=".$entityId);
exit;
