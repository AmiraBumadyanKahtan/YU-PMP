<?php
require_once "php/kpis_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPIs - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/kpis.css">
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

    <?php if ($canEdit): ?>
    <div style="margin-bottom: 25px; text-align: right;">
        <button onclick="openModal('addKPIModal')" class="btn-primary">
            <i class="fa-solid fa-plus"></i> Define New KPI
        </button>
    </div>
    <?php endif; ?>

    <?php if (empty($kpis)): ?>
        <div style="text-align:center; padding:60px; background:#fff; border-radius:12px; border:2px dashed #eee;">
            <i class="fa-solid fa-chart-line" style="font-size:3rem; color:#e0e0e0; margin-bottom:15px;"></i>
            <p style="color:#888; font-size:1.1rem;">No Key Performance Indicators (KPIs) defined yet.</p>
        </div>
    <?php else: ?>
        <div class="kpi-grid">
            <?php foreach ($kpis as $k): ?>
                <?php 
                    // منطق الألوان والنسبة
                    $percent = ($k['target_value'] > 0) ? min(100, round(($k['current_value'] / $k['target_value']) * 100)) : 0;
                    $color = '#2ecc71'; // Default Green (On Track)
                    if ($k['status_id_code'] == 2) $color = '#e74c3c'; // At Risk (Red)
                    if ($k['status_id_code'] == 3) $color = '#f1c40f'; // Needs Work (Yellow)
                    if ($k['status_id_code'] == 4) $color = '#3498db'; // Achieved (Blue)
                    
                    $avatar = $k['avatar'] ? BASE_URL.'assets/uploads/avatars/'.$k['avatar'] : BASE_URL.'assets/images/default-avatar.png';
                ?>
                <div class="kpi-card status-<?= $k['status_id_code'] ?>">
                    <div class="kpi-header">
                        <h3 class="kpi-title"><?= htmlspecialchars($k['name']) ?></h3>
                        <span class="kpi-freq"><?= ucfirst($k['frequency']) ?></span>
                    </div>
                    
                    <div class="kpi-body">
                        <?= htmlspecialchars($k['description']) ?>
                        <?php if(!empty($k['data_source'])): ?>
                            <br><span class="source-tag">Src: <?= htmlspecialchars($k['data_source']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="kpi-metrics">
                        <div>
                            <span class="metric-main"><?= $k['current_value'] ?><span class="metric-unit"><?= $k['unit'] ?></span></span>
                        </div>
                        <div class="metric-target">
                            Target: <?= $k['target_value'] ?>
                        </div>
                    </div>

                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $percent ?>%; background: <?= $color ?>;"></div>
                    </div>
                    
                    <div style="font-size:0.8rem; color:<?= $color ?>; font-weight:bold; margin-bottom:10px; display:flex; justify-content:space-between;">
                        <span><?= $k['status_name'] ?></span>
                        <span><?= $percent ?>%</span>
                    </div>

                    <div class="kpi-footer">
                        <div class="owner-info">
                            <img src="<?= $avatar ?>" class="owner-img">
                            <?= htmlspecialchars($k['owner_name']) ?>
                        </div>
                        <div>
                            <button onclick="openUpdateModal(<?= $k['id'] ?>, '<?= htmlspecialchars($k['name'], ENT_QUOTES) ?>', <?= $k['current_value'] ?>)" class="btn-update">Update</button>
                            <?php if($canEdit): ?>
                                <a href="?id=<?= $id ?>&delete_kpi=<?= $k['id'] ?>" onclick="return confirm('Delete KPI?')" class="btn-delete"><i class="fa-solid fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</div>

<div id="addKPIModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Define New KPI</h3>
            <span onclick="closeModal('addKPIModal')" class="close-btn">&times;</span>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="add_kpi" value="1">
                <div class="form-group">
                    <label class="form-label">KPI Name</label>
                    <input type="text" name="name" required class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Baseline Value</label>
                        <input type="number" name="baseline_value" step="0.01" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Target Value</label>
                        <input type="number" name="target_value" step="0.01" required class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Unit (e.g. %, SAR)</label>
                        <input type="text" name="unit" required class="form-input" placeholder="%">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Source</label>
                        <input type="text" name="data_source" class="form-input" placeholder="e.g. HR System">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Frequency</label>
                        <select name="frequency" class="form-select">
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Owner</label>
                        <select name="owner_id" required class="form-select">
                            <option value="">-- Team Member --</option>
                            <?php foreach($teamMembers as $tm): ?>
                                <option value="<?= $tm['user_id'] ?>"><?= htmlspecialchars($tm['full_name_en']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="text-align:right; margin-top:10px;">
                    <button type="submit" class="btn-primary">Create KPI</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="updateModal" class="modal">
    <div class="modal-content" style="width:400px;">
        <div class="modal-header">
            <h3>Update Reading</h3>
            <span onclick="closeModal('updateModal')" class="close-btn">&times;</span>
        </div>
        <form method="POST">
            <div class="modal-body">
                <p id="updateKPIName" style="color:#777; margin-bottom:20px; font-weight:bold;"></p>
                <input type="hidden" name="update_reading" value="1">
                <input type="hidden" name="kpi_id" id="updateKPIId">
                <div class="form-group">
                    <label class="form-label">Current Value</label>
                    <input type="number" name="current_value" id="updateCurrentValue" step="0.01" required class="form-input">
                </div>
                <div style="text-align:right;">
                    <button type="submit" class="btn-primary" style="width:100%;">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).style.display = 'block'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    
    function openUpdateModal(id, name, val) {
        document.getElementById('updateKPIId').value = id;
        document.getElementById('updateKPIName').innerText = name;
        document.getElementById('updateCurrentValue').value = val;
        openModal('updateModal');
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
        if(msg == 'added') Toast.fire({icon: 'success', title: 'KPI Created'});
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Reading Updated'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'KPI Deleted'});
    <?php endif; ?>
</script>

</body>
</html>