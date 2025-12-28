<?php
// modules/operational_projects/resources.php
require_once "php/resources_BE.php";

// حساب عدد الموارد للإحصائيات
$resCount = count($resources);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resources - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/resources.css">
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

    <div class="page-header-flex">
        <div class="page-title">
            <h3>Resource Allocation</h3>
            <p>Manage budget items, materials, and human resources efficiently.</p>
        </div>
        
        <?php if ($canManageResources): ?>
            <button onclick="openResModal()" class="btn-primary-pill">
                <i class="fa-solid fa-plus"></i> Add Resource
            </button>
        <?php endif; ?>
    </div>

    <div class="res-stats">
        <div class="rs-card rs-blue">
            <div class="rs-icon"><i class="fa-solid fa-cubes"></i></div>
            <div class="rs-info"><div>Total Items</div><div><?= $resCount ?></div></div>
        </div>
        <div class="rs-card rs-orange">
            <div class="rs-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="rs-info"><div>Total Cost</div><div><?= number_format($totalResourcesCost, 2) ?><small>SAR</small></div></div>
        </div>
        <div class="rs-card rs-green">
            <div class="rs-icon"><i class="fa-solid fa-chart-pie"></i></div>
            <div class="rs-info"><div>Avg. Cost</div><div><?= ($resCount > 0) ? number_format($totalResourcesCost / $resCount, 0) : 0 ?><small>SAR</small></div></div>
        </div>
    </div>

    <?php if (empty($resources)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-box-open" style="font-size:4rem; margin-bottom:20px; color:#e2e8f0;"></i>
            <h3 style="margin:0 0 10px 0; color:#475569;">No Resources Yet</h3>
            <p style="margin:0; color:#94a3b8;">Start by allocating resources to this project.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div style="overflow-x: auto;">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th width="30%">Item Details</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                            <th>Assigned To</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $r): 
                            $catClass = 'rt-other'; $icon = 'fa-box';
                            if($r['category'] == 'human') { $catClass = 'rt-human'; $icon = 'fa-user-gear'; }
                            elseif($r['category'] == 'material') { $catClass = 'rt-material'; $icon = 'fa-screwdriver-wrench'; }
                            elseif($r['category'] == 'software') { $catClass = 'rt-software'; $icon = 'fa-laptop-code'; }
                            elseif($r['category'] == 'service') { $catClass = 'rt-service'; $icon = 'fa-handshake'; }
                        ?>
                        <tr class="res-row">
                            <td>
                                <span class="item-name"><?= htmlspecialchars($r['name']) ?></span>
                                <?php if($r['notes']): ?><span class="item-meta"><?= htmlspecialchars(substr($r['notes'],0,50)) . (strlen($r['notes'])>50 ? '...' : '') ?></span><?php endif; ?>
                            </td>
                            <td><span class="r-type-badge <?= $catClass ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $r['type_name'] ?></span></td>
                            <td><span style="font-weight:600; color:#475569; background:#f1f5f9; padding:4px 10px; border-radius:8px;"><?= $r['qty'] ?></span></td>
                            <td style="color:#64748b;"><?= number_format($r['cost_per_unit']) ?></td>
                            <td style="font-weight:800; color:#166534;"><?= number_format($r['total_cost']) ?></td>
                            <td>
                                <?php if($r['assigned_user_name']): ?>
                                    <div class="r-assignee">
                                        <div class="avatar-circle"><i class="fa-solid fa-user"></i></div>
                                        <span class="assignee-name"><?= htmlspecialchars($r['assigned_user_name']) ?></span>
                                    </div>
                                <?php else: ?> <span style="color:#cbd5e1; font-style:italic;">- Unassigned -</span> <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($canManageResources): ?>
                                    <a href="?id=<?= $id ?>&delete_res=<?= $r['id'] ?>" onclick="return confirm('Delete this resource?')" class="btn-icon delete" title="Delete Resource"><i class="fa-solid fa-trash-can"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
</div>

<div id="addResModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">New Resource</h3>
            <span class="close-modal" onclick="closeResModal()">&times;</span>
        </div>
        
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="add_resource" value="1">
                
                <div class="form-row">
                    <label class="form-lbl">Resource Name <span class="required-star">*</span></label>
                    <input type="text" name="name" required class="form-input" placeholder="e.g. Server License, Senior Consultant...">
                </div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-lbl">Category <span class="required-star">*</span></label>
                        <select name="resource_type_id" required class="form-input">
                            <option value="">-- Select Type --</option>
                            <?php foreach($resourceTypes as $rt): ?>
                                <option value="<?= $rt['id'] ?>"><?= htmlspecialchars($rt['type_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-lbl">Assigned Member</label>
                        <select name="assigned_to" class="form-input">
                            <option value="">-- Unassigned --</option>
                            <?php foreach($teamMembers as $tm): ?>
                                <option value="<?= $tm['user_id'] ?>"><?= htmlspecialchars($tm['full_name_en']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:10px;">
                    <div>
                        <label class="form-lbl">Quantity <span class="required-star">*</span></label>
                        <input type="number" name="qty" id="qty" value="1" min="1" required class="form-input" oninput="calcTotal()">
                    </div>
                    <div>
                        <label class="form-lbl">Cost per Unit (SAR)</label>
                        <input type="number" name="cost_per_unit" id="cost" value="0" min="0" step="0.01" class="form-input" oninput="calcTotal()">
                    </div>
                </div>

                <div class="total-display">
                    Total: <span id="res_total_disp">0.00</span> SAR
                </div>

                <div class="form-row" style="margin-top:20px; margin-bottom:0;">
                    <label class="form-lbl">Notes / Description</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Any additional details about this resource..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeResModal()">Cancel</button>
                <button type="submit" class="btn-save">Save Resource</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResModal() { document.getElementById('addResModal').style.display = 'flex'; }
    function closeResModal() { document.getElementById('addResModal').style.display = 'none'; }
    
    function calcTotal() {
        let q = document.getElementById('qty').value || 0;
        let c = document.getElementById('cost').value || 0;
        let total = (parseFloat(q) * parseFloat(c)).toFixed(2);
        // Add thousand separators for display
        document.getElementById('res_total_disp').innerText = Number(total).toLocaleString(undefined, {minimumFractionDigits: 2});
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addResModal')) {
            closeResModal();
        }
    }

    <?php if(isset($_GET['msg'])): ?>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        const msg = "<?= $_GET['msg'] ?>";
        if(msg == 'added') Toast.fire({icon: 'success', title: 'Resource Added Successfully'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Resource Deleted'});
    <?php endif; ?>
</script>

</body>
</html>