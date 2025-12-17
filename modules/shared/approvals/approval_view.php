<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/init.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once '../functions/approval_workflow.php';

if (!isset($_GET['instance_id'])) {
    die("Invalid request.");
}

$db = new Database();
$instanceId = $_GET['instance_id'];
$userId = $_SESSION['user_id'];

// Fetch approval instance
$instance = $db->fetch("SELECT * FROM approval_instances WHERE id = ?", [$instanceId]);
if (!$instance) die("Approval instance not found.");

// Fetch entity data (pillar)
$pillar = $db->fetch("SELECT * FROM pillars WHERE id = ?", [$instance['entity_id']]);

// Fetch flow stages
$stages = getFlowStages($db, $instance['entity_type']);

// Fetch current stage
$currentStage = $db->fetch("SELECT * FROM approval_flow_stages WHERE id = ?", [$instance['current_stage_id']]);

// Fetch approval history
$history = $db->fetchAll("
    SELECT a.*, u.full_name_en AS reviewer 
    FROM approval_actions a 
    JOIN users u ON u.id = a.reviewer_id
    WHERE a.instance_id = ?
    ORDER BY a.created_at ASC
", [$instanceId]);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Approval Review</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background:#f5f6fa; font-family: sans-serif; }
        .wrapper { max-width: 900px; margin: 40px auto; background:white; padding:25px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
        .title { font-size: 24px; margin-bottom: 10px; font-weight: bold; }
        .section { margin-bottom: 25px; }
        .stage-box { padding:12px; border-left:4px solid #3498db; margin-bottom:8px; background:#f0f6ff; border-radius:5px; }
        .current { border-left-color:#e67e22 !important; background:#fff6e8; }
        .completed { border-left-color:#2ecc71 !important; background:#e9fbe9; }
        .history-item { padding:10px; background:#f7f7f7; border-radius:5px; margin-bottom:10px; }
        .decision-form textarea { width:100%; height:120px; padding:10px; border:1px solid #ccc; border-radius:5px; }
        .decision-form button { padding:12px 20px; border:none; border-radius:5px; cursor:pointer; }
        .approve { background:#2ecc71; color:white; }
        .reject { background:#e74c3c; color:white; }
        .return { background:#f1c40f; color:black; }
    </style>
</head>
<body>

<div class="wrapper">
    
    <div class="title">Approval Review – Pillar #<?= $pillar['pillar_number'] ?></div>

    <div class="section">
        <strong>Name:</strong> <?= $pillar['name'] ?><br>
        <strong>Description:</strong> <?= $pillar['description'] ?><br>
        <strong>Status:</strong> <?= $pillar['status_id'] ?><br>
        <strong>Current Stage:</strong> <?= $currentStage['stage_name'] ?><br>
    </div>

    <div class="section">
        <h3>Approval Stages</h3>
        <?php foreach ($stages as $stage): ?>
            <div class="stage-box
                <?= $stage['id'] == $instance['current_stage_id'] ? 'current' : '' ?>
                <?= $stage['id'] < $instance['current_stage_id'] ? 'completed' : '' ?>">
                <?= $stage['stage_order'] ?>. <?= $stage['stage_name'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="section">
        <h3>Approval History</h3>

        <?php if (empty($history)): ?>
            <div>No actions yet.</div>
        <?php else: ?>
            <?php foreach ($history as $h): ?>
                <div class="history-item">
                    <strong><?= ucfirst($h['decision']) ?></strong> by <?= $h['reviewer'] ?><br>
                    <em><?= $h['created_at'] ?></em><br>
                    <?= $h['comments'] ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Take Action</h3>
        <form class="decision-form" action="approval_process.php" method="POST">
            <input type="hidden" name="instance_id" value="<?= $instanceId ?>">

            <label>Comments (optional):</label>
            <textarea name="comments"></textarea>

            <br><br>

            <button type="submit" name="decision" value="approved" class="approve">Approve</button>
            <button type="submit" name="decision" value="rejected" class="reject">Reject</button>
            <button type="submit" name="decision" value="returned" class="return">Return to Owner</button>
        </form>
    </div>

</div>

</body>
</html>
