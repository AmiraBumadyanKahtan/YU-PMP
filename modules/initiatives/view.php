<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "initiative_functions.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("
    SELECT 
        i.*,
        p.name AS pillar_name,
        u.full_name_en AS owner_name,
        s.name AS status_name
    FROM initiatives i
    LEFT JOIN pillars p ON i.pillar_id = p.id
    LEFT JOIN users u ON i.owner_user_id = u.id
    LEFT JOIN initiative_statuses s ON i.status_id = s.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$initiative = $stmt->fetch();

if (!$initiative) die("Initiative not found");

/* =============================
   Current Approval Instance
============================= */
$approvalStmt = $db->prepare("
    SELECT ai.*, af.stage_name, af.role_key
    FROM approval_instances ai
    JOIN approval_flow_stages af ON ai.current_stage_id = af.id
    WHERE ai.entity_type = 'initiative'
    AND ai.entity_id = ?
    AND ai.status = 'in_progress'
");
$approvalStmt->execute([$id]);
$currentApproval = $approvalStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Initiative</title>
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/initiatives.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-bullseye"></i> <?= htmlspecialchars($initiative['name']) ?>
        </h1>

        <?php if (!$currentApproval && Auth::id() == $initiative['owner_user_id']): ?>
            <a href="send_for_approval.php?id=<?= $initiative['id'] ?>" class="btn-primary">
                Send For Approval
            </a>
        <?php endif; ?>
    </div>

    <div class="initiative-view-card">

        <p><strong>Code:</strong> <?= $initiative['initiative_code'] ?></p>
        <p><strong>Pillar:</strong> <?= $initiative['pillar_name'] ?></p>
        <p><strong>Owner:</strong> <?= $initiative['owner_name'] ?></p>
        <p><strong>Status:</strong> <?= $initiative['status_name'] ?></p>
        <p><strong>Priority:</strong> <?= ucfirst($initiative['priority']) ?></p>
        <p><strong>Start Date:</strong> <?= $initiative['start_date'] ?></p>
        <p><strong>Due Date:</strong> <?= $initiative['due_date'] ?></p>
        <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($initiative['description'])) ?></p>

    </div>

    <?php if ($currentApproval): ?>
        <div class="approval-box">
            <h3>Approval Status</h3>
            <p><strong>Current Stage:</strong> <?= $currentApproval['stage_name'] ?></p>
            <p><strong>Required Role:</strong> <?= $currentApproval['role_key'] ?></p>
            <p><strong>Approval State:</strong> <?= $currentApproval['status'] ?></p>
        </div>
    <?php endif; ?>

</div>
</div>
</body>
</html>
