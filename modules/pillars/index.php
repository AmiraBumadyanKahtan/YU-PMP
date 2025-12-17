<?php
// modules/pillars/index.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access Denied");

// ✅ إضافة هذا الجزء: تحديث إحصائيات جميع الركائز قبل العرض
$db = Database::getInstance()->pdo();
$allPillarIds = $db->query("SELECT id FROM pillars WHERE is_deleted=0")->fetchAll(PDO::FETCH_COLUMN);
foreach ($allPillarIds as $pId) {
    if (function_exists('updatePillarStatusAutomatic')) {
        updatePillarStatusAutomatic($pId);
    }
}

$pillars = getPillars(); // الآن سيجلب البيانات وهي محدثة
$stats = getPillarsStats(); 
// ...
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Pillars Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        body { background-color: #f8f9fa; }
        
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 1.8rem; color: #2c3e50; font-weight: 700; margin: 0; }
        .page-subtitle { color: #7f8c8d; margin-top: 5px; font-size: 0.95rem; }
        
        /* --- Stats Bar Design --- */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            margin-bottom: 35px; 
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s;
            border-bottom: 4px solid transparent;
        }
        .stat-card:hover { transform: translateY(-3px); }
        
        .stat-info h3 { margin: 0; font-size: 1.8rem; color: #2c3e50; font-weight: 800; }
        .stat-info p { margin: 5px 0 0; color: #7f8c8d; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .stat-icon-wrapper {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Colors for Stats */
        .stat-pillars { border-bottom-color: #3498db; }
        .stat-pillars .stat-icon-wrapper { background: #e3f2fd; color: #3498db; }
        
        .stat-members { border-bottom-color: #9b59b6; }
        .stat-members .stat-icon-wrapper { background: #f3e5f5; color: #9b59b6; }
        
        .stat-inits { border-bottom-color: #e67e22; }
        .stat-inits .stat-icon-wrapper { background: #fff3e0; color: #e67e22; }
        
        .stat-progress { border-bottom-color: #2ecc71; }
        .stat-progress .stat-icon-wrapper { background: #e8f5e9; color: #2ecc71; }


        /* --- Pillars Grid --- */
        .pillars-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 25px; }
        
        .pillar-card { 
            background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
            overflow: hidden; border-top: 5px solid #ccc; transition: all 0.3s ease;
            display: flex; flex-direction: column; position: relative;
        }
        .pillar-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        
        .card-header { padding: 20px 25px; border-bottom: 1px solid #f7f7f7; display: flex; justify-content: space-between; align-items: flex-start; }
        .pillar-num { font-size: 0.75rem; font-weight: bold; padding: 3px 10px; border-radius: 20px; color: #fff; margin-bottom: 8px; display: inline-block; text-transform: uppercase; }
        .pillar-title { font-size: 1.3rem; font-weight: 700; color: #34495e; margin: 0; line-height: 1.3; }
        .pillar-icon { font-size: 3rem; opacity: 0.1; position: absolute; right: 20px; top: 20px; }

        .card-body { padding: 25px; flex: 1; }
        
        .lead-section { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .lead-avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .lead-text div:first-child { font-weight: 700; color: #2c3e50; font-size: 0.95rem; }
        .lead-text div:last-child { font-size: 0.8rem; color: #95a5a6; }

        .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; font-size: 0.85rem; color: #555; }
        .meta-item { background: #f8f9fa; padding: 8px 12px; border-radius: 6px; display: flex; align-items: center; gap: 8px; }
        .meta-item i { color: #bdc3c7; }

        .progress-section { margin-bottom: 20px; }
        .progress-header { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 0.85rem; font-weight: 600; color: #555; }
        .progress-bar-bg { background: #ecf0f1; height: 8px; border-radius: 4px; overflow: hidden; }
        .progress-bar-fill { height: 100%; border-radius: 4px; transition: width 1s ease-in-out; }

        .objectives-preview { background: #fffcf5; padding: 15px; border-radius: 8px; border: 1px dashed #e0d0b0; }
        .obj-title { font-size: 0.85rem; font-weight: 700; color: #d35400; margin-bottom: 8px; display: block; }
        .obj-list { list-style: none; padding: 0; margin: 0; }
        .obj-list li { font-size: 0.85rem; color: #555; margin-bottom: 5px; display: flex; gap: 8px; line-height: 1.4; }
        .obj-list li::before { content: "•"; color: #d35400; font-weight: bold; }
        .obj-more { font-size: 0.8rem; color: #999; font-style: italic; margin-top: 5px; display: block; }

        .card-footer { padding: 15px 25px; background: #fdfdfd; border-top: 1px solid #f0f0f0; text-align: center; }
        .btn-view-inits { 
            background: #fff; border: 1px solid #ddd; color: #555; padding: 8px 20px; 
            border-radius: 30px; font-size: 0.9rem; font-weight: 600; cursor: pointer; 
            transition: 0.2s; width: 100%; display: block;
        }
        .btn-view-inits:hover { background: #3498db; color: #fff; border-color: #3498db; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 0; border-radius: 12px; width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 15px 40px rgba(0,0,0,0.3); animation: slideDown 0.3s; }
        .modal-header { padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fcfcfc; border-radius: 12px 12px 0 0; }
        .modal-header h3 { margin: 0; color: #2c3e50; }
        .modal-body { padding: 25px; }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .init-item { 
            padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; 
            display: flex; justify-content: space-between; align-items: center; transition: 0.2s;
            border-left: 4px solid transparent;
        }
        .init-item:hover { background: #f9f9f9; transform: translateX(5px); box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
        .close-btn { font-size: 1.5rem; cursor: pointer; color: #aaa; transition: 0.2s; }
        .close-btn:hover { color: #e74c3c; }
        .btn-manage-list {
        background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
        color: #fff;
        padding: 12px 25px;
        border-radius: 30px; /* حواف دائرية بالكامل */
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .btn-manage-list:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(44, 62, 80, 0.3);
        background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
    }

    .btn-manage-list i {
        font-size: 1.1rem;
    }

        @media (max-width: 1200px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } .pillars-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <div>
            <h1 class="page-title">Strategic Pillars Dashboard</h1>
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
                $avatar = $p['lead_avatar'] ? BASE_URL.'assets/uploads/avatars/'.$p['lead_avatar'] : BASE_URL.'assets/images/default-profile.png';
            ?>
            <div class="pillar-card" style="border-top-color: <?= $p['color'] ?>;">
                
                <i class="fa-solid <?= $p['icon'] ?> pillar-icon" style="color: <?= $p['color'] ?>;"></i>
                
                <div class="card-header">
                    <div>
                        <span class="pillar-num" style="background-color: <?= $p['color'] ?>;">Pillar #<?= $p['pillar_number'] ?></span>
                        <h3 class="pillar-title"><?= htmlspecialchars($p['name']) ?></h3>
                    </div>
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
                            <?= date('M Y', strtotime($p['start_date'])) ?> - <?= date('M Y', strtotime($p['end_date'])) ?>
                        </div>
                        <div class="meta-item">
                            <i class="fa-solid fa-layer-group"></i> 
                            <?= count($initiatives) ?> Initiatives
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
                            <span class="obj-title">Top Objectives</span>
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
                        Show Initiatives
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
            <h3 style="margin:0;" id="modalPillarTitle">Initiatives</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalInitiativesList"></div>
    </div>
</div>

<script>
    function showInitiatives(initiatives, pillarName, color) {
        document.getElementById('modalPillarTitle').innerText = pillarName + " - Initiatives";
        document.getElementById('modalPillarTitle').style.color = color;
        
        const list = document.getElementById('modalInitiativesList');
        list.innerHTML = '';

        if (initiatives.length === 0) {
            list.innerHTML = `
                <div style="text-align:center; padding:30px; color:#999;">
                    <i class="fa-solid fa-folder-open" style="font-size:2rem; margin-bottom:10px;"></i>
                    <p>No initiatives linked to this pillar yet.</p>
                </div>
            `;
        } else {
            initiatives.forEach(init => {
                const statusColor = init.status_color || '#ccc';
                const html = `
                    <div class="init-item" style="border-left-color: ${statusColor}">
                        <div>
                            <div style="font-weight:bold; color:#333; font-size:1.05rem;">${init.name}</div>
                            <div style="font-size:0.85rem; color:#777; margin-top:3px;">
                                <i class="fa-solid fa-user"></i> ${init.owner_name || 'N/A'} &nbsp;|&nbsp; 
                                <span style="color:${statusColor}; font-weight:600;">${init.status_name}</span>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:bold; font-size:1.1rem; color:#555;">${init.progress_percentage}%</div>
                            <a href="../initiatives/view.php?id=${init.id}" style="font-size:0.8rem; color:#3498db; text-decoration:none; display:inline-block; margin-top:5px;">View Details</a>
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