<?php
// modules/project_roles/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::can('manage_project_roles')) die("Access Denied");

$roles = getProjectRoles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Roles</title>
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
        <h1 class="page-title"><i class="fa-solid fa-id-badge"></i> Project Roles Configuration</h1>
        <a href="create.php" class="btn-primary">
            <i class="fa-solid fa-plus"></i> New Role
        </a>
    </div>

    <div class="roles-grid">
        <?php if(empty($roles)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-users-slash"></i>
                <h3>No roles defined yet</h3>
                <p>Create roles to assign permissions within projects.</p>
            </div>
        <?php else: ?>
            <?php foreach ($roles as $r): ?>
                <div class="role-card">
                    <div class="role-content">
                        <h3><i class="fa-regular fa-user"></i> <?= htmlspecialchars($r['name']) ?></h3>
                        <div class="role-desc">
                            <?= htmlspecialchars($r['description']) ?>
                        </div>
                    </div>
                    
                    <div class="role-actions">
                        <a href="edit.php?id=<?= $r['id'] ?>" class="btn-manage">
                            <i class="fa-solid fa-shield-halved"></i> Configure Permissions
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</div>
</body>
</html>