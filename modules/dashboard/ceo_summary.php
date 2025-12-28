<?php
// modules/dashboard/ceo_summary.php

require_once "../../core/config.php";
require_once "../../core/auth.php";

// 1. التحقق من الصلاحية
if (!in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office'])) {
    die("Access Denied");
}

$db = Database::getInstance()->pdo();

// ==========================================================
// 1. QUICK STATS (Top Row)
// ==========================================================
$totalProjects = $db->query("SELECT COUNT(*) FROM operational_projects WHERE is_deleted=0")->fetchColumn();
$totalEmployees = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1 AND is_deleted=0")->fetchColumn();
$totalRisksOpen = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE status_id NOT IN (3,4)")->fetchColumn();
$financeStats = $db->query("SELECT SUM(spent_budget) as spent FROM operational_projects WHERE is_deleted=0")->fetch(PDO::FETCH_ASSOC);
$totalSpentFormatted = number_format(($financeStats['spent'] ?? 0) / 1000, 1) . 'K';


// ==========================================================
// 2. HEALTH SCORE LOGIC
// ==========================================================
$avgProg = $db->query("SELECT AVG(progress_percentage) FROM operational_projects WHERE status_id=6 AND is_deleted=0")->fetchColumn() ?: 0;
$scoreProg = ($avgProg / 100) * 40;

$finData = $db->query("SELECT SUM(approved_budget) as appr, SUM(spent_budget) as spent FROM operational_projects WHERE is_deleted=0")->fetch(PDO::FETCH_ASSOC);
$spentRatio = ($finData['appr'] > 0) ? ($finData['spent'] / $finData['appr']) : 0;
$scoreBudget = ($spentRatio <= 1) ? 30 : max(0, 30 - (($spentRatio - 1) * 100)); 

$criticalRisksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE risk_score >= 20 AND status_id NOT IN (3,4)")->fetchColumn();
$scoreRisk = max(0, 30 - ($criticalRisksCount * 5)); 

$healthScore = round($scoreProg + $scoreBudget + $scoreRisk);
$healthColor = ($healthScore >= 80) ? '#2ecc71' : (($healthScore >= 50) ? '#f39c12' : '#e74c3c'); // Green, Orange, Red
$healthLabel = ($healthScore >= 80) ? 'Excellent' : (($healthScore >= 50) ? 'Average' : 'Critical');


// ==========================================================
// 3. CRITICAL ALERTS
// ==========================================================
$alerts = [];
$late = $db->query("SELECT id, name, end_date, manager_id FROM operational_projects WHERE end_date < CURDATE() AND status_id NOT IN (4,8) AND is_deleted=0 LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach($late as $p) {
    $days = (strtotime(date('Y-m-d')) - strtotime($p['end_date'])) / (60 * 60 * 24);
    $mgrName = $db->query("SELECT full_name_en FROM users WHERE id=".$p['manager_id'])->fetchColumn() ?: 'N/A';
    $alerts[] = ['type'=>'delay', 'icon'=>'fa-clock', 'color'=>'#e74c3c', 'bg'=>'#fadbd8', 
                 'title'=>'Project Delayed', 'msg'=>"<b>{$p['name']}</b> (Mgr: $mgrName) is overdue by ".round($days)." days.", 'link'=>"../operational_projects/view.php?id={$p['id']}"];
}
$risks = $db->query("SELECT r.title, p.name as proj_name, p.id as pid FROM risk_assessments r JOIN operational_projects p ON p.id = r.parent_id WHERE r.risk_score >= 20 AND r.status_id NOT IN (3,4) LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach($risks as $r) {
    $alerts[] = ['type'=>'risk', 'icon'=>'fa-fire', 'color'=>'#f39c12', 'bg'=>'#fdebd0', 
                 'title'=>'Critical Risk', 'msg'=>"Risk <b>'{$r['title']}'</b> in <b>{$r['proj_name']}</b> needs immediate mitigation.", 'link'=>"../operational_projects/view.php?id={$r['pid']}"];
}


// ==========================================================
// 4. TOP DEPARTMENTS
// ==========================================================
$topDepts = $db->query("
    SELECT d.name, 
           AVG(p.progress_percentage) as avg_prog, 
           COUNT(p.id) as projects,
           SUM(p.approved_budget) as budget,
           SUM(p.spent_budget) as spent
    FROM departments d
    JOIN operational_projects p ON p.department_id = d.id
    WHERE p.is_deleted=0 AND p.status_id IN (2,6,8)
    GROUP BY d.name
    ORDER BY avg_prog DESC
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);


// ==========================================================
// 5. RESOURCE WORKLOAD
// ==========================================================
$busyUsers = $db->query("
    SELECT u.full_name_en, u.avatar, 
           COUNT(t.id) as total_tasks,
           SUM(CASE WHEN t.status_id = 3 THEN 1 ELSE 0 END) as completed_tasks
    FROM users u
    JOIN project_tasks t ON t.assigned_to = u.id
    WHERE t.is_deleted=0 AND u.is_deleted=0
    GROUP BY u.id
    ORDER BY total_tasks DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 6. TASKS STATS
// ==========================================================
$taskStats = $db->query("
    SELECT 
        SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as completed
    FROM project_tasks WHERE is_deleted=0
")->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CEO Dashboard - Summary</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Premium Theme (Matches Org & Projects Pages) --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* Header */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }
        .page-subtitle { margin: 5px 0 0; color: #7f8c8d; font-size: 0.95rem; }

        /* Tabs */
        .tabs-nav { background: #fff; padding: 5px; border-radius: 50px; display: inline-flex; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .tabs-nav a { padding: 10px 25px; text-decoration: none; color: #7f8c8d; border-radius: 40px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
        .tabs-nav a:hover { color: #ff8c00; }
        .tabs-nav a.active { background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3); }

        /* Quick Stats Row */
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .mini-stat { background: #fff; padding: 20px; border-radius: 14px; border: 1px solid #f9f9f9; box-shadow: 0 4px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s; }
        .mini-stat:hover { transform: translateY(-5px); }
        
        .mini-label { font-size: 0.8rem; color: #95a5a6; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
        .mini-value { font-size: 1.8rem; font-weight: 800; color: #2c3e50; }
        .mini-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        
        .icon-blue { background: #e3f2fd; color: #3498db; }
        .icon-green { background: #d5f5e3; color: #2ecc71; }
        .icon-purple { background: #f4ecf7; color: #9b59b6; }
        .icon-orange { background: #fdebd0; color: #e67e22; }

        /* Health Score Hero */
        .health-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: #fff;
            padding: 30px; border-radius: 20px; margin-bottom: 30px; position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 10px 30px -10px rgba(44, 62, 80, 0.4);
        }
        .health-info h2 { margin: 0 0 5px 0; font-size: 2.5rem; color: <?= $healthColor ?>; font-weight: 800; }
        .health-info p { color: #bdc3c7; font-size: 0.95rem; max-width: 500px; line-height: 1.6; }
        
        .gauge-wrapper { width: 130px; height: 130px; position: relative; }
        .gauge-svg { transform: rotate(-90deg); width: 100%; height: 100%; }
        .gauge-bg { fill: none; stroke: rgba(255,255,255,0.1); stroke-width: 10; }
        .gauge-val { 
            fill: none; stroke: <?= $healthColor ?>; stroke-width: 10; stroke-linecap: round; 
            stroke-dasharray: 283; stroke-dashoffset: <?= 283 - (283 * $healthScore / 100) ?>; 
            transition: stroke-dashoffset 1.5s ease;
        }
        .gauge-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 2rem; font-weight: 800; color: #fff; }

        /* Main Grid */
        .summary-grid { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 25px; margin-bottom: 30px; }
        @media (max-width: 1100px) { .summary-grid { grid-template-columns: 1fr; } }

        /* Standard Cards */
        .card { background: #fff; border-radius: 14px; border: 1px solid #f9f9f9; box-shadow: 0 4px 8px rgba(0,0,0,0.04); padding: 25px; height: 93%; display: flex; flex-direction: column; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f9f9f9; padding-bottom: 15px; }
        .card-title { font-size: 1.1rem; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 10px; }

        /* Alerts */
        .alert-item { display: flex; gap: 15px; padding: 15px; border-radius: 10px; margin-bottom: 12px; background: #fff; border: 1px solid #f0f0f0; transition: 0.2s; text-decoration: none; color: inherit; }
        .alert-item:hover { transform: translateX(5px); border-color: #ff8c00; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .alert-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .alert-content h4 { margin: 0 0 4px 0; font-size: 0.95rem; font-weight: 700; color: #2c3e50; }
        .alert-content p { margin: 0; font-size: 0.85rem; color: #7f8c8d; }

        /* Dept Table */
        .dept-table { width: 100%; border-collapse: collapse; }
        .dept-table td { padding: 12px 5px; border-bottom: 1px dashed #f0f0f0; vertical-align: middle; }
        .dept-table tr:last-child td { border-bottom: none; }
        .dept-rank { font-weight: 700; color: #bdc3c7; font-size: 0.9rem; margin-right: 10px; }
        .dept-rank.top { color: #f39c12; }

        /* Resource Item */
        .res-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f9f9f9; }
        .res-user { display: flex; align-items: center; gap: 10px; }
        .res-user img { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .res-stats { text-align: right; font-size: 0.85rem; }

        /* Task Overview Bar */
        .task-bar-container { display: flex; height: 50px; border-radius: 12px; overflow: hidden; margin-top: 20px; }
        .task-segment { display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 0.9rem; transition: width 0.5s ease; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-chart-line"></i> Executive Summary</h1>
            <p class="page-subtitle">Real-time pulse check of organizational performance and health.</p>
        </div>
        <div class="tabs-nav">
            <a href="ceo_org.php">Organization</a>
            <a href="ceo_projects.php">Projects & Ops</a>
            <a href="../reports/ceo_updates.php">Project Updates</a>
            <a href="ceo_summary.php" class="active">Summary</a>
        </div>
    </div>

    <div class="stats-row">
        <div class="mini-stat">
            <div><div class="mini-label">Total Projects</div><div class="mini-value"><?= $totalProjects ?></div></div>
            <div class="mini-icon icon-blue"><i class="fa-solid fa-folder-open"></i></div>
        </div>
        <div class="mini-stat">
            <div><div class="mini-label">Total Spent</div><div class="mini-value"><?= $totalSpentFormatted ?></div></div>
            <div class="mini-icon icon-green"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="mini-stat">
            <div><div class="mini-label">Active Staff</div><div class="mini-value"><?= $totalEmployees ?></div></div>
            <div class="mini-icon icon-purple"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="mini-stat">
            <div><div class="mini-label">Open Risks</div><div class="mini-value"><?= $totalRisksOpen ?></div></div>
            <div class="mini-icon icon-orange"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
    </div>

    <div class="health-card">
        <div class="health-info">
            <div style="font-weight:700; text-transform:uppercase; color:#95a5a6; font-size:0.85rem; letter-spacing:1px; margin-bottom:5px;">Overall Health Score</div>
            <h2><?= $healthLabel ?></h2>
            <p>Score is calculated based on weighted project completion (40%), budget adherence (30%), and critical risk exposure (30%). Current performance is <strong><?= $healthScore ?>/100</strong>.</p>
        </div>
        <div class="gauge-wrapper">
            <svg class="gauge-svg" viewBox="0 0 100 100">
                <circle class="gauge-bg" cx="50" cy="50" r="45"></circle>
                <circle class="gauge-val" cx="50" cy="50" r="45"></circle>
            </svg>
            <div class="gauge-text"><?= $healthScore ?></div>
        </div>
    </div>

    <div class="summary-grid">
        
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fa-solid fa-bell" style="color:#e74c3c;"></i> Critical Attention Items</div>
                <span class="badge" style="background:#fadbd8; color:#c0392b; padding:4px 12px; border-radius:20px; font-size:0.75rem; font-weight:700;"><?= count($alerts) ?> Alerts</span>
            </div>
            
            <div style="flex:1; overflow-y:auto; max-height:350px;">
                <?php if(empty($alerts)): ?>
                    <div style="text-align:center; padding:40px; color:#bdc3c7;">
                        <i class="fa-solid fa-circle-check" style="font-size:3rem; color:#2ecc71; margin-bottom:15px; opacity:0.6;"></i>
                        <p>No critical issues found. Systems nominal.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($alerts as $alert): ?>
                        <a href="<?= $alert['link'] ?>" class="alert-item">
                            <div class="alert-icon" style="background:<?= $alert['bg'] ?>; color:<?= $alert['color'] ?>;">
                                <i class="fa-solid <?= $alert['icon'] ?>"></i>
                            </div>
                            <div class="alert-content">
                                <h4><?= $alert['title'] ?></h4>
                                <p><?= $alert['msg'] ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:25px;">
            
            <div class="card" style="height:auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fa-solid fa-trophy" style="color:#f39c12;"></i> Top Departments</div>
                    <small style="color:#7f8c8d;">By Avg. Completion</small>
                </div>
                <table class="dept-table">
                    <?php $i=1; foreach($topDepts as $d): 
                        $util = ($d['budget'] > 0) ? round(($d['spent']/$d['budget'])*100) : 0;
                        $utilColor = $util > 90 ? '#e74c3c' : '#2ecc71';
                    ?>
                    <tr>
                        <td width="5%"><span class="dept-rank <?= $i==1?'top':'' ?>">#<?= $i++ ?></span></td>
                        <td>
                            <div style="font-weight:600; color:#2c3e50;"><?= htmlspecialchars($d['name']) ?></div>
                            <div style="font-size:0.75rem; color:#95a5a6;">Budget Used: <span style="color:<?= $utilColor ?>; font-weight:700;"><?= $util ?>%</span></div>
                        </td>
                        <td align="right">
                            <div style="font-weight:700; color:#3498db;"><?= round($d['avg_prog'], 1) ?>%</div>
                            <div style="font-size:0.75rem; color:#95a5a6;"><?= $d['projects'] ?> Proj.</div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="card" style="height:auto;">
                <div class="card-header">
                    <div class="card-title"><i class="fa-solid fa-user-clock" style="color:#9b59b6;"></i> Workload & Efficiency</div>
                </div>
                <?php foreach($busyUsers as $u): 
                    $efficiency = ($u['total_tasks'] > 0) ? round(($u['completed_tasks'] / $u['total_tasks']) * 100) : 0;
                    $effColor = $efficiency > 70 ? '#2ecc71' : '#f39c12';
                ?>
                <div class="res-item">
                    <div class="res-user">
                        <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $u['avatar'] ?: 'default-profile.png' ?>">
                        <span style="font-weight:600; font-size:0.9rem; color:#2c3e50;"><?= htmlspecialchars($u['full_name_en']) ?></span>
                    </div>
                    <div class="res-stats">
                        <div style="font-weight:700; color:#2c3e50;"><?= $u['total_tasks'] ?> Tasks</div>
                        <div style="color:<?= $effColor ?>; font-weight:600;"><?= $efficiency ?>% Done</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>

    </div>
    
    <div class="card" style="height:auto;">
        <div class="card-header" style="border:none; padding-bottom:0;">
            <div class="card-title"><i class="fa-solid fa-bars-progress" style="color:#7f8c8d;"></i> Global Task Overview</div>
        </div>
        
        <?php 
            $totalTasks = $taskStats['pending'] + $taskStats['in_progress'] + $taskStats['completed'];
            $pPend = ($totalTasks > 0) ? ($taskStats['pending'] / $totalTasks) * 100 : 0;
            $pProg = ($totalTasks > 0) ? ($taskStats['in_progress'] / $totalTasks) * 100 : 0;
            $pComp = ($totalTasks > 0) ? ($taskStats['completed'] / $totalTasks) * 100 : 0;
        ?>

        <div class="task-bar-container">
            <div class="task-segment" style="width:<?= $pPend ?>%; background:#f39c12;" title="Pending">
                <?= $pPend > 5 ? $taskStats['pending'] : '' ?>
            </div>
            <div class="task-segment" style="width:<?= $pProg ?>%; background:#3498db;" title="In Progress">
                <?= $pProg > 5 ? $taskStats['in_progress'] : '' ?>
            </div>
            <div class="task-segment" style="width:<?= $pComp ?>%; background:#2ecc71;" title="Completed">
                <?= $pComp > 5 ? $taskStats['completed'] : '' ?>
            </div>
        </div>
        
        <div style="display:flex; justify-content:center; gap:20px; margin-top:10px; font-size:0.8rem; color:#7f8c8d;">
            <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#f39c12; border-radius:50%;"></span> Pending</span>
            <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#3498db; border-radius:50%;"></span> In Progress</span>
            <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#2ecc71; border-radius:50%;"></span> Completed</span>
        </div>
    </div>

</div>
</div>

</body>
</html>