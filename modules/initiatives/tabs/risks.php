<?php
// modules/initiatives/tabs/risks.php

// 1. الصلاحيات
$canManageRisks = ($isOwner || $isSuper || Auth::can('manage_initiative_risks')) && !$isLocked;

// 2. معالجة الإضافة (Add Risk)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_risk']) && $canManageRisks) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $prob = $_POST['probability'];
    $impact = $_POST['impact'];
    $mitigation = $_POST['mitigation_plan'];
    
    // Status ID 1 = Identified (Default)
    $stmt = $db->prepare("
        INSERT INTO risk_assessments (
            parent_type, parent_id, title, description, mitigation_plan, 
            probability, impact, status_id, identified_date, created_at
        ) VALUES (
            'initiative', ?, ?, ?, ?, 
            ?, ?, 1, CURDATE(), NOW()
        )
    ");
    $stmt->execute([$id, $title, $desc, $mitigation, $prob, $impact]);
    
    echo "<script>window.location.href='view.php?id=$id&tab=risks&msg=risk_added';</script>";
}

// 3. معالجة تحديث الحالة (Update Status)
if (isset($_GET['close_risk']) && $canManageRisks) {
    $rId = $_GET['close_risk'];
    $db->prepare("UPDATE risk_assessments SET status_id = 4, updated_at = NOW() WHERE id = ?")->execute([$rId]); // 4 = Closed
    echo "<script>window.location.href='view.php?id=$id&tab=risks&msg=risk_closed';</script>";
}

// 4. جلب البيانات
$risks = $db->prepare("
    SELECT r.*, s.status_name 
    FROM risk_assessments r
    JOIN risk_statuses s ON s.id = r.status_id
    WHERE r.parent_type = 'initiative' AND r.parent_id = ?
    ORDER BY r.risk_score DESC
");
$risks->execute([$id]);
$riskList = $risks->fetchAll(PDO::FETCH_ASSOC);

// إحصائيات للمصفوفة
$highRisks = 0; $medRisks = 0; $lowRisks = 0;
foreach($riskList as $r) {
    if ($r['status_id'] != 4) { // Don't count closed risks
        if ($r['risk_score'] >= 15) $highRisks++;
        elseif ($r['risk_score'] >= 8) $medRisks++;
        else $lowRisks++;
    }
}
?>

<style>
    /* --- Risk Cards --- */
    .risk-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 25px; }
    
    .risk-card { 
        background: #fff; border: 1px solid #f0f2f5; border-radius: 16px; padding: 25px; 
        position: relative; transition: all 0.3s ease; overflow: hidden;
        border-top: 5px solid #ccc; /* Default */
    }
    .risk-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    
    /* Risk Levels Colors */
    .risk-high { border-top-color: #e74c3c; }
    .risk-med { border-top-color: #f39c12; }
    .risk-low { border-top-color: #2ecc71; }
    .risk-closed { border-top-color: #95a5a6; opacity: 0.7; }
    
    .r-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
    .r-title { font-size: 1.1rem; font-weight: 800; color: #2d3436; margin: 0; }
    
    .r-badge { font-size: 0.7rem; padding: 4px 10px; border-radius: 12px; font-weight: 700; text-transform: uppercase; color: #fff; }
    .bg-high { background: #e74c3c; }
    .bg-med { background: #f39c12; }
    .bg-low { background: #2ecc71; }
    .bg-closed { background: #95a5a6; }

    .r-score-box { 
        display: flex; gap: 5px; margin-bottom: 15px; background: #f9f9f9; 
        padding: 10px; border-radius: 8px; justify-content: center;
    }
    .sc-item { text-align: center; flex: 1; }
    .sc-lbl { font-size: 0.65rem; color: #aaa; text-transform: uppercase; display: block; }
    .sc-val { font-size: 1.1rem; font-weight: 800; color: #555; }

    .r-desc { color: #636e72; font-size: 0.9rem; margin-bottom: 15px; line-height: 1.5; }
    .r-mitigation { background: #f0f8ff; border-left: 3px solid #3498db; padding: 10px; font-size: 0.85rem; color: #2c3e50; border-radius: 0 4px 4px 0; }

    .r-footer { 
        margin-top: 20px; padding-top: 15px; border-top: 1px dashed #eee; 
        display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #999;
    }
    .btn-close-risk { color: #27ae60; cursor: pointer; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 5px; }
    .btn-close-risk:hover { text-decoration: underline; }

    /* --- Matrix Summary --- */
    .matrix-summary { 
        display: flex; gap: 20px; margin-bottom: 30px; 
        background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #f0f2f5;
    }
    .mx-item { flex: 1; display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 12px; }
    .mx-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #fff; }
    .mx-high { background: #ffebee; } .mx-high .mx-icon { background: #e74c3c; }
    .mx-med { background: #fff8e1; } .mx-med .mx-icon { background: #f39c12; }
    .mx-low { background: #e8f5e9; } .mx-low .mx-icon { background: #2ecc71; }

    .mx-text div:first-child { font-size: 0.8rem; color: #7f8c8d; font-weight: 700; text-transform: uppercase; }
    .mx-text div:last-child { font-size: 1.5rem; font-weight: 900; color: #2d3436; }

    /* --- Modal --- */
    /* Reuse modal styles from previous tabs */
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Risk Management</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Identify, assess, and mitigate risks.</p>
        </div>
        <?php if($canManageRisks): ?>
            <button onclick="openRiskModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-shield-virus"></i> Identify Risk
            </button>
        <?php endif; ?>
    </div>

    <div class="matrix-summary">
        <div class="mx-item mx-high">
            <div class="mx-icon"><i class="fa-solid fa-fire"></i></div>
            <div class="mx-text">
                <div>High Risks</div>
                <div><?= $highRisks ?></div>
            </div>
        </div>
        <div class="mx-item mx-med">
            <div class="mx-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="mx-text">
                <div>Medium Risks</div>
                <div><?= $medRisks ?></div>
            </div>
        </div>
        <div class="mx-item mx-low">
            <div class="mx-icon"><i class="fa-solid fa-check-shield"></i></div>
            <div class="mx-text">
                <div>Low Risks</div>
                <div><?= $lowRisks ?></div>
            </div>
        </div>
    </div>

    <div class="risk-grid">
        <?php if(empty($riskList)): ?>
            <div style="grid-column:1/-1; text-align:center; padding:40px; color:#ccc;">
                <i class="fa-solid fa-clipboard-check" style="font-size:3rem; margin-bottom:10px;"></i>
                <p>No risks identified yet. Good job!</p>
            </div>
        <?php else: ?>
            <?php foreach($riskList as $r): 
                $score = $r['risk_score'];
                $levelClass = 'risk-low'; $bgClass = 'bg-low'; $levelTxt = 'Low';
                
                if($r['status_id'] == 4) { // Closed
                    $levelClass = 'risk-closed'; $bgClass = 'bg-closed'; $levelTxt = 'Closed';
                } elseif($score >= 15) {
                    $levelClass = 'risk-high'; $bgClass = 'bg-high'; $levelTxt = 'High';
                } elseif($score >= 8) {
                    $levelClass = 'risk-med'; $bgClass = 'bg-med'; $levelTxt = 'Medium';
                }
            ?>
            <div class="risk-card <?= $levelClass ?>">
                <div class="r-header">
                    <div class="r-title"><?= htmlspecialchars($r['title']) ?></div>
                    <span class="r-badge <?= $bgClass ?>"><?= $levelTxt ?></span>
                </div>

                <div class="r-score-box">
                    <div class="sc-item">
                        <span class="sc-val"><?= $r['probability'] ?></span>
                        <span class="sc-lbl">Prob.</span>
                    </div>
                    <div style="display:flex; align-items:center; color:#ddd;">&times;</div>
                    <div class="sc-item">
                        <span class="sc-val"><?= $r['impact'] ?></span>
                        <span class="sc-lbl">Impact</span>
                    </div>
                    <div style="display:flex; align-items:center; color:#ddd;">=</div>
                    <div class="sc-item">
                        <span class="sc-val" style="color:#2c3e50;"><?= $score ?></span>
                        <span class="sc-lbl">Score</span>
                    </div>
                </div>

                <div class="r-desc"><?= htmlspecialchars($r['description']) ?></div>
                
                <?php if($r['mitigation_plan']): ?>
                    <div class="r-mitigation">
                        <strong><i class="fa-solid fa-umbrella"></i> Mitigation:</strong><br>
                        <?= htmlspecialchars($r['mitigation_plan']) ?>
                    </div>
                <?php endif; ?>

                <div class="r-footer">
                    <span><?= date('d M Y', strtotime($r['identified_date'])) ?></span>
                    <?php if($canManageRisks && $r['status_id'] != 4): ?>
                        <a href="view.php?id=<?= $id ?>&tab=risks&close_risk=<?= $r['id'] ?>" class="btn-close-risk" onclick="return confirm('Mark this risk as closed/resolved?')">
                            <i class="fa-regular fa-circle-check"></i> Close
                        </a>
                    <?php else: ?>
                        <span><?= $r['status_name'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if($canManageRisks): ?>
<div id="addRiskModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fa-solid fa-triangle-exclamation" style="color:#e74c3c;"></i> Identify Risk
            </h3>
            <div class="modal-close" onclick="closeRiskModal()">&times;</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="add_risk" value="1">
            <div class="modal-body">
                
                <div class="form-row">
                    <label class="form-lbl">Risk Title <span style="color:#e74c3c">*</span></label>
                    <input type="text" name="title" class="form-input" required placeholder="e.g. Budget Overrun">
                </div>

                <div class="form-grid-2">
                    <div>
                        <label class="form-lbl">Probability (1-5)</label>
                        <select name="probability" id="prob" class="form-input" onchange="calcRiskScore()">
                            <option value="1">1 - Rare</option>
                            <option value="2">2 - Unlikely</option>
                            <option value="3">3 - Possible</option>
                            <option value="4">4 - Likely</option>
                            <option value="5">5 - Almost Certain</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-lbl">Impact (1-5)</label>
                        <select name="impact" id="imp" class="form-input" onchange="calcRiskScore()">
                            <option value="1">1 - Insignificant</option>
                            <option value="2">2 - Minor</option>
                            <option value="3">3 - Moderate</option>
                            <option value="4">4 - Major</option>
                            <option value="5">5 - Severe</option>
                        </select>
                    </div>
                </div>

                <div style="text-align:center; margin-bottom:20px; background:#f9f9f9; padding:10px; border-radius:8px;">
                    <span style="font-size:0.9rem; color:#7f8c8d;">Calculated Risk Score:</span>
                    <strong id="risk_score_display" style="font-size:1.5rem; color:#2c3e50; margin-left:10px;">1</strong>
                    <span id="risk_level_display" style="font-size:0.8rem; padding:3px 8px; border-radius:10px; background:#2ecc71; color:#fff; margin-left:10px;">Low</span>
                </div>

                <div class="form-row">
                    <label class="form-lbl">Description</label>
                    <textarea name="description" class="form-input" style="height:80px;" placeholder="Describe the risk..."></textarea>
                </div>

                <div class="form-row" style="margin-bottom:0;">
                    <label class="form-lbl">Mitigation Plan</label>
                    <textarea name="mitigation_plan" class="form-input" style="height:80px;" placeholder="How will you handle this risk?"></textarea>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRiskModal()">Cancel</button>
                <button type="submit" class="btn-save" style="background:#e74c3c; box-shadow:0 4px 10px rgba(231,76,60,0.3);">Add Risk</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRiskModal() { document.getElementById('addRiskModal').style.display = 'flex'; }
    function closeRiskModal() { document.getElementById('addRiskModal').style.display = 'none'; }

    function calcRiskScore() {
        let p = parseInt(document.getElementById('prob').value);
        let i = parseInt(document.getElementById('imp').value);
        let score = p * i;
        
        let display = document.getElementById('risk_score_display');
        let level = document.getElementById('risk_level_display');
        
        display.innerText = score;
        
        if (score >= 15) {
            level.innerText = 'High';
            level.style.background = '#e74c3c';
        } else if (score >= 8) {
            level.innerText = 'Medium';
            level.style.background = '#f39c12';
        } else {
            level.innerText = 'Low';
            level.style.background = '#2ecc71';
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addRiskModal')) closeRiskModal();
    }
</script>
<?php endif; ?>