<?php
// modules/operational_projects/permissions.php
require_once "php/permissions_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Permissions - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/permissions.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <div class="content-card">
        <div class="card-header">
            <div style="width:40px; height:40px; background:#fff3e0; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#ff8c00; font-size:1.2rem;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <h3>Access Control Matrix</h3>
                <p>Manage permissions for team members within this project context.</p>
            </div>
        </div>
        
        <div class="perm-table-wrapper">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th>Team Member</th>
                        <?php foreach ($allPerms as $perm): ?>
                            <th title="<?= $perm['description'] ?>">
                                <?= str_replace(['manage_project_', 'view_project_', 'edit_'], '', $perm['permission_key']) ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teamMembers as $member): ?>
                        <tr>
                            <td>
                                <span class="member-name"><?= htmlspecialchars($member['full_name_en']) ?></span>
                                <span class="role-badge"><?= htmlspecialchars($member['role_name']) ?></span>
                            </td>
                            <?php foreach ($allPerms as $perm): ?>
                                <?php 
                                    $ovVal = $overrides[$member['user_id']][$perm['id']] ?? null;
                                    $roleHas = in_array($perm['id'], $roleDefaults[$member['role_id']] ?? []);
                                    
                                    // الحالة النهائية
                                    $isAllowed = ($ovVal === 1) || ($ovVal === null && $roleHas);
                                    
                                    // تحديد الستايل
                                    $iconClass = $isAllowed ? 'fa-circle-check' : 'fa-circle-xmark';
                                    $styleClass = $isAllowed ? 'st-allow' : 'st-deny';
                                    
                                    // إذا كان هناك استثناء (Override)
                                    if ($ovVal === 1) $styleClass = 'st-force-allow';
                                    if ($ovVal === 0) $styleClass = 'st-force-deny';
                                ?>
                                <td>
                                    <i class="fa-regular <?= $iconClass ?> status-icon <?= $styleClass ?>"></i>
                                    
                                    <form method="POST" style="display:flex; justify-content:center;">
                                        <input type="hidden" name="set_user_perm" value="1">
                                        <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>">
                                        <input type="hidden" name="permission_id" value="<?= $perm['id'] ?>">
                                        
                                        <button type="submit" name="action" value="grant" title="Force Allow" class="ctrl-btn btn-grant <?= $ovVal === 1 ? 'active' : '' ?>">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button type="submit" name="action" value="deny" title="Force Deny" class="ctrl-btn btn-deny <?= $ovVal === 0 ? 'active' : '' ?>">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                        <button type="submit" name="action" value="reset" title="Reset to Role Default" class="ctrl-btn">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    </form>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="legend-box">
            <div class="legend-item"><i class="fa-regular fa-circle-check st-allow"></i> Role Allowed</div>
            <div class="legend-item"><i class="fa-regular fa-circle-check st-force-allow"></i> Forced Allow</div>
            <div class="legend-item"><i class="fa-regular fa-circle-xmark st-deny"></i> Role Denied</div>
            <div class="legend-item"><i class="fa-regular fa-circle-xmark st-force-deny"></i> Forced Deny</div>
        </div>

    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <script>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({icon: 'success', title: 'Permission Updated'});
    </script>
<?php endif; ?>

</body>
</html>