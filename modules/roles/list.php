<?php
// modules/roles/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "role_functions.php";

// ✅ 1. التحقق من صلاحية العرض
if (!Auth::can('sys_role_view')) {
    header("Location: ../../error/403.php");
    exit;
}

$roles = getRoles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Roles Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/list.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* تحسينات بسيطة */
        .role-actions .disabled-link {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
            background: #ccc;
        }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-shield-halved"></i> Roles & Permissions</h1>
        
        <?php if (Auth::can('sys_role_manage')): ?>
            <a href="create.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Create New Role
            </a>
        <?php endif; ?>
    </div>

    <div class="roles-grid">
        <?php foreach ($roles as $role): ?>
            <div class="role-card">
                <div class="role-header">
                    <div class="role-icon">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                </div>

                <div class="role-content">
                    <h3><?= htmlspecialchars($role['role_name']) ?></h3>
                    <span class="role-key">KEY: <?= htmlspecialchars($role['role_key']) ?></span>
                </div>
                
                <div class="role-desc">
                    <?= htmlspecialchars($role['description'] ?? 'No description provided.') ?>
                </div>

                <div class="role-actions">
                    <?php if (Auth::can('sys_perm_assign') || Auth::can('sys_role_manage')): ?>
                        <a href="edit.php?id=<?= $role['id'] ?>" class="btn-manage">
                            <i class="fa-solid fa-gears"></i> Manage Role
                        </a>
                    <?php else: ?>
                        <span class="btn-manage disabled-link">
                            <i class="fa-solid fa-lock"></i> View Only
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

</body>
</html>