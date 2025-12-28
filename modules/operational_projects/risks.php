<?php
// modules/operational_projects/risks.php
require_once "php/risks_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Risk Register - <?= htmlspecialchars($project['name']) ?></title>
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

        /* --- Buttons --- */
        .btn-grad {
            font-family: 'Varela Round', sans-serif;
            background: linear-gradient(135deg, #ff8c00 0%, #e67e00 100%);
            color: white; border: none; padding: 12px 25px; border-radius: 50px;
            font-weight: 700; cursor: pointer; transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.25);
            font-size: 0.95rem; text-decoration: none;
        }
        .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 140, 0, 0.35); color: #fff; }

        /* --- Alerts --- */
        .locked-banner { 
            background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; 
            padding: 16px; border-radius: 12px; margin-bottom: 30px; 
            display: flex; align-items: center; gap: 12px; font-size: 0.95rem; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* --- Risk Grid --- */
        .risk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .risk-card { 
            background: #fff; border-radius: 16px; padding: 25px; 
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); border: 1px solid #f1f2f6; 
            position: relative; transition: all 0.3s ease; overflow: hidden;
            display: flex; flex-direction: column; justify-content: space-between;
        }
        .risk-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px -10px rgba(0,0,0,0.1); }
        
        /* Risk Level Indicator Strip */
        .risk-card::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px;
            background: #dfe6e9; transition: 0.3s;
        }
        
        .risk-low .risk-card::before { background: #2ecc71; }
        .risk-medium .risk-card::before { background: #f39c12; }
        .risk-high .risk-card::before { background: #e74c3c; }

        /* Header inside card */
        .risk-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; padding-left: 10px; }
        
        .risk-icon-box {
            width: 45px; height: 45px; border-radius: 12px; display: flex; 
            align-items: center; justify-content: center; font-size: 1.4rem;
        }
        .risk-low .risk-icon-box { background: #e8f5e9; color: #2ecc71; }
        .risk-medium .risk-icon-box { background: #fff3e0; color: #f39c12; }
        .risk-high .risk-icon-box { background: #ffebee; color: #e74c3c; }

        .risk-score-badge {
            font-size: 0.8rem; font-weight: 800; padding: 5px 10px; border-radius: 20px;
            display: inline-block; min-width: 35px; text-align: center;
        }
        .bg-low { background: #e8f5e9; color: #2ecc71; border: 1px solid #c8e6c9; }
        .bg-medium { background: #fff3e0; color: #f39c12; border: 1px solid #ffe0b2; }
        .bg-high { background: #ffebee; color: #e74c3c; border: 1px solid #ffcdd2; }

        /* Content */
        .risk-title { font-size: 1.1rem; font-weight: 700; color: #2d3436; margin: 0 0 10px 0; padding-left: 10px; line-height: 1.4; }
        
        .risk-matrix { 
            display: flex; gap: 15px; margin-bottom: 15px; padding-left: 10px;
        }
        .matrix-item {
            font-size: 0.75rem; color: #636e72; font-weight: 600; text-transform: uppercase;
            display: flex; flex-direction: column;
        }
        .matrix-val { font-size: 1.1rem; font-weight: 800; color: #2d3436; }

        .risk-body { font-size: 0.9rem; color: #636e72; padding-left: 10px; margin-bottom: 20px; }
        .risk-label { font-size: 0.75rem; font-weight: 700; color: #b2bec3; text-transform: uppercase; display: block; margin-bottom: 4px; margin-top: 10px; }
        .risk-text { line-height: 1.5; }

        /* Footer */
        .risk-footer { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; padding-left: 10px; 
        }
        .risk-status { 
            font-size: 0.75rem; font-weight: 700; text-transform: uppercase; 
            padding: 4px 10px; border-radius: 6px; background: #f1f2f6; color: #636e72; letter-spacing: 0.5px;
        }

        /* Actions */
        .btn-icon-edit { color: #b2bec3; font-size: 1rem; margin-right: 10px; cursor: pointer; transition: 0.2s; }
        .btn-icon-edit:hover { color: #ff8c00; }
        
        .btn-icon-del { color: #e74c3c; background: #fff0f0; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; display: inline-flex; }
        .btn-icon-del:hover { background: #e74c3c; color: #fff; }

        /* Empty State */
        .empty-state { text-align: center; padding: 80px 20px; background: #fff; border-radius: 16px; border: 2px dashed #e0e0e0; }
        .empty-icon { font-size: 3.5rem; color: #e2e8f0; margin-bottom: 20px; }

        /* --- Modal Styling --- */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(45, 52, 54, 0.6); backdrop-filter: blur(4px); }
        .modal-content { 
            background-color: #fff; margin: 4% auto; padding: 0; border-radius: 16px; 
            width: 600px; max-width: 90%; max-height: 90vh; overflow-y: auto; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
            animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        .modal-header { padding: 20px 30px; border-bottom: 1px solid #f1f2f6; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #2d3436; font-size: 1.2rem; font-weight: 800; }
        .close-btn { font-size: 1.5rem; color: #b2bec3; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #c0392b; }

        .modal-body { padding: 30px; }
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
            <h3>Risk Register</h3>
            <p>Identify, assess, and mitigate potential project risks.</p>
        </div>
        
        <?php if ($canEdit): ?>
            <button onclick="openModal()" class="btn-grad">
                <i class="fa-solid fa-triangle-exclamation"></i> Identify New Risk
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($risks)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-shield-halved empty-icon"></i>
            <h3>No Risks Identified</h3>
            <p style="color:#95a5a6;">Great job! No risks have been logged for this project yet.</p>
        </div>
    <?php else: ?>
        <div class="risk-grid">
            <?php foreach ($risks as $r): ?>
                <?php 
                    $score = $r['risk_score']; 
                    $scorePercent = round(($score / 25) * 100);
                    
                    $level = 'low'; 
                    if ($score >= 15) { $level = 'high'; }
                    elseif ($score >= 8) { $level = 'medium'; }
                    
                    $bgClass = 'bg-' . $level;
                ?>
                <div class="risk-card risk-<?= $level ?>">
                    
                    <div class="risk-header">
                        <div class="risk-icon-box">
                            <i class="fa-solid fa-bolt"></i>
                        </div>
                        <span class="risk-score-badge <?= $bgClass ?>"><?= $scorePercent ?>% Risk</span>
                    </div>

                    <h3 class="risk-title"><?= htmlspecialchars($r['title']) ?></h3>
                    
                    <div class="risk-matrix">
                        <div class="matrix-item">
                            <span>Probability</span>
                            <span class="matrix-val"><?= $r['probability'] ?>/5</span>
                        </div>
                        <div class="matrix-item">
                            <span>Impact</span>
                            <span class="matrix-val"><?= $r['impact'] ?>/5</span>
                        </div>
                    </div>

                    <div class="risk-body">
                        <span class="risk-label">Description</span>
                        <div class="risk-text">
                            <?= nl2br(htmlspecialchars(substr($r['description'], 0, 100))) ?><?= strlen($r['description']) > 100 ? '...' : '' ?>
                        </div>
                        
                        <span class="risk-label">Mitigation Plan</span>
                        <div class="risk-text">
                            <?= nl2br(htmlspecialchars(substr($r['mitigation_plan'], 0, 100))) ?><?= strlen($r['mitigation_plan']) > 100 ? '...' : '' ?>
                        </div>
                    </div>

                    <div class="risk-footer">
                        <span class="risk-status"><?= htmlspecialchars($r['status_name']) ?></span>
                        
                        <?php if($canEdit): ?>
                            <div>
                                <i class="fa-solid fa-pen btn-icon-edit" title="Edit" onclick="editRisk(this)"
                                   data-id="<?= $r['id'] ?>"
                                   data-title="<?= htmlspecialchars($r['title']) ?>"
                                   data-desc="<?= htmlspecialchars($r['description']) ?>"
                                   data-plan="<?= htmlspecialchars($r['mitigation_plan']) ?>"
                                   data-prob="<?= $r['probability'] ?>"
                                   data-imp="<?= $r['impact'] ?>"
                                   data-status="<?= $r['status_id'] ?>">
                                </i>
                                <a href="?id=<?= $id ?>&delete_risk=<?= $r['id'] ?>" onclick="return confirm('Delete this risk?')" class="btn-icon-del">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</div>

<div id="riskModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Identify New Risk</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="risk_id" id="r_id">
                
                <div class="form-group">
                    <label class="form-label">Risk Title</label>
                    <input type="text" name="title" id="r_title" required class="form-input" placeholder="e.g. Budget Overrun">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description (Cause & Effect)</label>
                    <textarea name="description" id="r_desc" rows="3" required class="form-textarea" placeholder="Detailed description of the risk..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mitigation Plan</label>
                    <textarea name="mitigation_plan" id="r_plan" rows="3" required class="form-textarea" placeholder="Action plan to mitigate this risk..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Probability (1-5)</label>
                        <select name="probability" id="r_prob" class="form-select">
                            <option value="1">1 - Rare</option>
                            <option value="2">2 - Unlikely</option>
                            <option value="3">3 - Possible</option>
                            <option value="4">4 - Likely</option>
                            <option value="5">5 - Almost Certain</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Impact (1-5)</label>
                        <select name="impact" id="r_imp" class="form-select">
                            <option value="1">1 - Negligible</option>
                            <option value="2">2 - Minor</option>
                            <option value="3">3 - Moderate</option>
                            <option value="4">4 - Major</option>
                            <option value="5">5 - Catastrophic</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" id="status_div" style="display:none;">
                    <label class="form-label">Status</label>
                    <select name="status_id" id="r_status" class="form-select">
                        <?php foreach($statuses as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['status_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn-submit-modal">Save Risk</button>
            </div>
        </form>
    </div>
</div>

<script src="js/risks.js"></script>
<script>
    <?php if(isset($_GET['msg'])): ?>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        const msg = "<?= $_GET['msg'] ?>";
        if(msg == 'added') Toast.fire({icon: 'success', title: 'Risk Identified'});
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Risk Updated'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Risk Deleted'});
    <?php endif; ?>
</script>

</body>
</html>