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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
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

    <?php if ($isLockedStatus): ?>
        <div class="locked-banner">
            <i class="fa-solid fa-lock fa-lg"></i>
            <div>
                Project is currently <strong><?= ($project['status_id'] == 4 ? 'Rejected' : ($project['status_id'] == 8  ? 'Completed' : 'Locked')) ?></strong>.
                Modifications are disabled.
            </div>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <div>
            <h3 ><i class="fa-solid fa-user-shield" style="color:#f39c12;"></i> Access Control Matrix</h3>
            <?php if($canEdit): ?>
                <span style="font-size:0.85rem; color:#666;"><i class="fa-solid fa-circle-info"></i> Hover over cells to modify permissions.</span>
            <?php endif; ?>
        </div>

        <div class="perm-table-wrapper">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th>Team Member</th>
                        <?php foreach ($allPerms as $perm): ?>
                            <th title="<?= htmlspecialchars($perm['description']) ?>">
                                <span class="module-header"><?= str_replace(['project_', 'proj_'], '', $perm['module']) ?></span>
                                <span class="perm-header-txt"><?= getReadableName($perm['permission_key']) ?></span>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teamMembers as $member): ?>
                        <?php 
                            // تخطي المدير من الجدول لأنه يملك كل الصلاحيات ولا يجب تعديله
                            // أو عرضه ولكن تعطيل الأزرار (سنعرضه ولكن نعطل الأزرار للمدير)
                            $isRowLocked = ($member['user_id'] == $project['manager_id']);
                        ?>
                        <tr>
                            <td>
                                <div class="member-info">
                                    <span class="member-name">
                                        <?= htmlspecialchars($member['full_name_en']) ?>
                                        <?php if($isRowLocked) echo '<i class="fa-solid fa-crown" style="color:#f1c40f; font-size:0.8rem; margin-left:5px;" title="Owner"></i>'; ?>
                                    </span>
                                    <span class="member-role"><?= htmlspecialchars($member['role_name']) ?></span>
                                </div>
                            </td>
                            
                            <?php foreach ($allPerms as $perm): ?>
                                <?php 
                                    // 1. هل الصلاحية موجودة في الدور الافتراضي؟
                                    $roleHasPerm = in_array($perm['id'], $roleDefaults[$member['role_id']] ?? []);
                                    
                                    // 2. هل يوجد استثناء خاص؟ (1=منح، 0=منع، null=لا يوجد)
                                    $override = $overrides[$member['user_id']][$perm['id']] ?? null;

                                    // 3. تحديد الحالة النهائية والايقونة
                                    $finalStatus = ($override === 1) ? true : (($override === 0) ? false : $roleHasPerm);
                                    
                                    // تحديد الكلاس للأيقونة
                                    if ($override === 1) {
                                        $iconClass = 'fa-circle-check st-forced-allow'; // منح خاص
                                    } elseif ($override === 0) {
                                        $iconClass = 'fa-circle-xmark st-forced-deny'; // منع خاص
                                    } elseif ($roleHasPerm) {
                                        $iconClass = 'fa-check st-allowed'; // مسموح من الدور
                                    } else {
                                        $iconClass = 'fa-xmark st-denied'; // ممنوع من الدور
                                    }
                                ?>
                                <td class="perm-cell">
                                    <i class="fa-solid <?= $iconClass ?> st-icon"></i>
                                    
                                    <?php if($canEdit && !$isRowLocked): ?>
                                    <div class="perm-controls">
                                        <form method="POST" style="display:contents;">
                                            <input type="hidden" name="set_user_perm" value="1">
                                            <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>">
                                            <input type="hidden" name="permission_id" value="<?= $perm['id'] ?>">
                                            
                                            <button type="submit" name="action" value="grant" class="btn-perm btn-grant" title="Force Allow">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                            
                                            <button type="submit" name="action" value="deny" class="btn-perm btn-deny" title="Force Deny">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            
                                            <?php if($override !== null): ?>
                                            <button type="submit" name="action" value="reset" class="btn-perm btn-reset" title="Reset to Default">
                                                <i class="fa-solid fa-rotate-left"></i>
                                            </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="legend-box">
            <div class="legend-item"><i class="fa-solid fa-check st-allowed"></i> Allowed (Role Default)</div>
            <div class="legend-item"><i class="fa-solid fa-circle-check st-forced-allow"></i> Specifically Granted</div>
            <div class="legend-item"><i class="fa-solid fa-xmark st-denied"></i> Denied (Role Default)</div>
            <div class="legend-item"><i class="fa-solid fa-circle-xmark st-forced-deny"></i> Specifically Denied</div>
        </div>
    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <script>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        const msg = "<?= $_GET['msg'] ?>";
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Permission Updated'});
        if(msg == 'error_owner') Swal.fire({icon: 'error', title: 'Action Denied', text: 'You cannot change permissions for the Project Manager.'});
    </script>
<?php endif; ?>

</body>
</html>