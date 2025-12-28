<?php
// modules/initiatives/tabs/kpis.php

// 1. الصلاحيات
$canManageKPIs = ($isOwner || $isSuper || Auth::can('manage_initiative_kpis')) && !$isLocked;

// 2. معالجة الإضافة (Add KPI)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_kpi']) && $canManageKPIs) {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $target = $_POST['target_value'];
    $unit = $_POST['unit']; // %, SAR, Count
    $freq = $_POST['frequency']; // Monthly, Quarterly
    $owner = $_POST['owner_id'];
    $type = $_POST['kpi_type']; // number, percentage
    $baseline = $_POST['baseline_value'] ?: 0;
    
    $stmt = $db->prepare("
        INSERT INTO kpis (
            name, description, target_value, current_value, baseline_value, 
            unit, kpi_type, frequency, owner_id, 
            parent_type, parent_id, status_id, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            'initiative', ?, 3, NOW()
        )
    "); // status 3 = Needs Work (Initial)
    
    $stmt->execute([$name, $desc, $target, $baseline, $baseline, $unit, $type, $freq, $owner, $id]);
    
    echo "<script>window.location.href='view.php?id=$id&tab=kpis&msg=kpi_added';</script>";
}

// 3. معالجة تحديث القيمة (Update Reading)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_kpi_value'])) {
    $kpiId = $_POST['kpi_id'];
    $newVal = $_POST['current_value'];
    
    // التحقق من صلاحية التحديث (المالك أو المدير)
    $kpiData = $db->query("SELECT owner_id, target_value, baseline_value FROM kpis WHERE id=$kpiId")->fetch();
    if ($canManageKPIs || $kpiData['owner_id'] == $_SESSION['user_id']) {
        
        // حساب الحالة تلقائياً
        $target = $kpiData['target_value'];
        $base = $kpiData['baseline_value'];
        
        // نسبة التحقيق
        $achievement = 0;
        if ($target != $base) {
            $achievement = ($newVal - $base) / ($target - $base) * 100;
        }
        
        $status = 2; // At Risk
        if ($achievement >= 100) $status = 4; // Achieved
        elseif ($achievement >= 80) $status = 1; // On Track
        elseif ($achievement >= 50) $status = 3; // Needs Work
        
        $db->prepare("UPDATE kpis SET current_value = ?, status_id = ?, last_updated = NOW() WHERE id = ?")
           ->execute([$newVal, $status, $kpiId]);
           
        echo "<script>window.location.href='view.php?id=$id&tab=kpis&msg=kpi_updated';</script>";
    }
}

// 4. حذف KPI
if (isset($_GET['delete_kpi']) && $canManageKPIs) {
    $kId = $_GET['delete_kpi'];
    $db->prepare("UPDATE kpis SET is_deleted=1 WHERE id=?")->execute([$kId]);
    echo "<script>window.location.href='view.php?id=$id&tab=kpis&msg=kpi_deleted';</script>";
}

// 5. جلب البيانات
$kpis = $db->prepare("
    SELECT k.*, u.full_name_en as owner_name, u.avatar as owner_avatar, 
           ks.status_name, ks.id as status_id_real
    FROM kpis k
    LEFT JOIN users u ON u.id = k.owner_id
    LEFT JOIN kpi_statuses ks ON ks.id = k.status_id
    WHERE k.parent_type = 'initiative' AND k.parent_id = ? AND k.is_deleted = 0
    ORDER BY k.id DESC
");
$kpis->execute([$id]);
$kpiList = $kpis->fetchAll(PDO::FETCH_ASSOC);

// بيانات للمودال
if ($canManageKPIs) {
    $team = $db->query("SELECT u.id, u.full_name_en FROM initiative_team it JOIN users u ON u.id = it.user_id WHERE it.initiative_id=$id AND it.is_active=1")->fetchAll();
}
?>

<style>
    /* KPI Cards */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 25px; }
    
    .kpi-card { 
        background: #fff; border: 1px solid #f0f2f5; border-radius: 16px; padding: 25px; 
        position: relative; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        border-left: 5px solid #ccc; /* Default color */
    }
    .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
    
    /* Status Colors */
    .st-border-1 { border-left-color: #2ecc71; } /* On Track */
    .st-border-2 { border-left-color: #e74c3c; } /* At Risk */
    .st-border-3 { border-left-color: #f39c12; } /* Needs Work */
    .st-border-4 { border-left-color: #3498db; } /* Achieved */
    
    .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    .kpi-title { font-size: 1.1rem; font-weight: 800; color: #2d3436; margin: 0; line-height: 1.3; }
    .kpi-freq { font-size: 0.75rem; color: #95a5a6; background: #f8f9fa; padding: 3px 8px; border-radius: 6px; text-transform: uppercase; font-weight: 700; }

    .kpi-values { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
    .curr-val { font-size: 2rem; font-weight: 900; color: #2d3436; line-height: 1; }
    .target-val { font-size: 0.9rem; color: #7f8c8d; font-weight: 600; margin-bottom: 4px; }
    
    .prog-bg { height: 8px; background: #eee; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .prog-fill { height: 100%; border-radius: 4px; transition: width 0.6s ease; }
    
    .kpi-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px dashed #eee; }
    .kpi-owner { display: flex; align-items: center; gap: 8px; }
    .owner-img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .owner-txt { font-size: 0.8rem; color: #555; font-weight: 600; }
    
    .kpi-actions { display: flex; gap: 10px; }
    .btn-upd { 
        background: #f9f9f9; color: #2c3e50; border: 1px solid #eee; padding: 6px 12px; 
        border-radius: 8px; font-size: 0.8rem; font-weight: 700; cursor: pointer; transition: 0.2s;
        text-decoration: none; display: flex; align-items: center; gap: 5px;
    }
    .btn-upd:hover { background: #34495e; color: #fff; border-color: #34495e; }
    .btn-del { color: #e74c3c; cursor: pointer; font-size: 0.9rem; padding: 6px; }

    /* Modal Styles (Consistent) */
    .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); justify-content: center; align-items: center; animation: fadeIn 0.3s; }
    .modal-box { background: #fff; width: 550px; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); overflow: hidden; transform: translateY(20px); animation: slideUp 0.3s forwards; }
    
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Performance Indicators (KPIs)</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Track key metrics and success criteria.</p>
        </div>
        <?php if($canManageKPIs): ?>
            <button onclick="openKPIModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-chart-line"></i> Add KPI
            </button>
        <?php endif; ?>
    </div>

    <?php if(empty($kpiList)): ?>
        <div style="text-align:center; padding:50px; border:2px dashed #eee; border-radius:16px; color:#ccc;">
            <i class="fa-solid fa-gauge-high" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
            <p style="font-size:1.1rem;">No KPIs defined yet.</p>
        </div>
    <?php else: ?>
        <div class="kpi-grid">
            <?php foreach($kpiList as $k): 
                $av = $k['owner_avatar'] ? '../../assets/uploads/avatars/'.$k['owner_avatar'] : '../../assets/uploads/avatars/default-profile.png';
                
                // حساب النسبة للعرض
                $percent = 0;
                $diff = $k['target_value'] - $k['baseline_value'];
                if ($diff != 0) {
                    $percent = ($k['current_value'] - $k['baseline_value']) / $diff * 100;
                }
                $percent = min(100, max(0, $percent)); // Clamp between 0-100 for width

                // ألوان الحالة
                $color = '#ccc';
                if($k['status_id_real'] == 1) $color = '#2ecc71'; // On Track
                elseif($k['status_id_real'] == 2) $color = '#e74c3c'; // At Risk
                elseif($k['status_id_real'] == 3) $color = '#f39c12'; // Needs Work
                elseif($k['status_id_real'] == 4) $color = '#3498db'; // Achieved
            ?>
            <div class="kpi-card st-border-<?= $k['status_id_real'] ?>">
                <div class="kpi-header">
                    <div class="kpi-title"><?= htmlspecialchars($k['name']) ?></div>
                    <span class="kpi-freq"><?= $k['frequency'] ?></span>
                </div>

                <div class="kpi-values">
                    <div class="curr-val">
                        <?= number_format($k['current_value']) ?>
                        <span style="font-size:1rem; font-weight:600; color:#aaa;"><?= htmlspecialchars($k['unit']) ?></span>
                    </div>
                    <div class="target-val">Target: <?= number_format($k['target_value']) ?></div>
                </div>

                <div class="prog-bg">
                    <div class="prog-fill" style="width:<?= $percent ?>%; background:<?= $color ?>;"></div>
                </div>
                
                <div style="font-size:0.8rem; color:#aaa; margin-bottom:15px;">
                    Last updated: <?= $k['last_updated'] ? date('d M', strtotime($k['last_updated'])) : 'Never' ?>
                </div>

                <div class="kpi-footer">
                    <div class="kpi-owner">
                        <img src="<?= $av ?>" class="owner-img" title="<?= htmlspecialchars($k['owner_name']) ?>">
                        <span class="owner-txt"><?= htmlspecialchars(explode(' ', $k['owner_name'])[0]) ?></span>
                    </div>
                    
                    <div class="kpi-actions">
                        <button class="btn-upd" onclick='openUpdateKPI(<?= json_encode($k) ?>)'>
                            <i class="fa-solid fa-pen"></i> Update
                        </button>
                        <?php if($canManageKPIs): ?>
                            <a href="view.php?id=<?= $id ?>&tab=kpis&delete_kpi=<?= $k['id'] ?>" class="btn-del" onclick="return confirm('Delete KPI?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if($canManageKPIs): ?>
<div id="addKPIModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header" style="padding:25px; border-bottom:1px solid #f0f0f0; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; color:#2c3e50;">Define New KPI</h3>
            <span onclick="closeKPIModal()" style="font-size:1.5rem; cursor:pointer; color:#ccc;">&times;</span>
        </div>
        <form method="POST" style="padding:25px;">
            <input type="hidden" name="add_kpi" value="1">
            
            <div class="form-row">
                <label class="form-lbl">KPI Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-input" required placeholder="e.g. Number of trained staff">
            </div>

            <div class="form-grid-2">
                <div>
                    <label class="form-lbl">Owner</label>
                    <select name="owner_id" class="form-input" required>
                        <?php foreach($team as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-lbl">Frequency</label>
                    <select name="frequency" class="form-input">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2" style="margin-top:15px;">
                <div>
                    <label class="form-lbl">Type</label>
                    <select name="kpi_type" class="form-input">
                        <option value="number">Number (Count)</option>
                        <option value="percentage">Percentage (%)</option>
                        <option value="currency">Currency (SAR)</option>
                    </select>
                </div>
                <div>
                    <label class="form-lbl">Unit Label</label>
                    <input type="text" name="unit" class="form-input" placeholder="e.g. Staff, %, SAR">
                </div>
            </div>

            <div class="form-grid-2" style="margin-top:15px;">
                <div>
                    <label class="form-lbl">Baseline Value</label>
                    <input type="number" name="baseline_value" class="form-input" value="0" step="any">
                </div>
                <div>
                    <label class="form-lbl">Target Value <span style="color:red">*</span></label>
                    <input type="number" name="target_value" class="form-input" required step="any">
                </div>
            </div>

            <div class="form-row" style="margin-top:15px;">
                <label class="form-lbl">Description</label>
                <textarea name="description" class="form-input" style="height:80px;"></textarea>
            </div>

            <div class="modal-footer" style="padding-top:20px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn-cancel" onclick="closeKPIModal()">Cancel</button>
                <button type="submit" class="btn-save">Create KPI</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="updateKPIModal" class="modal-overlay">
    <div class="modal-box" style="width:400px;">
        <div class="modal-header" style="padding:20px; border-bottom:1px solid #eee;">
            <h3 style="margin:0; font-size:1.2rem;">Update KPI Reading</h3>
            <span onclick="document.getElementById('updateKPIModal').style.display='none'" style="cursor:pointer;">&times;</span>
        </div>
        <form method="POST" style="padding:25px;">
            <input type="hidden" name="update_kpi_value" value="1">
            <input type="hidden" name="kpi_id" id="upd_kpi_id">
            
            <div style="text-align:center; margin-bottom:20px;">
                <div id="upd_kpi_name" style="font-weight:700; color:#2c3e50; margin-bottom:5px;"></div>
                <div style="font-size:0.9rem; color:#7f8c8d;">Target: <span id="upd_kpi_target"></span></div>
            </div>

            <div class="form-row">
                <label class="form-lbl">New Current Value</label>
                <input type="number" name="current_value" id="upd_current_val" class="form-input" required step="any" style="font-size:1.5rem; text-align:center; color:#3498db; font-weight:800;">
            </div>

            <div class="modal-footer" style="justify-content:center;">
                <button type="submit" class="btn-save" style="width:100%; background:#3498db;">Save Reading</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openKPIModal() { document.getElementById('addKPIModal').style.display = 'flex'; }
    function closeKPIModal() { document.getElementById('addKPIModal').style.display = 'none'; }
    
    function openUpdateKPI(kpi) {
        document.getElementById('updateKPIModal').style.display = 'flex';
        document.getElementById('upd_kpi_id').value = kpi.id;
        document.getElementById('upd_kpi_name').innerText = kpi.name;
        document.getElementById('upd_kpi_target').innerText = kpi.target_value + ' ' + kpi.unit;
        document.getElementById('upd_current_val').value = kpi.current_value;
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addKPIModal')) closeKPIModal();
        if (event.target == document.getElementById('updateKPIModal')) document.getElementById('updateKPIModal').style.display='none';
    }
</script>