<?php
// modules/project_roles/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::can('manage_project_roles')) die("Access Denied");

$id = $_GET['id'] ?? 0;
$role = getProjectRoleById($id);

if (!$role) die("Role not found");

$groupedPerms = getProjectRelatedPermissions();
$currentPerms = getProjectRolePermissions($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perms = $_POST['permissions'] ?? [];
    
    if (updateProjectRolePermissions($id, $perms)) {
        header("Location: edit.php?id=$id&msg=success");
        exit;
    } else {
        $error = "Failed to update permissions.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Project Role</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/edit.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">

</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-user-shield"></i> Configure Role: <span style="color:#555; margin-left:10px; font-weight:400;"><?= htmlspecialchars($role['name']) ?></span>
        </h1>
        <a href="list.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to Roles</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Project Permissions updated successfully.',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        </script>
    <?php endif; ?>

    <form method="POST">
        
        <?php foreach($groupedPerms as $module => $items): ?>
            <div class="perm-section">
                <div class="perm-header">
                    <h3><i class="fa-regular fa-folder-open"></i> <?= htmlspecialchars(ucfirst($module)) ?></h3>
                    <span class="select-all" onclick="toggleGroup(this)">Select All</span>
                </div>
                
                <div class="perm-grid">
                    <?php foreach($items as $p): ?>
                        <label class="perm-item">
                            <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" 
                                <?= in_array($p['id'], $currentPerms) ? 'checked' : '' ?>>
                            
                            <div class="perm-text">
                                <span class="perm-title">
                                    <?= htmlspecialchars($p['description'] ?: ucwords(str_replace('_', ' ', $p['permission_key']))) ?>
                                </span>
                                <span class="perm-key"><?= $p['permission_key'] ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="save-bar">
            <div class="info-text">
                <i class="fa-solid fa-circle-info"></i> Changes affect project members immediately.
            </div>
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-save"></i> Save Changes
            </button>
        </div>

    </form>

</div>
</div>

<script>
function toggleGroup(btn) {
    const section = btn.closest('.perm-section');
    const checkboxes = section.querySelectorAll('input[type="checkbox"]');
    
    // Check if ALL are currently checked
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    // Toggle
    checkboxes.forEach(cb => cb.checked = !allChecked);
    
    // Update button text
    btn.textContent = !allChecked ? 'Deselect All' : 'Select All';
}
</script>

</body>
</html>