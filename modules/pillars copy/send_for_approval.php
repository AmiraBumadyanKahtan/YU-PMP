<?php
require_once '../../core/config.php';
require_once '../../core/Database.php';
require_once '../../core/init.php';
require_once '../shared/functions/approval_workflow.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}

$db = Database::getInstance()->pdo();
$userId   = $_SESSION['user_id'];
$pillarId = $_POST['pillar_id'] ?? null;

// -----------------------
// Validate pillar ID
// -----------------------
if (!$pillarId || !is_numeric($pillarId)) {
    $_SESSION['toast_error'] = "Invalid request.";
    header("Location: list.php");
    exit;
}

// -----------------------
// Load pillar
// -----------------------
$pillar = $db->fetch("SELECT * FROM pillars WHERE id = ?", [$pillarId]);

if (!$pillar) {
    $_SESSION['toast_error'] = "Pillar not found.";
    header("Location: list.php");
    exit;
}

// -----------------------
// Must have at least 1 objective
// -----------------------
$objectiveCount = $db->fetchColumn("SELECT COUNT(*) FROM strategic_objectives WHERE pillar_id = ?", [$pillarId]);

if ($objectiveCount == 0) {
    $_SESSION['toast_error'] = "You must add at least one strategic objective before sending for approval.";
    header("Location: view.php?id=".$pillarId);
    exit;
}

// -----------------------
// Pillar must be in Draft only
// -----------------------
$draftStatusId = 1; // تأكدي هذا رقم حالة Draft عندك
if ($pillar['status_id'] != $draftStatusId) {
    $_SESSION['toast_error'] = "This pillar is already under approval workflow.";
    header("Location: view.php?id=".$pillarId);
    exit;
}


// -----------------------
// Check if approval instance already exists
// -----------------------
$existing = getApprovalInstance($db, "pillar", $pillarId);

if ($existing) {
    $_SESSION['toast_error'] = "Approval workflow has already started.";
    header("Location: view.php?id=".$pillarId);
    exit;
}


// -----------------------
// Load Flow Stages
// -----------------------
$stages = getFlowStages($db, 'pillar');

if (empty($stages)) {
    $_SESSION['toast_error'] = "Approval flow is not configured.";
    header("Location: view.php?id=".$pillarId);
    exit;
}

$firstStage = $stages[0];


// -----------------------
// 1) Create approval instance
// -----------------------
$instanceId = createApprovalInstance($db, 'pillar', $pillarId, $userId);


// -----------------------
// 2) Move instance to first stage
// -----------------------
updateApprovalInstanceStage($db, $instanceId, $firstStage['id']);


// -----------------------
// 3) Create pending approval entry for reviewer
// -----------------------
$db->query(
    "INSERT INTO approval_pending (instance_id, stage_id, reviewer_role, assigned_to_user)
     VALUES (?, ?, ?, NULL)",
    [
        $instanceId,
        $firstStage['id'],
        $firstStage['reviewer_role']
    ]
);


// -----------------------
// 4) Update pillar status → Pending Review
// -----------------------
$pendingReviewId = 9; // تأكدي أن 9 = Pending Review
$db->query("UPDATE pillars SET status_id = ? WHERE id = ?", [$pendingReviewId, $pillarId]);


// -----------------------
// Done
// -----------------------
$_SESSION['toast_success'] = "Pillar successfully submitted for approval.";

header("Location: view.php?id=".$pillarId);
exit;
