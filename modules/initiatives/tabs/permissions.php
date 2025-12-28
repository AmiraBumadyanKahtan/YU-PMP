<?php
// modules/initiatives/tabs/permissions.php

// 1. التحقق من الصلاحية (من يملك حق تغيير الصلاحيات؟)
// المالك، السوبر أدمن، أو من لديه صلاحية 'manage_initiative_permissions'
$canManagePerms = ($isOwner || $isSuper || Auth::can('manage_initiative_permissions'));

// 2. التحقق من حالة الإغلاق (Locked Status)
// الحالات المغلقة: Approved(5), Rejected(7), Cancelled
// (أرقام الحالات حسب جدولك: 5=Approved, 7=Rejected)
$isLocked = in_array($init['status_id'], [ 7]);

// 3. معالجة الطلب (POST) - تحديث الصلاحية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user_perm']) && $canManagePerms && !$isLocked) {
    $uId = $_POST['user_id'];
    $pId = $_POST['permission_id'];
    $act = $_POST['action']; // grant, deny, reset

    if ($act == 'reset') {
        // حذف الاستثناء والعودة للافتراضي
        $stmt = $db->prepare("DELETE FROM initiative_user_permissions WHERE initiative_id=? AND user_id=? AND permission_id=?");
        $stmt->execute([$id, $uId, $pId]);
    } else {
        // إضافة/تحديث الاستثناء
        $grant = ($act == 'grant') ? 1 : 0;
        $stmt = $db->prepare("
            INSERT INTO initiative_user_permissions (initiative_id, user_id, permission_id, is_granted) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE is_granted=?
        ");
        $stmt->execute([$id, $uId, $pId, $grant, $grant]);
    }
    
    // إعادة تحميل الصفحة لتحديث الجدول (داخل نفس التبويب)
    echo "<script>window.location.href='view.php?id=$id&tab=permissions&msg=perm_updated';</script>";
    exit;
}

// 4. جلب البيانات للعرض
// أ) قائمة الصلاحيات الخاصة بالمبادرات
$allPerms = $db->query("SELECT * FROM permissions WHERE module = 'initiatives' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// ب) أعضاء الفريق (موجودة مسبقاً في view.php كـ $teamMembers ولكن نحتاج نعيد جلبها إذا لم تكن كافية)
// سنستخدم $teamMembers الموجودة في view.php، ولكن نحتاج التأكد أنها تحتوي على user_id و role_id

// ج) الاستثناءات (Overrides)
$overrides = [];
$stmt = $db->prepare("SELECT * FROM initiative_user_permissions WHERE initiative_id = ?");
$stmt->execute([$id]);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $overrides[$row['user_id']][$row['permission_id']] = $row['is_granted'];
}

// د) الافتراضيات للأدوار (Defaults)
$roleDefaults = [];
$stmt = $db->query("SELECT * FROM initiative_role_permissions");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $roleDefaults[$row['role_id']][] = $row['permission_id'];
}
?>

<style>
    /* --- Styles copied and adapted from Projects --- */
    .perm-card {
        background: #fff; border: 1px solid #f0f0f0; border-radius: 16px; 
        padding: 0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }
    
    .perm-header {
        padding: 20px 25px; border-bottom: 1px solid #f0f0f0; display: flex; 
        justify-content: space-between; align-items: center; background: #fff;
    }

    .locked-alert { 
        background: #fff3cd; color: #856404; padding: 12px; border-radius: 8px; 
        margin-bottom: 20px; border: 1px solid #ffeeba; text-align: center; font-weight: 600;
    }

    /* Table */
    .perm-table-wrapper { overflow-x: auto; }
    .perm-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    
    .perm-table th { 
        background: #fcfcfc; color: #636e72; font-size: 0.75rem; text-transform: uppercase; 
        padding: 15px; border-bottom: 1px solid #eee; text-align: center; vertical-align: middle;
    }
    .perm-table th:first-child { text-align: left; padding-left: 25px; min-width: 200px; position: sticky; left: 0; background: #fcfcfc; z-index: 2; border-right: 1px solid #eee; }
    
    .perm-table td { 
        padding: 12px; border-bottom: 1px solid #f9f9f9; text-align: center; vertical-align: middle;
        transition: 0.2s;
    }
    .perm-table td:first-child { 
        text-align: left; padding-left: 25px; font-weight: 600; color: #2d3436; 
        position: sticky; left: 0; background: #fff; z-index: 1; border-right: 1px solid #eee;
    }
    .perm-table tr:hover td { background-color: #fafafa; }
    .perm-table tr:hover td:first-child { background-color: #fafafa; }

    /* Icons & Buttons */
    .status-icon { font-size: 1.2rem; }
    .st-allow { color: #2ecc71; } /* Green Check */
    .st-deny { color: #e0e0e0; } /* Gray X */
    
    .st-force-allow { color: #27ae60; text-shadow: 0 0 5px rgba(46,204,113,0.4); }
    .st-force-deny { color: #c0392b; text-shadow: 0 0 5px rgba(231,76,60,0.4); }

    /* Control Buttons (Hidden by default, show on hover) */
    .ctrl-group { 
        display: flex; gap: 5px; justify-content: center; opacity: 0.1; 
        transform: scale(0.8); transition: 0.2s; margin-top: 5px; 
    }
    .perm-table td:hover .ctrl-group { opacity: 1; transform: scale(1); }
    
    .ctrl-btn { 
        width: 24px; height: 24px; border-radius: 4px; border: none; cursor: pointer; 
        display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #fff;
    }
    .btn-grant { background: #eee; color: #ccc; } .btn-grant:hover, .btn-grant.active { background: #2ecc71; color: #fff; }
    .btn-deny { background: #eee; color: #ccc; } .btn-deny:hover, .btn-deny.active { background: #e74c3c; color: #fff; }
    .btn-reset { background: #f0f0f0; color: #7f8c8d; } .btn-reset:hover { background: #34495e; color: #fff; }

    /* Legend */
    .legend-box { 
        display: flex; gap: 20px; padding: 15px 25px; background: #fcfcfc; 
        border-top: 1px solid #eee; justify-content: flex-end; font-size: 0.8rem; color: #7f8c8d;
    }
    .legend-item i { margin-right: 5px; }

    .role-badge-small { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; background: #eee; color: #777; margin-top: 4px; display: inline-block; }
</style>

<div class="tab-card" style="padding: 0; border:none; box-shadow:none;">
    
    <?php if ($isLocked): ?>
        <div class="locked-alert">
            <i class="fa-solid fa-lock"></i> Permissions are locked because the initiative is 
            <strong><?= $init['status_name'] ?></strong>.
        </div>
    <?php endif; ?>

    <div class="perm-card">
        <div class="perm-header">
            <div>
                <h3 style="margin:0; color:#2c3e50;">Access Control Matrix</h3>
                <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Fine-tune permissions for each member.</p>
            </div>
            <div>
                </div>
        </div>

        <div class="perm-table-wrapper">
            <table class="perm-table">
                <thead>
                    <tr>
                        <th>Team Member</th>
                        <?php foreach ($allPerms as $perm): 
                            // تنظيف الاسم للعرض (حذف initiative_ و manage_ وغيرها)
                            $shortName = str_replace(['manage_initiative_', 'view_initiative_', 'initiative_'], '', $perm['permission_key']);
                            $shortName = ucwords(str_replace('_', ' ', $shortName));
                        ?>
                            <th title="<?= htmlspecialchars($perm['description'] ?? $perm['permission_key']) ?>">
                                <?= $shortName ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($teamMembers)): ?>
                        <tr><td colspan="<?= count($allPerms) + 1 ?>" style="padding:30px;">No team members found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($teamMembers as $member): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php $av = $member['avatar'] ? '../../assets/uploads/avatars/'.$member['avatar'] : '../../assets/uploads/avatars/default-profile.png'; ?>
                                        <img src="<?= $av ?>" style="width:32px; height:32px; border-radius:50%; object-fit:cover;">
                                        <div>
                                            <div style="font-weight:700;"><?= htmlspecialchars($member['full_name_en']) ?></div>
                                            <span class="role-badge-small"><?= htmlspecialchars($member['role_name']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <?php foreach ($allPerms as $perm): ?>
                                    <?php 
                                        $uId = $member['user_id'];
                                        $pId = $perm['id'];
                                        $rId = $member['role_id'];

                                        // 1. هل هناك استثناء؟
                                        $override = $overrides[$uId][$pId] ?? null; // 1, 0, or null
                                        
                                        // 2. هل الدور يمتلك الصلاحية؟
                                        $roleHas = in_array($pId, $roleDefaults[$rId] ?? []);

                                        // 3. الحالة النهائية
                                        $finalAccess = ($override === 1) || ($override === null && $roleHas);

                                        // 4. الأيقونة والستايل
                                        $icon = $finalAccess ? 'fa-circle-check' : 'fa-circle-xmark';
                                        $style = $finalAccess ? 'st-allow' : 'st-deny';
                                        if ($override === 1) $style = 'st-force-allow';
                                        if ($override === 0) $style = 'st-force-deny';
                                    ?>
                                    <td>
                                        <i class="fa-regular <?= $icon ?> status-icon <?= $style ?>"></i>
                                        
                                        <?php if($canManagePerms && !$isLocked): ?>
                                            <form method="POST" class="ctrl-group">
                                                <input type="hidden" name="set_user_perm" value="1">
                                                <input type="hidden" name="user_id" value="<?= $uId ?>">
                                                <input type="hidden" name="permission_id" value="<?= $pId ?>">
                                                
                                                <button type="submit" name="action" value="grant" class="ctrl-btn btn-grant <?= $override===1?'active':'' ?>" title="Grant">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                                <button type="submit" name="action" value="deny" class="ctrl-btn btn-deny <?= $override===0?'active':'' ?>" title="Deny">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                                <button type="submit" name="action" value="reset" class="ctrl-btn btn-reset" title="Reset to Role Default">
                                                    <i class="fa-solid fa-rotate-left"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="legend-box">
            <div class="legend-item"><i class="fa-regular fa-circle-check st-allow"></i> Role Allowed</div>
            <div class="legend-item"><i class="fa-regular fa-circle-check st-force-allow"></i> User Granted (Override)</div>
            <div class="legend-item"><i class="fa-regular fa-circle-xmark st-deny"></i> Role Denied</div>
            <div class="legend-item"><i class="fa-regular fa-circle-xmark st-force-deny"></i> User Denied (Override)</div>
        </div>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'perm_updated'): ?>
<script>
    const Toast = Swal.mixin({
        toast: true, position: 'top-end', showConfirmButton: false, timer: 2000,
        timerProgressBar: true, didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    Toast.fire({icon: 'success', title: 'Permissions Updated'});
</script>
<?php endif; ?>