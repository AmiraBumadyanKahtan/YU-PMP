<?php
// modules/roles/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "role_functions.php";

// التحقق من صلاحية manage_rbac (عادة للسوبر أدمن)
if (!Auth::can('manage_rbac')) {
    die("Access Denied. You need 'manage_rbac' permission.");
}

$roles = getRoles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Roles Management</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <style>
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .role-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        .role-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .role-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .role-title { font-size: 1.2rem; font-weight: bold; color: #333; }
        .role-key { font-size: 0.85rem; color: #777; background: #f5f5f5; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
        .role-desc { color: #666; font-size: 0.95rem; line-height: 1.5; margin-bottom: 20px; height: 40px; overflow: hidden; }
        .role-actions { border-top: 1px solid #eee; padding-top: 15px; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-manage { background: #3498db; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-size: 0.9rem; }
        .btn-manage:hover { background: #2980b9; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-shield-halved"></i> Roles & Permissions</h1>
        <a href="create.php" class="btn-primary">+ Create New Role</a>
    </div>

    <div class="roles-grid">
        <?php foreach ($roles as $role): ?>
            <div class="role-card">
                <div class="role-header">
                    <span class="role-title"><?= htmlspecialchars($role['role_name']) ?></span>
                    <span class="role-key"><?= htmlspecialchars($role['role_key']) ?></span>
                </div>
                
                <div class="role-desc">
                    <?= htmlspecialchars($role['description'] ?? 'No description provided.') ?>
                </div>

                <div class="role-actions">
                    <a href="edit.php?id=<?= $role['id'] ?>" class="btn-manage">
                        <i class="fa-solid fa-gears"></i> Manage Permissions
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

</body>
</html>