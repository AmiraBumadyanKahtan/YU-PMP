<?php
// modules/project_roles/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::can('manage_rbac')) die("Access Denied");

$id = $_GET['id'] ?? 0;
$role = getProjectRoleById($id);

if (!$role) die("Role not found");

// جلب الصلاحيات (الآن المصفوفة ستكون صحيحة 100%)
$groupedPerms = getProjectRelatedPermissions();
$currentPerms = getProjectRolePermissions($id);

// الحفظ
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* تحسينات التصميم */
        .perm-section { margin-bottom: 30px; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .perm-header {
            background: #f8f9fa; padding: 12px 20px; border-bottom: 1px solid #e0e0e0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .perm-header h3 { margin: 0; font-size: 1rem; color: #444; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .perm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* شبكة متجاوبة */
            gap: 1px; /* للفواصل */
            background: #eee; /* لون الفواصل */
        }
        
        .perm-item {
            background: #fff; padding: 15px; 
            display: flex; align-items: flex-start; gap: 12px;
            cursor: pointer; transition: background 0.15s;
        }
        .perm-item:hover { background: #f0f8ff; }
        
        .perm-item input[type="checkbox"] {
            margin-top: 4px; transform: scale(1.2); accent-color: #3498db; cursor: pointer;
        }
        
        .perm-text { flex: 1; }
        .perm-title { display: block; font-weight: 600; color: #2c3e50; font-size: 0.95rem; margin-bottom: 4px; }
        .perm-key { display: block; font-size: 0.8rem; color: #95a5a6; font-family: monospace; background: #f5f5f5; padding: 2px 6px; border-radius: 4px; width: fit-content; }
        
        .select-all { color: #3498db; font-size: 0.85rem; font-weight: 600; cursor: pointer; user-select: none; }
        .select-all:hover { text-decoration: underline; }
        
        /* Sticky Footer for Save Button */
        .save-bar {
            position: fixed; bottom: 0; right: 0; left: 250px; /* Adjust based on sidebar width */
            background: #fff; padding: 15px 30px; border-top: 1px solid #ddd;
            text-align: right; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        @media (max-width: 768px) { .save-bar { left: 0; } }
    </style>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper" style="padding-bottom: 80px;"> <div class="page-header-flex">
        <h1 class="page-title">
            Configure Role: <span style="color:#3498db;"><?= htmlspecialchars($role['name']) ?></span>
        </h1>
        <a href="list.php" class="btn-secondary">Back</a>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'Project Permissions updated successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

    <form method="POST">
        
        <?php foreach($groupedPerms as $module => $items): ?>
            <div class="perm-section">
                <div class="perm-header">
                    <h3><?= htmlspecialchars($module) ?></h3>
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
            <button type="submit" class="btn-primary" style="padding: 10px 30px; font-size: 1rem;">
                <i class="fa-solid fa-save"></i> Save Permissions
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