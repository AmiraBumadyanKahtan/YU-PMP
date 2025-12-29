<?php
require_once "../../core/init.php";
require_once "approval_functions.php";

if (!Auth::can('view_approvals')) {
    die("Access denied");
}

$id = (int)($_GET['id'] ?? 0);
$approval = getApprovalInstance($id);

if (!$approval) die("Approval not found");

$canAct = canUserActOnApproval($_SESSION['user_id'], $approval);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approval Details</title>
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/approvals.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>

<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <h1 class="page-title">
        <i class="fa-solid fa-file-circle-check"></i> Approval Details
    </h1>

    <div class="approval-card">

        <p><strong>Entity:</strong> <?= strtoupper($approval['entity_type']) ?></p>
        <p><strong>Stage:</strong> <?= $approval['stage_name'] ?></p>
        <p><strong>Requested By:</strong> <?= $approval['requester_name'] ?></p>
        <p><strong>Status:</strong> <?= strtoupper($approval['status']) ?></p>
        <p><strong>Created At:</strong> <?= $approval['created_at'] ?></p>

        <?php if ($canAct && $approval['status'] === 'in_progress'): ?>
            <div class="approval-actions">
                <button onclick="submitAction('approve')" class="btn btn-success">Approve</button>
                <button onclick="submitAction('reject')" class="btn btn-danger">Reject</button>
                <button onclick="submitAction('return')" class="btn btn-warning">Return</button>
            </div>
        <?php endif; ?>

        <form id="actionForm" method="POST" action="action.php">
            <input type="hidden" name="approval_id" value="<?= $id ?>">
            <input type="hidden" name="action" id="approval_action">
        </form>

    </div>

</div>
</div>

<script>
function submitAction(type) {
    document.getElementById("approval_action").value = type;
    document.getElementById("actionForm").submit();
}
</script>

</body>
</html>
