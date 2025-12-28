<?php
// modules/initiatives/tabs/resources.php

// 1. الصلاحيات
// نفترض وجود صلاحية manage_initiative_resources أو نستخدم manage_initiative_tasks
$canManageResources = ($isOwner || $isSuper || Auth::can('manage_initiative_tasks')) && !$isLocked;

// 2. معالجة الإضافة (Add Resource)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource']) && $canManageResources) {
    $rName = $_POST['name'];
    $rTypeId = $_POST['resource_type_id'];
    $qty = $_POST['qty'] ?: 1;
    $cost = $_POST['cost_per_unit'] ?: 0;
    $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
    $notes = $_POST['notes'];

    $stmt = $db->prepare("
        INSERT INTO work_resources (
            parent_type, parent_id, resource_type_id, name, qty, cost_per_unit, 
            assigned_to, notes, created_at
        ) VALUES (
            'initiative', ?, ?, ?, ?, ?, 
            ?, ?, NOW()
        )
    ");
    $stmt->execute([$id, $rTypeId, $rName, $qty, $cost, $assignedTo, $notes]);
    
    echo "<script>window.location.href='view.php?id=$id&tab=resources&msg=resource_added';</script>";
}

// 3. معالجة الحذف
if (isset($_GET['delete_resource']) && $canManageResources) {
    $rId = $_GET['delete_resource'];
    // الحذف المباشر (الجدول لا يحتوي على is_deleted حسب المخطط، لذا DELETE)
    $db->prepare("DELETE FROM work_resources WHERE id=?")->execute([$rId]);
    echo "<script>window.location.href='view.php?id=$id&tab=resources&msg=resource_deleted';</script>";
}

// 4. جلب البيانات
$resources = $db->prepare("
    SELECT r.*, rt.type_name, rt.category, u.full_name_en as assignee_name, u.avatar
    FROM work_resources r
    JOIN resource_types rt ON rt.id = r.resource_type_id
    LEFT JOIN users u ON u.id = r.assigned_to
    WHERE r.parent_type = 'initiative' AND r.parent_id = ?
    ORDER BY r.created_at DESC
");
$resources->execute([$id]);
$resList = $resources->fetchAll(PDO::FETCH_ASSOC);

// حساب الإجماليات
$totalCost = 0;
$resCount = count($resList);
foreach($resList as $r) {
    $totalCost += $r['total_cost']; // الحقل total_cost محسوب تلقائياً في القاعدة (Generated Column)
    // إذا لم يكن محسوباً في نسختك من MySQL:
    // $totalCost += ($r['qty'] * $r['cost_per_unit']);
}

// بيانات للمودال
if ($canManageResources) {
    $resTypes = $db->query("SELECT * FROM resource_types WHERE is_active=1")->fetchAll();
    $team = $db->query("SELECT u.id, u.full_name_en FROM initiative_team it JOIN users u ON u.id = it.user_id WHERE it.initiative_id=$id AND it.is_active=1")->fetchAll();
}
?>

<style>
    /* Stats Row */
    .res-stats { display: flex; gap: 20px; margin-bottom: 25px; }
    .rs-card { 
        flex: 1; background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #eee; 
        display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .rs-icon { width: 50px; height: 50px; border-radius: 12px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #555; }
    .rs-info div:first-child { font-size: 0.8rem; color: #999; font-weight: 700; text-transform: uppercase; }
    .rs-info div:last-child { font-size: 1.4rem; font-weight: 800; color: #2d3436; }

    /* Resource Table/Grid */
    .res-table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    .res-table th { text-align: left; color: #95a5a6; font-size: 0.85rem; padding: 0 15px; font-weight: 600; text-transform: uppercase; }
    
    .res-row { background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.02); transition: 0.2s; }
    .res-row:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    
    .res-row td { padding: 15px; border-top: 1px solid #f0f2f5; border-bottom: 1px solid #f0f2f5; }
    .res-row td:first-child { border-left: 1px solid #f0f2f5; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
    .res-row td:last-child { border-right: 1px solid #f0f2f5; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

    .r-type-badge { 
        font-size: 0.75rem; padding: 4px 10px; border-radius: 8px; font-weight: 700; 
        display: inline-flex; align-items: center; gap: 5px;
    }
    .rt-human { background: #e3f2fd; color: #3498db; }
    .rt-material { background: #fff3e0; color: #e67e22; }
    .rt-software { background: #e8f5e9; color: #27ae60; }
    .rt-service { background: #f3e5f5; color: #9b59b6; }
    .rt-other { background: #f5f5f5; color: #7f8c8d; }

    .r-assignee { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #555; }
    .r-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }

    /* Modal (Reuse) */
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Resource Allocation</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Manage budget items, materials, and human resources.</p>
        </div>
        <?php if($canManageResources): ?>
            <button onclick="openResModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-box-open"></i> Add Resource
            </button>
        <?php endif; ?>
    </div>

    <div class="res-stats">
        <div class="rs-card">
            <div class="rs-icon" style="color:#3498db; background:#e3f2fd;"><i class="fa-solid fa-cubes"></i></div>
            <div class="rs-info">
                <div>Total Items</div>
                <div><?= $resCount ?></div>
            </div>
        </div>
        <div class="rs-card">
            <div class="rs-icon" style="color:#e67e22; background:#fff3e0;"><i class="fa-solid fa-coins"></i></div>
            <div class="rs-info">
                <div>Total Cost</div>
                <div><?= number_format($totalCost, 2) ?> <small style="font-size:0.9rem; color:#aaa;">SAR</small></div>
            </div>
        </div>
        <div class="rs-card">
            <div class="rs-icon" style="color:#2ecc71; background:#e8f5e9;"><i class="fa-solid fa-chart-pie"></i></div>
            <div class="rs-info">
                <div>Budget Utilization</div>
                <?php 
                    $utilization = ($init['approved_budget'] > 0) ? ($totalCost / $init['approved_budget']) * 100 : 0; 
                ?>
                <div><?= round($utilization, 1) ?>%</div>
            </div>
        </div>
    </div>

    <?php if(empty($resList)): ?>
        <div style="text-align:center; padding:40px; color:#ccc; border:2px dashed #eee; border-radius:12px;">
            <i class="fa-solid fa-box-open" style="font-size:3rem; margin-bottom:10px;"></i>
            <p>No resources allocated yet.</p>
        </div>
    <?php else: ?>
        <table class="res-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Total</th>
                    <th>Assigned To</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($resList as $r): 
                    $catClass = 'rt-other'; $icon = 'fa-box';
                    if($r['category'] == 'human') { $catClass = 'rt-human'; $icon = 'fa-user'; }
                    elseif($r['category'] == 'material') { $catClass = 'rt-material'; $icon = 'fa-hammer'; }
                    elseif($r['category'] == 'software') { $catClass = 'rt-software'; $icon = 'fa-laptop-code'; }
                    elseif($r['category'] == 'service') { $catClass = 'rt-service'; $icon = 'fa-handshake'; }
                    
                    $av = $r['avatar'] ? '../../assets/uploads/avatars/'.$r['avatar'] : '../../assets/uploads/avatars/default-profile.png';
                ?>
                <tr class="res-row">
                    <td>
                        <div style="font-weight:700; color:#2c3e50;"><?= htmlspecialchars($r['name']) ?></div>
                        <div style="font-size:0.8rem; color:#999;"><?= htmlspecialchars($r['notes']) ?></div>
                    </td>
                    <td>
                        <span class="r-type-badge <?= $catClass ?>">
                            <i class="fa-solid <?= $icon ?>"></i> <?= $r['type_name'] ?>
                        </span>
                    </td>
                    <td style="font-weight:600;"><?= $r['qty'] ?></td>
                    <td style="color:#7f8c8d;"><?= number_format($r['cost_per_unit']) ?></td>
                    <td style="font-weight:800; color:#2c3e50;"><?= number_format($r['total_cost']) ?></td>
                    <td>
                        <?php if($r['assigned_to']): ?>
                            <div class="r-assignee">
                                <img src="<?= $av ?>" class="r-avatar">
                                <span><?= htmlspecialchars($r['assignee_name']) ?></span>
                            </div>
                        <?php else: ?>
                            <span style="color:#ccc;">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <?php if($canManageResources): ?>
                            <a href="view.php?id=<?= $id ?>&tab=resources&delete_resource=<?= $r['id'] ?>" onclick="return confirm('Remove resource?')" style="color:#e74c3c; opacity:0.6; transition:0.2s;">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if($canManageResources): ?>
<div id="addResModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header" style="padding:25px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; color:#2c3e50;">Allocated Resource</h3>
            <span onclick="closeResModal()" style="font-size:1.5rem; cursor:pointer; color:#ccc;">&times;</span>
        </div>
        <form method="POST" style="padding:25px;">
            <input type="hidden" name="add_resource" value="1">
            
            <div class="form-row">
                <label class="form-lbl">Resource Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-input" required placeholder="e.g. Server License, Meeting Room">
            </div>

            <div class="form-grid-2">
                <div>
                    <label class="form-lbl">Type</label>
                    <select name="resource_type_id" class="form-input" required>
                        <?php foreach($resTypes as $rt): ?>
                            <option value="<?= $rt['id'] ?>"><?= $rt['type_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-lbl">Assigned To (Optional)</label>
                    <select name="assigned_to" class="form-input">
                        <option value="">-- No Assignment --</option>
                        <?php foreach($team as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid-2" style="margin-top:15px;">
                <div>
                    <label class="form-lbl">Quantity</label>
                    <input type="number" name="qty" class="form-input" value="1" min="1" required oninput="calcTotal()">
                </div>
                <div>
                    <label class="form-lbl">Cost Per Unit (SAR)</label>
                    <input type="number" name="cost_per_unit" class="form-input" value="0" step="0.01" required oninput="calcTotal()">
                </div>
            </div>
            
            <div style="text-align:right; margin-top:10px; font-size:1.1rem; color:#27ae60; font-weight:700;">
                Total: <span id="res_total_disp">0.00</span> SAR
            </div>

            <div class="form-row" style="margin-top:15px; margin-bottom:0;">
                <label class="form-lbl">Notes</label>
                <textarea name="notes" class="form-input" style="height:80px;" placeholder="Additional details..."></textarea>
            </div>

            <div class="modal-footer" style="padding-top:20px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-cancel" onclick="closeResModal()">Cancel</button>
                <button type="submit" class="btn-save">Allocate</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openResModal() { document.getElementById('addResModal').style.display = 'flex'; }
    function closeResModal() { document.getElementById('addResModal').style.display = 'none'; }
    
    function calcTotal() {
        let q = document.querySelector('[name="qty"]').value;
        let c = document.querySelector('[name="cost_per_unit"]').value;
        document.getElementById('res_total_disp').innerText = (q * c).toFixed(2);
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addResModal')) closeResModal();
    }
</script>
<?php endif; ?>