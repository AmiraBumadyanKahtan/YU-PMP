<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/strategic-project-system/modules/shared/functions/approval_workflow.php';

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

$userId  = $_SESSION['user_id'];
$roleKey = $_SESSION['role_key'] ?? null;

$instanceId = $_GET['instance'] ?? null;
$entityId   = $_GET['id'] ?? null;

if (!$instanceId || !$entityId) {
    $_SESSION['toast_error'] = "Invalid approval reference.";
    header("Location: view.php?id=".$entityId);
    exit;
}

/* ----------------------------------------------------
   Load Instance + Current Stage
---------------------------------------------------- */
$instance = $db->prepare("SELECT * FROM approval_instances WHERE id = ?");
$instance->execute([$instanceId]);
$instance = $instance->fetch();

if (!$instance) {
    $_SESSION['toast_error'] = "Approval instance not found.";
    header("Location: view.php?id=".$entityId);
    exit;
}

$stages = getFlowStages($db, "pillar");

$currentStage = null;
foreach ($stages as $s) {
    if ($s['id'] == $instance['current_stage_id']) {
        $currentStage = $s;
        break;
    }
}

if (!$currentStage) {
    $_SESSION['toast_error'] = "Approval stage mismatch.";
    header("Location: view.php?id=".$entityId);
    exit;
}

/* ----------------------------------------------------
   Check if the user is allowed to act
---------------------------------------------------- */
$pending = $db->prepare("
    SELECT *
    FROM approval_pending
    WHERE instance_id = ? AND stage_id = ?
    LIMIT 1
");
$pending->execute([$instanceId, $currentStage['id']]);
$pending = $pending->fetch();

$allowed = false;

if ($pending) {
    if ($pending['assigned_to_user']) {
        if ($pending['assigned_to_user'] == $userId) $allowed = true;
    } else {
        if ($pending['reviewer_role'] == $roleKey) $allowed = true;
    }
}

// Super admin always allowed
if ($roleKey == "super_admin") $allowed = true;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Approval Decision</title>

<link rel="stylesheet" href="../../assets/css/layout.css">
<link rel="stylesheet" href="../../assets/css/toast.css">
<link rel="stylesheet" href="css/approve.css">
<link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<style>
.approval-wrapper {
    background:#fff;
    margin:40px auto;
    padding:30px;
    border-radius:16px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    max-width:700px;
    font-family: "Times New Roman", serif;
}
.approval-header {
    text-align:center;
    margin-bottom:25px;
}
.approval-header h2 {
    font-size:26px;
    margin-bottom:8px;
}
.stage-box {
    background:#f8f9fa;
    padding:15px;
    border-left:4px solid #3498db;
    margin-bottom:20px;
    border-radius:6px;
}
.form-group {
    margin-bottom:18px;
}
textarea {
    width:100%;
    padding:12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:15px;
}
.radio-row {
    display:flex;
    gap:20px;
    margin:15px 0;
}
.radio-row label {
    display:flex;
    align-items:center;
    gap:6px;
    font-size:16px;
}
.submit-btn {
    background:#27ae60;
    color:#fff;
    border:none;
    padding:12px 20px;
    border-radius:8px;
    font-size:16px;
    cursor:pointer;
}
.submit-btn:hover {
    background:#1f8e50;
}
.btn-back {
    display:inline-block;
    margin-top:15px;
    text-decoration:none;
    color:#555;
}
</style>

</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="approval-wrapper">

        <div class="approval-header">
            <h2><i class="fa-solid fa-gavel"></i> Approval Decision</h2>
            <p>Review and submit your decision for this pillar.</p>
        </div>

        <div class="stage-box">
            <strong>Current Stage:</strong> <?= htmlspecialchars($currentStage['stage_name']) ?><br>
            <strong>Reviewer Role:</strong> <?= htmlspecialchars($currentStage['role_key']) ?>
        </div>

        <?php if (!$allowed): ?>
            <div class="warning-box" style="padding:12px;background:#ffe9e9;border-left:4px solid #e74c3c;border-radius:6px;">
                <strong>You are not authorized to approve this stage.</strong>
            </div>

            <a href="view.php?id=<?= $entityId ?>" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <?php else: ?>

        <form action="approve_action.php" method="POST">

            <input type="hidden" name="instance_id" value="<?= $instanceId ?>">

            <div class="radio-row">
                <label><input type="radio" name="decision" value="approved" required> Approve</label>
                <label><input type="radio" name="decision" value="rejected"> Reject</label>
                <label><input type="radio" name="decision" value="returned"> Return for Changes</label>
            </div>

            <div class="form-group">
                <label><strong>Comments (optional)</strong></label>
                <textarea name="comments" rows="5" placeholder="Add your notes or justification here..."></textarea>
            </div>

            <button class="submit-btn"><i class="fa-solid fa-check"></i> Submit Decision</button>

        </form>

        <a href="view.php?id=<?= $entityId ?>" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back</a>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
