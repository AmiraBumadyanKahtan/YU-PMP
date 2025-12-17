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
    <link rel="stylesheet" href="css/risks.css">
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
        <button onclick="openModal()" class="btn-primary">
            <i class="fa-solid fa-triangle-exclamation"></i> Identify New Risk
        </button>
    </div>
    <?php endif; ?>

    <?php if (empty($risks)): ?>
        <div style="text-align:center; padding:60px; background:#fff; border-radius:12px; border:2px dashed #eee;">
            <i class="fa-solid fa-shield-halved" style="font-size:3rem; color:#e0e0e0; margin-bottom:15px;"></i>
            <p style="color:#888; font-size:1.1rem;">No risks identified yet. Good job!</p>
        </div>
    <?php else: ?>
        <div class="risk-grid">
            <?php foreach ($risks as $r): ?>
                <?php 
                    $score = $r['risk_score']; 
                    // -----------------------------------------------------
                    // التعديل هنا: تحويل الدرجة إلى نسبة مئوية
                    // Max Score = 25 (5*5)
                    $scorePercent = round(($score / 25) * 100);
                    // -----------------------------------------------------
                    
                    $level = 'low'; $bgClass = 'bg-low';
                    if ($score >= 15) { $level = 'high'; $bgClass = 'bg-high'; }
                    elseif ($score >= 8) { $level = 'medium'; $bgClass = 'bg-medium'; }
                ?>
                <div class="risk-card risk-<?= $level ?>">
                    <div class="risk-header">
                        <h3 class="risk-title"><?= htmlspecialchars($r['title']) ?></h3>
                        <span class="risk-score-badge <?= $bgClass ?>"><?= $scorePercent ?>%</span>
                    </div>
                    
                    <div class="risk-matrix">
                        <div>Prob: <span><?= $r['probability'] ?></span>/5</div>
                        <div>Imp: <span><?= $r['impact'] ?></span>/5</div>
                    </div>

                    <div class="risk-body">
                        <span class="risk-label">Description:</span>
                        <?= nl2br(htmlspecialchars(substr($r['description'], 0, 100))) ?>...
                        
                        <span class="risk-label">Mitigation:</span>
                        <?= nl2br(htmlspecialchars(substr($r['mitigation_plan'], 0, 100))) ?>...
                    </div>

                    <div class="risk-footer">
                        <span class="risk-status"><?= htmlspecialchars($r['status_name']) ?></span>
                        <?php if($canEdit): ?>
                            <div>
                                <i class="fa-solid fa-pen btn-action" title="Edit" onclick="editRisk(this)"
                                   data-id="<?= $r['id'] ?>"
                                   data-title="<?= htmlspecialchars($r['title']) ?>"
                                   data-desc="<?= htmlspecialchars($r['description']) ?>"
                                   data-plan="<?= htmlspecialchars($r['mitigation_plan']) ?>"
                                   data-prob="<?= $r['probability'] ?>"
                                   data-imp="<?= $r['impact'] ?>"
                                   data-status="<?= $r['status_id'] ?>">
                                </i>
                                <a href="?id=<?= $id ?>&delete_risk=<?= $r['id'] ?>" onclick="return confirm('Delete this risk?')" class="btn-action" style="color:#e74c3c;">
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
                    <input type="text" name="title" id="r_title" required class="form-input" placeholder="Short descriptive title">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description (Cause & Effect)</label>
                    <textarea name="description" id="r_desc" rows="3" required class="form-textarea"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mitigation Plan</label>
                    <textarea name="mitigation_plan" id="r_plan" rows="3" required class="form-textarea" placeholder="What will we do to prevent or handle it?"></textarea>
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
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal()" class="btn-secondary" style="margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-primary">Save Risk</button>
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
        if(msg == 'added') Toast.fire({icon: 'success', title: 'Risk Added'});
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Risk Updated'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Risk Deleted'});
    <?php endif; ?>
</script>

</body>
</html>