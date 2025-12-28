<?php
// modules/pillars/index.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access Denied");

// ✅ تحديث إحصائيات جميع الركائز قبل العرض
$db = Database::getInstance()->pdo();
$allPillarIds = $db->query("SELECT id FROM pillars WHERE is_deleted=0")->fetchAll(PDO::FETCH_COLUMN);
foreach ($allPillarIds as $pId) {
    if (function_exists('updatePillarStatusAutomatic')) {
        updatePillarStatusAutomatic($pId);
    }
}

$pillars = getPillars(); 
$stats = getPillarsStats(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Pillars Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <div>
            <h1 class="page-title">Strategic Pillars</h1>
            <p class="page-subtitle">Overview of strategic pillars, objectives, and progress.</p>
        </div>
        <div>
            <?php if(Auth::can('view_pillars')): ?>
                <a href="list.php" class="btn-manage-list">
                    <i class="fa-solid fa-list-check"></i> <span>Manage Pillars</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card stat-pillars">
            <div class="stat-info">
                <h3><?= $stats['pillars'] ?></h3>
                <p>Total Pillars</p>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-bullseye"></i>
            </div>
        </div>

        <div class="stat-card stat-members">
            <div class="stat-info">
                <h3><?= $stats['members'] ?></h3>
                <p>Total Members</p>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="stat-card stat-inits">
            <div class="stat-info">
                <h3><?= $stats['initiatives'] ?></h3>
                <p>Linked Initiatives</p>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-rocket"></i>
            </div>
        </div>

        <div class="stat-card stat-progress">
            <div class="stat-info">
                <h3><?= $stats['avg_progress'] ?>%</h3>
                <p>Avg. Progress</p>
            </div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
        </div>
    </div>

    <div class="pillars-grid">
        <?php foreach ($pillars as $p): ?>
            <?php 
                $objectives = getPillarObjectives($p['id']);
                $initiatives = getPillarInitiatives($p['id']);
                $avatar = $p['lead_avatar'] ? BASE_URL.'assets/uploads/avatars/'.$p['lead_avatar'] : BASE_URL.'assets/uploads/avatars/default-profile.png';
            ?>
            <div class="pillar-card" style="border-top-color: <?= $p['color'] ?>;">
                
                <i class="fa-solid <?= $p['icon'] ?> pillar-bg-icon" style="color: <?= $p['color'] ?>;"></i>
                
                <div class="card-header">
                    <span class="pillar-num" style="background-color: <?= $p['color'] ?>;">Pillar #<?= $p['pillar_number'] ?></span>
                    <h3 class="pillar-title"><?= htmlspecialchars($p['name']) ?></h3>
                </div>
                
                <div class="card-body">
                    <div class="lead-section">
                        <img src="<?= $avatar ?>" class="lead-avatar" alt="Lead">
                        <div class="lead-text">
                            <div><?= htmlspecialchars($p['lead_name'] ?? 'Unassigned') ?></div>
                            <div>Pillar Lead</div>
                        </div>
                    </div>

                    <div class="meta-grid">
                        <div class="meta-item">
                            <i class="fa-regular fa-calendar"></i> 
                            <?= date('M Y', strtotime($p['end_date'])) ?>
                        </div>
                        <div class="meta-item">
                            <i class="fa-solid fa-layer-group"></i> 
                            <?= count($initiatives) ?> Inits
                        </div>
                    </div>

                    <div class="progress-section">
                        <div class="progress-header">
                            <span>Completion</span>
                            <span><?= $p['progress_percentage'] ?>%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?= $p['progress_percentage'] ?>%; background-color: <?= $p['color'] ?>;"></div>
                        </div>
                    </div>

                    <?php if (!empty($objectives)): ?>
                        <div class="objectives-preview">
                            <span class="obj-title">Key Objectives</span>
                            <ul class="obj-list">
                                <?php foreach(array_slice($objectives, 0, 2) as $obj): ?>
                                    <li><?= htmlspecialchars($obj['objective_text']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if(count($objectives) > 2): ?>
                                <span class="obj-more">+<?= count($objectives)-2 ?> more objectives...</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <button class="btn-view-inits" onclick='showInitiatives(<?= json_encode($initiatives) ?>, "<?= htmlspecialchars($p['name']) ?>", "<?= $p['color'] ?>")'>
                        View Initiatives <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

<div id="initiativesModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalPillarTitle">Initiatives</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalInitiativesList"></div>
    </div>
</div>

<script>
    function showInitiatives(initiatives, pillarName, color) {
        document.getElementById('modalPillarTitle').innerText = pillarName;
        document.getElementById('modalPillarTitle').style.color = color;
        
        const list = document.getElementById('modalInitiativesList');
        list.innerHTML = '';

        if (initiatives.length === 0) {
            list.innerHTML = `
                <div style="text-align:center; padding:50px; color:#b2bec3;">
                    <i class="fa-regular fa-folder-open" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
                    <p style="font-weight:600;">No initiatives linked to this pillar yet.</p>
                </div>
            `;
        } else {
            initiatives.forEach(init => {
                const statusColor = init.status_color || '#b2bec3';
                const html = `
                    <div class="init-item" style="border-left-color: ${statusColor}">
                        <div>
                            <div style="font-weight:800; color:#2d3436; font-size:1.1rem; margin-bottom:5px;">${init.name}</div>
                            <div style="font-size:0.85rem; color:#636e72;">
                                <i class="fa-solid fa-user-circle" style="color:#b2bec3; margin-right:5px;"></i> ${init.owner_name || 'N/A'} 
                                &nbsp;&bull;&nbsp; 
                                <span style="color:${statusColor}; font-weight:700; text-transform:uppercase; font-size:0.75rem;">${init.status_name}</span>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:900; font-size:1.4rem; color:${statusColor}; line-height:1;">${init.progress_percentage}%</div>
                            <a href="../initiatives/view.php?id=${init.id}" style="font-size:0.8rem; color:#0984e3; text-decoration:none; font-weight:700; display:inline-block; margin-top:5px;">
                                Details <i class="fa-solid fa-chevron-right" style="font-size:0.7rem;"></i>
                            </a>
                        </div>
                    </div>
                `;
                list.insertAdjacentHTML('beforeend', html);
            });
        }
        document.getElementById('initiativesModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('initiativesModal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('initiativesModal')) {
            closeModal();
        }
    }
</script>

</body>
</html>