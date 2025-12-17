<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "workflow_functions.php";

if (!Auth::can('manage_rbac')) die("Access Denied");

$workflows = getAllWorkflows();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approval Workflows</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .badge-entity { background:#e3f2fd; color:#1565c0; padding:4px 8px; border-radius:4px; font-size:0.85rem; }
    </style>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-code-branch"></i> Approval Workflows</h1>
        <a href="create.php" class="btn-primary">+ Create Workflow</a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Workflow Name</th>
                <th>Target Entity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workflows as $w): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($w['workflow_name']) ?></strong></td>
                    <td><span class="badge-entity"><?= htmlspecialchars($w['entity_name']) ?></span></td>
                    <td>
                        <?= $w['is_active'] ? '<span style="color:green">Active</span>' : '<span style="color:red">Inactive</span>' ?>
                    </td>
                    <td>
                        <a href="edit.php?id=<?= $w['id'] ?>" class="btn-edit" style="text-decoration:none; padding:5px 10px; border-radius:4px; background:#f0f0f0;">
                            <i class="fa-solid fa-pen"></i> Configure
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</div>
</div>
</body>
</html>