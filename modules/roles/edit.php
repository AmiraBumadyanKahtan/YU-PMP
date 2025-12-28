<?php
// modules/roles/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "role_functions.php";

// 1. التحقق من صلاحية إدارة الصلاحيات
if (!Auth::can('sys_perm_assign')) {
    header("Location: ../../error/403.php");
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Role ID");

// جلب بيانات الدور
$role = getRoleById($id);
if (!$role) die("Role not found");

// 2. التحقق من حماية السوبر أدمن
// إذا كان الدور هو Super Admin (ID: 1) والمستخدم الحالي ليس هو السوبر أدمن نفسه (role_id: 1)
// نمنعه. أما إذا كان سوبر أدمن يعدل سوبر أدمن، نسمح له (مع تحذير).
if ($id === 1 && $_SESSION['role_id'] != 1) {
    die("Access Denied: Only a Super Admin can edit the Super Admin role.");
}

// جلب الصلاحيات
$groupedPermissions = getAllPermissionsGrouped(); 
$currentPermissions = getRolePermissionIds($id); 

$message = "";

// 3. معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['role_name']);
    $desc = trim($_POST['description']);
    
    updateRoleDetails($id, $name, $desc);

    $selectedPerms = $_POST['permissions'] ?? [];
    
    // حماية إضافية: إذا كنا نعدل السوبر أدمن، تأكد أننا لا نحذف صلاحيات النظام الحساسة بالخطأ
    if ($id === 1) {
        // يمكنك إضافة منطق هنا لإجبار وجود صلاحيات معينة، لكن سأتركها لمرونتك
    }
    
    if (updateRolePermissions($id, $selectedPerms)) {
        $message = "success";
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
    <link rel="stylesheet" href="css/edit.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-user-shield"></i> Edit Role: <span style="color:#555; margin-left:10px; font-weight:400;"><?= htmlspecialchars($role['role_name']) ?></span>
            </h1>
            <a href="list.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Roles</a>
        </div>

        <?php if ($id === 1): ?>
            <div class="alert" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba;">
                <i class="fa-solid fa-triangle-exclamation"></i> 
                <b>Warning:</b> You are editing the <b>Super Admin</b> role. Removing permissions from this role may lock you out of the system. Proceed with caution.
            </div>
        <?php endif; ?>

        <?php if ($message === 'success'): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i> Permissions updated successfully!
            </div>
        <?php elseif ($message === 'error'): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> Failed to update permissions.
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="role-header-card">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="role_name" class="form-input" value="<?= htmlspecialchars($role['role_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role Key</label>
                        <input type="text" class="form-input" value="<?= htmlspecialchars($role['role_key']) ?>" disabled style="background:#f9f9f9; color:#888;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-textarea" rows="2"><?= htmlspecialchars($role['description']) ?></textarea>
                </div>
            </div>

            <h3 style="margin-bottom: 20px; color:#2d3748; font-size: 1.1rem; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                <i class="fa-solid fa-list-check" style="color:#ff8c00; margin-right:8px;"></i> Permissions Matrix
            </h3>
            
            <div class="perms-container">
                <?php foreach ($groupedPermissions as $module => $permissions): ?>
                    <div class="module-card">
                        <div class="module-header">
                            <span class="module-title">
                                <i class="fa-regular fa-folder-open"></i> <?= ucfirst(str_replace('_', ' ', $module)) ?>
                            </span>
                            <span class="select-all-btn" onclick="toggleGroup(this)">Select All</span>
                        </div>
                        <div class="perm-list">
                            <?php foreach ($permissions as $p): ?>
                                <?php $isChecked = in_array($p['id'], $currentPermissions) ? 'checked' : ''; ?>
                                <div class="perm-item">
                                    <input type="checkbox" 
                                           name="permissions[]" 
                                           value="<?= $p['id'] ?>" 
                                           id="perm_<?= $p['id'] ?>" 
                                           <?= $isChecked ?>>
                                    <label for="perm_<?= $p['id'] ?>">
                                        <?= htmlspecialchars($p['description'] ?: $p['permission_key']) ?>
                                        <span class="perm-key"><?= $p['permission_key'] ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="save-bar">
                <div style="color:#718096; font-size: 0.9rem;">
                    <i class="fa-solid fa-circle-info"></i> Changes are saved instantly.
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Save Permissions
                </button>
            </div>

        </form>

    </div>
</div>

<script>
    function toggleGroup(btn) {
        let card = btn.closest('.module-card');
        let checkboxes = card.querySelectorAll('input[type="checkbox"]');
        let allChecked = true;
        checkboxes.forEach(cb => { if (!cb.checked) allChecked = false; });
        checkboxes.forEach(cb => { cb.checked = !allChecked; });
        btn.innerText = allChecked ? "Select All" : "Deselect All";
    }
</script>

</body>
</html>