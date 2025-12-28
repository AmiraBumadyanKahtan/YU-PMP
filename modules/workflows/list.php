<?php
// modules/workflows/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "workflow_functions.php";

if (!Auth::can('manage_workflows')) die("Access Denied");

$workflows = getAllWorkflows();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approval Workflows</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/list.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-code-branch"></i> Approval Workflows</h1>
        <a href="create.php" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Create Workflow
        </a>
    </div>

    <div class="table-container">
        <?php if(empty($workflows)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-diagram-project"></i>
                <h3>No Workflows Found</h3>
                <p>Define approval processes for your system entities.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="30%">Workflow Name</th>
                        <th width="25%">Target Entity</th>
                        <th width="15%">Status</th>
                        <th width="30%" style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workflows as $w): ?>
                        <tr>
                            <td style="font-weight:700; color:#2c3e50; font-size:1rem;">
                                <?= htmlspecialchars($w['workflow_name']) ?>
                            </td>
                            <td>
                                <span class="badge-entity">
                                    <i class="fa-solid fa-cube"></i> <?= htmlspecialchars($w['entity_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($w['is_active']): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="edit.php?id=<?= $w['id'] ?>" class="action-btn btn-config">
                                    <i class="fa-solid fa-gear"></i> Configure
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</div>
</body>
</html>