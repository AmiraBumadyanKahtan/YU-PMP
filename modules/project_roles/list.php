<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::can('manage_rbac')) die("Access Denied");

$roles = getProjectRoles();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Roles</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-id-badge"></i> Project Roles Configuration</h1>
        <a href="create.php" class="btn-primary">+ New Role</a>
    </div>

    <div class="roles-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
        <?php foreach ($roles as $r): ?>
            <div class="card" style="background:#fff; padding:20px; border-radius:8px; border-top:4px solid #3498db; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
                <h3 style="margin-top:0; color:#2c3e50;"><?= htmlspecialchars($r['name']) ?></h3>
                <p style="color:#777; height:40px;"><?= htmlspecialchars($r['description']) ?></p>
                
                <div style="border-top:1px solid #eee; margin-top:15px; padding-top:15px; text-align:right;">
                    <a href="edit.php?id=<?= $r['id'] ?>" class="btn-secondary" style="font-size:0.9rem;">
                        <i class="fa-solid fa-shield-halved"></i> Configure Permissions
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>
</body>
</html>