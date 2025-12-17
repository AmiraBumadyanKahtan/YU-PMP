<?php
// modules/roles/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "role_functions.php";

// التحقق من الصلاحية
if (!Auth::can('manage_rbac')) {
    die("Access Denied. You do not have permission to manage roles.");
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Role ID");

// جلب بيانات الدور
$role = getRoleById($id);
if (!$role) die("Role not found");

// جلب كل الصلاحيات (مجمعة) + صلاحيات الدور الحالية
$groupedPermissions = getAllPermissionsGrouped(); 
$currentPermissions = getRolePermissionIds($id); // مصفوفة تحتوي على IDs فقط

$message = "";

// --- معالجة الحفظ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. تحديث البيانات الأساسية
    $name = trim($_POST['role_name']);
    $desc = trim($_POST['description']);
    updateRoleDetails($id, $name, $desc);

    // 2. تحديث الصلاحيات
    // المصفوفة permissions[] قد تكون غير موجودة إذا ألغى المستخدم تحديد كل شيء
    $selectedPerms = $_POST['permissions'] ?? [];
    
    if (updateRolePermissions($id, $selectedPerms)) {
        $message = "success";
        // تحديث المتغيرات لعرض الحالة الجديدة
        $role = getRoleById($id);
        $currentPermissions = getRolePermissionIds($id);
    } else {
        $message = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Role Permissions</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <style>
        /* Role Info Section */
        .role-header-card {
            background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px;
            border-left: 5px solid #3498db; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .form-group input, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        
        /* Permissions Grid */
        .perms-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .module-card {
            background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;
        }
        .module-header {
            background: #f8f9fa; padding: 10px 15px; border-bottom: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: center;
        }
        .module-title { font-weight: bold; text-transform: uppercase; color: #444; font-size: 0.9rem; }
        .select-all-btn { font-size: 0.75rem; color: #3498db; cursor: pointer; text-decoration: underline; }
        
        .perm-list { padding: 10px 15px; }
        .perm-item {
            display: flex; align-items: center; margin-bottom: 8px; padding: 5px;
            border-radius: 4px; transition: background 0.2s;
        }
        .perm-item:hover { background: #f0f8ff; }
        .perm-item input { margin-right: 10px; accent-color: #3498db; transform: scale(1.2); cursor: pointer; }
        .perm-item label { cursor: pointer; font-size: 0.95rem; color: #333; flex: 1; }
        .perm-key { display: block; font-size: 0.75rem; color: #999; margin-top: 2px; }

        /* Save Bar (Sticky Bottom) */
        .save-bar {
            position: sticky; bottom: 0; background: #fff; padding: 15px 30px;
            border-top: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05); margin: 0 -30px -30px -30px; /* Counteract padding */
            z-index: 100;
        }
        .alert { padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .alert-error { background: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper" style="padding-bottom: 0;"> <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-shield-halved"></i> Edit Role: <?= htmlspecialchars($role['role_name']) ?>
        </h1>
        <a href="list.php" class="btn-secondary">← Back to Roles</a>
    </div>

    <?php if ($message === 'success'): ?>
        <div class="alert alert-success">Permissions updated successfully!</div>
    <?php elseif ($message === 'error'): ?>
        <div class="alert alert-error">Failed to update permissions. Please try again.</div>
    <?php endif; ?>

    <form method="POST">
        
        <div class="role-header-card">
            <div class="form-row">
                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text" name="role_name" value="<?= htmlspecialchars($role['role_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Role Key (System Identifier)</label>
                    <input type="text" value="<?= htmlspecialchars($role['role_key']) ?>" disabled style="background:#eee; cursor:not-allowed;">
                    <small>Role Key cannot be changed.</small>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2"><?= htmlspecialchars($role['description']) ?></textarea>
            </div>
        </div>

        <h3 style="margin-bottom: 15px; color:#555;">Permissions Matrix</h3>
        
        <div class="perms-container">
            <?php foreach ($groupedPermissions as $module => $permissions): ?>
                <div class="module-card">
                    <div class="module-header">
                        <span class="module-title">
                            <i class="fa-solid fa-folder"></i> <?= ucfirst($module ?: 'General') ?>
                        </span>
                        <span class="select-all-btn" onclick="toggleGroup(this)">Select All</span>
                    </div>
                    <div class="perm-list">
                        <?php foreach ($permissions as $p): ?>
                            <?php 
                                $isChecked = in_array($p['id'], $currentPermissions) ? 'checked' : '';
                            ?>
                            <div class="perm-item">
                                <input type="checkbox" 
                                       name="permissions[]" 
                                       value="<?= $p['id'] ?>" 
                                       id="perm_<?= $p['id'] ?>" 
                                       <?= $isChecked ?>>
                                <label for="perm_<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['description'] ?: ucwords(str_replace('_', ' ', $p['permission_key']))) ?>
                                    <span class="perm-key"><?= $p['permission_key'] ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="save-bar">
            <div style="color:#666;">
                <i class="fa-solid fa-info-circle"></i> Changes affect users with this role immediately.
            </div>
            <button type="submit" class="btn-primary" style="padding: 10px 25px; font-size: 1rem;">
                <i class="fa-solid fa-save"></i> Save Changes
            </button>
        </div>

    </form>

</div>
</div>

<script>
// سكربت بسيط لتحديد الكل داخل المربع
function toggleGroup(btn) {
    // العثور على العنصر الأب (الكارت)
    let card = btn.closest('.module-card');
    // جلب جميع الـ checkboxes داخل هذا الكارت
    let checkboxes = card.querySelectorAll('input[type="checkbox"]');
    
    // التحقق: إذا كان الكل محدد، نلغي التحديد، والعكس
    let allChecked = true;
    checkboxes.forEach(cb => {
        if (!cb.checked) allChecked = false;
    });

    checkboxes.forEach(cb => {
        cb.checked = !allChecked;
    });

    // تغيير نص الزر
    btn.innerText = allChecked ? "Select All" : "Deselect All";
}
</script>

</body>
</html>