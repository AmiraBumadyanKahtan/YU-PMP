<?php
// modules/operational_projects/kpis.php
require_once "php/kpis_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPIs - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- General Layout --- */
        body { background-color: #f8f9fa; font-family: 'Varela Round', sans-serif; color: #2d3436; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* --- Header Section --- */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-title h3 { margin: 0; color: #2d3436; font-weight: 800; font-size: 1.75rem; letter-spacing: -0.5px; }
        .page-title p { margin: 6px 0 0; color: #636e72; font-size: 0.95rem; }

        /* --- Alerts --- */
        .locked-banner { 
            background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; 
            padding: 16px; border-radius: 12px; margin-bottom: 30px; 
            display: flex; align-items: center; gap: 12px; font-size: 0.95rem; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* --- KPI Grid --- */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; }
        
        .kpi-card { 
            font-family: 'Varela Round', sans-serif;
            background: #fff; border-radius: 16px; padding: 25px; 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); border: 1px solid #f1f2f6; 
            position: relative; transition: all 0.3s ease; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px -10px rgba(0,0,0,0.1); }
        
        /* Status Strip */
        .kpi-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px;
            background: #dfe6e9; transition: 0.3s;
        }
        
        /* Colors */
        .status-1 .kpi-card::before { background: #27ae60; } /* On Track */
        .status-1 .metric-val { color: #27ae60; }
        
        .status-2 .kpi-card::before { background: #e74c3c; } /* At Risk */
        .status-2 .metric-val { color: #e74c3c; }
        
        .status-3 .kpi-card::before { background: #f39c12; } /* Needs Work */
        .status-3 .metric-val { color: #f39c12; }
        
        .status-4 .kpi-card::before { background: #2980b9; } /* Achieved */
        .status-4 .metric-val { color: #2980b9; }

        /* Header inside card */
        .kpi-header {font-family: 'Varela Round', sans-serif; display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; padding-left: 10px; }
        .kpi-icon { 
            width: 40px; height: 40px; border-radius: 10px; background: #f8f9fa; 
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #b2bec3;
        }
        .kpi-freq { 
            font-size: 0.7rem; font-weight: 700; text-transform: uppercase; 
            padding: 4px 10px; border-radius: 20px; background: #f1f2f6; color: #636e72; 
        }

        /* Content */
        .kpi-title {font-family: 'Varela Round', sans-serif; font-size: 1.1rem; font-weight: 700; color: #2d3436; margin: 0 0 5px 0; padding-left: 10px; }
        .kpi-desc { font-size: 0.85rem; color: #95a5a6; padding-left: 10px; margin-bottom: 20px; line-height: 1.4; min-height: 38px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        /* Metrics Display */
        .metrics-box { 
            display: flex; align-items: flex-end; justify-content: space-between; 
            padding: 15px; background: #fdfdfd; border-radius: 12px; border: 1px solid #f5f6fa; margin-left: 10px;
        }
        .metric-val { font-size: 1.8rem; font-weight: 800; line-height: 1; }
        .metric-unit { font-size: 0.9rem; font-weight: 600; color: #b2bec3; margin-left: 2px; }
        .metric-target { font-size: 0.8rem; color: #636e72; font-weight: 600; text-align: right; }
        .metric-label { font-size: 0.7rem; color: #b2bec3; text-transform: uppercase; display: block; margin-bottom: 2px; }

        /* Progress Bar */
        .progress-wrapper { margin: 15px 0 15px 10px; }
        .progress-bg { height: 6px; background: #edf2f7; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }
        
        .status-1 .progress-fill { background: #2ecc71; }
        .status-2 .progress-fill { background: #e74c3c; }
        .status-3 .progress-fill { background: #f1c40f; }
        .status-4 .progress-fill { background: #3498db; }

        .progress-text { display: flex; justify-content: space-between; font-size: 0.75rem; color: #b2bec3; margin-top: 5px; font-weight: 600; }

        /* Footer */
        .kpi-footer { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; padding-left: 10px; 
        }
        .owner { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #636e72; font-weight: 600; }
        .owner img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        /* Buttons */
        .btn-grad {
            font-family: 'Varela Round', sans-serif;
            background: linear-gradient(135deg, #ff8c00 0%, #e67e00 100%);
            color: white; border: none; padding: 12px 25px; border-radius: 50px;
            font-weight: 700; cursor: pointer; transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.25); font-size: 0.95rem;
        }
        .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 140, 0, 0.35); }

        .btn-mini-update { 
            font-family: 'Varela Round', sans-serif;
            background: #fff3e0; color: #ff8c00; border: none; padding: 6px 14px; 
            border-radius: 20px; font-weight: 700; font-size: 0.75rem; cursor: pointer; transition: 0.2s; 
        }
        .btn-mini-update:hover { background: #ff8c00; color: #fff; }

        .btn-icon-del { color: #e74c3c; background: #fff0f0; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-icon-del:hover { background: #e74c3c; color: #fff; }

        /* Empty State */
        .empty-state { text-align: center; padding: 80px 20px; background: #fff; border-radius: 16px; border: 2px dashed #e0e0e0; }
        .empty-icon { font-size: 3.5rem; color: #e2e8f0; margin-bottom: 20px; }

        /* --- Modal Styling --- */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(45, 52, 54, 0.6); backdrop-filter: blur(4px); }
        .modal-content { 
            background-color: #fff; margin: 5% auto; padding: 0; border-radius: 16px; 
            width: 550px; max-width: 90%; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
            animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        .modal-header { padding: 20px 30px; border-bottom: 1px solid #f1f2f6; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #2d3436; font-size: 1.2rem; font-weight: 800; }
        .close-btn { font-size: 1.5rem; color: #b2bec3; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #c0392b; }

        .modal-body { padding: 30px; max-height: 70vh; overflow-y: auto; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        
        .form-label { display: block; margin-bottom: 8px; font-weight: 700; color: #636e72; font-size: 0.9rem; }
        .form-input, .form-select, .form-textarea { 
            width: 100%; padding: 12px 15px; border: 2px solid #f1f2f6; border-radius: 10px; 
            font-family: inherit; font-size: 0.95rem; box-sizing: border-box; transition: 0.2s; color: #2d3436; background: #fdfdfd;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #ff8c00; background: #fff; outline: none; }

        .btn-submit-modal { 
            font-family: 'Varela Round', sans-serif;
            width: 100%; padding: 14px; background: linear-gradient(135deg, #ff8c00 0%, #e67e00 100%); 
            color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; 
            margin-top: 10px; transition: 0.2s; font-size: 1rem;
        }
        .btn-submit-modal:hover { box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3); transform: translateY(-1px); }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <?php if ($isLockedStatus): ?>
        <div class="locked-banner">
            <i class="fa-solid fa-lock"></i>
            <div>
                Project is currently <strong><?= ($project['status_id'] == 4 ? 'Rejected' : ($project['status_id'] == 8 ? 'Completed' : 'Locked')) ?></strong>.
                Modifications are disabled.
            </div>
        </div>
    <?php endif; ?>

    <div class="page-header-flex">
        <div class="page-title">
            <h3>Key Performance Indicators</h3>
            <p>Track targets, measure progress, and ensure success.</p>
        </div>
        
        <?php if ($canManageKPIs): ?>
            <button onclick="openModal('addKPIModal')" class="btn-grad">
                <i class="fa-solid fa-plus"></i> Define New KPI
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($kpis)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-chart-pie empty-icon"></i>
            <h3>No KPIs Defined</h3>
            <p style="color:#95a5a6;">Define KPIs to start tracking project performance metrics.</p>
        </div>
    <?php else: ?>
        <div class="kpi-grid">
            <?php foreach ($kpis as $k): ?>
                <?php 
                    $percent = ($k['target_value'] > 0) ? min(100, round(($k['current_value'] / $k['target_value']) * 100)) : 0;
                    
                    // Logic for Status Code
                    $stCode = $k['status_id_code']; 
                    $statusClass = "status-" . $stCode;
                    
                    $avatar = $k['avatar'] ? BASE_URL.'assets/uploads/avatars/'.$k['avatar'] : BASE_URL.'assets/uploads/avatars/default-profile.png';
                    $isOwner = ($k['owner_id'] == $_SESSION['user_id']);
                    $canUpdateThisKPI = (!$isLockedStatus && ($canUpdateReadingGeneric || $isOwner));
                ?>
                <div class="kpi-card <?= $statusClass ?>">
                    
                    <div class="kpi-header">
                        <div class="kpi-icon"><i class="fa-solid fa-chart-simple"></i></div>
                        <span class="kpi-freq"><?= ucfirst($k['frequency']) ?></span>
                    </div>

                    <h3 class="kpi-title"><?= htmlspecialchars($k['name']) ?></h3>
                    <div class="kpi-desc">
                        <?= htmlspecialchars($k['description']) ?>
                        <?php if(!empty($k['data_source'])): ?>
                            <br><small style="color:#b2bec3;">Source: <?= htmlspecialchars($k['data_source']) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="metrics-box">
                        <div>
                            <span class="metric-label">Current</span>
                            <span class="metric-val"><?= $k['current_value'] ?></span>
                            <span class="metric-unit"><?= $k['unit'] ?></span>
                        </div>
                        <div>
                            <span class="metric-label" style="text-align:right;">Target</span>
                            <div class="metric-target"><?= $k['target_value'] ?> <?= $k['unit'] ?></div>
                        </div>
                    </div>

                    <div class="progress-wrapper">
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: <?= $percent ?>%;"></div>
                        </div>
                        <div class="progress-text">
                            <span><?= $k['status_name'] ?></span>
                            <span><?= $percent ?>%</span>
                        </div>
                    </div>

                    <div class="kpi-footer">
                        <div class="owner">
                            <img src="<?= $avatar ?>" alt="Owner">
                            <span><?= htmlspecialchars(explode(' ', $k['owner_name'])[0]) ?></span>
                        </div>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <?php if($canUpdateThisKPI): ?>
                                <button onclick="openUpdateModal(<?= $k['id'] ?>, '<?= htmlspecialchars($k['name'], ENT_QUOTES) ?>', <?= $k['current_value'] ?>)" class="btn-mini-update">Update</button>
                            <?php endif; ?>

                            <?php if($canManageKPIs): ?>
                                <a href="?id=<?= $id ?>&delete_kpi=<?= $k['id'] ?>" onclick="return confirm('Delete KPI?')" class="btn-icon-del"><i class="fa-solid fa-trash"></i></a>
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
                    <input type="text" name="name" required class="form-input" placeholder="e.g. Employee Satisfaction Score">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea" placeholder="Brief description..."></textarea>
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
                        <label class="form-label">Unit</label>
                        <input type="text" name="unit" required class="form-input" placeholder="%, SAR, #">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Source</label>
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
                <button type="submit" class="btn-submit-modal">Create KPI</button>
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
                <p id="updateKPIName" style="color:#636e72; margin-bottom:20px; font-weight:700; font-size:1.1rem; text-align:center;"></p>
                <input type="hidden" name="update_reading" value="1">
                <input type="hidden" name="kpi_id" id="updateKPIId">
                <div class="form-group">
                    <label class="form-label">New Current Value</label>
                    <input type="number" name="current_value" id="updateCurrentValue" step="0.01" required class="form-input" style="font-size:1.5rem; text-align:center; padding:15px; font-weight:bold; color:#2d3436;">
                </div>
                <button type="submit" class="btn-submit-modal">Save Update</button>
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

    // Close on outside click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
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
        if(msg == 'added') Toast.fire({icon: 'success', title: 'KPI Created Successfully'});
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Reading Updated'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'KPI Deleted'});
    <?php endif; ?>
</script>

</body>
</html>