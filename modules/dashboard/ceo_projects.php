<?php
// modules/dashboard/ceo_projects.php

require_once "../../core/config.php";
require_once "../../core/auth.php";

// 1. التحقق من الصلاحية
if (!in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office'])) {
    die("Access Denied");
}

$db = Database::getInstance()->pdo();

// ==========================================================
// 1. PROJECT METRICS (KPI Cards)
// ==========================================================

$activeProjects = $db->query("SELECT COUNT(*) FROM operational_projects WHERE status_id = 6 AND is_deleted=0")->fetchColumn();
$avgProgress = $db->query("SELECT AVG(progress_percentage) FROM operational_projects WHERE status_id = 6 AND is_deleted=0")->fetchColumn();
$avgProgress = round($avgProgress ?: 0, 1);
$delayedProjects = $db->query("SELECT COUNT(*) FROM operational_projects WHERE end_date < CURDATE() AND status_id NOT IN (4, 8) AND is_deleted=0")->fetchColumn();

$financials = $db->query("SELECT SUM(approved_budget) as total_approved, SUM(spent_budget) as total_spent FROM operational_projects WHERE is_deleted=0")->fetch(PDO::FETCH_ASSOC);
$totalBudget = $financials['total_approved'] ?: 0;
$totalSpent = $financials['total_spent'] ?: 0;
$burnRate = ($totalBudget > 0) ? round(($totalSpent / $totalBudget) * 100, 1) : 0;

// ==========================================================
// 2. CHARTS DATA
// ==========================================================

$budgetChartData = $db->query("
    SELECT d.name, SUM(p.approved_budget) as budget, SUM(p.spent_budget) as spent
    FROM operational_projects p
    JOIN departments d ON d.id = p.department_id
    WHERE p.is_deleted=0
    GROUP BY d.name
    ORDER BY budget DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$statusChartData = $db->query("
    SELECT s.name, s.color, COUNT(p.id) as count
    FROM operational_projects p
    JOIN operational_project_statuses s ON s.id = p.status_id
    WHERE p.is_deleted=0
    GROUP BY s.name
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 3. TABLES DATA
// ==========================================================

$priorityProjects = $db->query("
    SELECT p.name, p.project_code, p.priority, p.progress_percentage, p.end_date, 
           s.name as status_name, s.color as status_color,
           u.full_name_en as manager_name, u.avatar as manager_avatar
    FROM operational_projects p
    LEFT JOIN operational_project_statuses s ON s.id = p.status_id
    LEFT JOIN users u ON u.id = p.manager_id
    WHERE p.is_deleted=0 AND p.priority IN ('high', 'critical') AND p.status_id != 8
    ORDER BY p.progress_percentage DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CEO Dashboard - Projects</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* =========================================================
           PAGE STYLES (Provided Theme: Varela Round + Orange Accents)
        ========================================================= */
        body { font-family: "Varela Round", sans-serif; font-weight: 400; font-style: normal; background-color: #fcfcfc; margin: 0; }
        .page-wrapper { padding: 2rem; }

        /* Page Header */
        .page-header-flex { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 25px; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }
        .page-subtitle { margin: 5px 0 0; color: #7f8c8d; font-size: 0.95rem; }

        /* Tabs (Orange Theme) */
        .tabs-nav { background: #fff; padding: 5px; border-radius: 50px; display: inline-flex; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .tabs-nav a { padding: 10px 25px; text-decoration: none; color: #7f8c8d; border-radius: 40px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
        .tabs-nav a:hover { color: #ff8c00; }
        .tabs-nav a.active { background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3); }

        /* KPI Grid (5 Cards) */
        .kpi-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 30px; }
        @media (max-width: 1400px) { .kpi-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); } }

        .kpi-card { 
            background: #ffffff; padding: 25px; border-radius: 14px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.04); border: 1px solid #f9f9f9;
            position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; min-height: 140px;
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-5px); }

        .kpi-title { font-size: 0.8rem; color: #95a5a6; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .kpi-value { font-size: 2.2rem; font-weight: 800; color: #2c3e50; line-height: 1; margin-bottom: 5px; }
        .kpi-sub { font-size: 0.85rem; color: #7f8c8d; font-weight: 500; }
        .text-danger { color: #c0392b !important; }
        .text-success { color: #27ae60 !important; }
        
        .kpi-icon-bg { position: absolute; right: -15px; bottom: -15px; font-size: 5rem; opacity: 0.05; color: #2c3e50; transform: rotate(-15deg); }

        /* Content Grid */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px; }
        
        /* Table Card (Using provided styles) */
        .table-card {
            background: #ffffff; padding: 25px; border-radius: 14px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.04); overflow-x: auto;
            display: flex; flex-direction: column;
        }
        .card-header { margin-bottom: 20px; border-bottom: 1px solid #f9f9f9; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #2c3e50; }

        .data-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; text-align: center; }
        
        .data-table th {
            background: #fff4e1; padding: 15px; font-size: 0.8rem; color: #c86a12; 
            font-weight: 700; text-align: left; border: none; text-transform: uppercase;
        }
        .data-table th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .data-table th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .data-table td {
            padding: 15px; font-size: 0.88rem; color: #333; vertical-align: middle; text-align: left;
            background: #ffffff; border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9;
        }
        .data-table tr:hover td { background-color: #fffdf9; }
        .data-table tr td:first-child { border-left: 1px solid #f9f9f9; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .data-table tr td:last-child { border-right: 1px solid #f9f9f9; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        /* Components */
        .proj-code { font-family: 'Varela Round', sans-serif; font-size: 0.75rem; background: #f0f2f5; padding: 3px 6px; border-radius: 4px; color: #7f8c8d; border: 1px solid #ddd; }
        .proj-name { font-weight: 700; color: #2c3e50; display: block; margin-bottom: 3px; font-size: 0.95rem; }
        .manager-cell { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #7f8c8d; }
        .avatar-sm { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid #eee; }
        
        .prog-track { width: 80px; height: 6px; background: #e5e7eb; border-radius: 10px; overflow: hidden; display: inline-block; vertical-align: middle; margin-right: 8px; }
        .prog-fill { height: 100%; border-radius: 10px; }
        
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }

        /* Action Buttons */
        .btn-view-all { 
            font-size: 0.85rem; color: #ff8c00; text-decoration: none; font-weight: 600; 
            padding: 5px 10px; border-radius: 20px; background: #fff4e0; transition: 0.2s;
        }
        .btn-view-all:hover { background: #ff8c00; color: #fff; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-chart-pie"></i> Projects Dashboard</h1>
            <p class="page-subtitle">Monitoring active projects, budgets, and delivery status.</p>
        </div>
        <div class="tabs-nav">
            <a href="ceo_org.php">Organization</a>
            <a href="ceo_projects.php" class="active">Projects & Ops</a>
            <a href="../reports/ceo_updates.php">Project Updates</a>
            <a href="ceo_summary.php">Summary</a>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-title">Active Projects</div>
            <div class="kpi-value"><?= $activeProjects ?></div>
            <div class="kpi-sub" style="color:#2980b9;">Currently In-Progress</div>
            <i class="fa-solid fa-layer-group kpi-icon-bg"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Avg Completion</div>
            <div class="kpi-value"><?= $avgProgress ?>%</div>
            <div class="kpi-sub">Across active portfolio</div>
            <i class="fa-solid fa-chart-line kpi-icon-bg"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">At Risk</div>
            <div class="kpi-value text-danger"><?= $delayedProjects ?></div>
            <div class="kpi-sub text-danger">Past Due Date</div>
            <i class="fa-solid fa-triangle-exclamation kpi-icon-bg"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Total Budget</div>
            <div class="kpi-value"><?= number_format($totalBudget / 1000, 1) ?>K</div>
            <div class="kpi-sub">SAR Approved</div>
            <i class="fa-solid fa-sack-dollar kpi-icon-bg"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-title">Expenditure</div>
            <div class="kpi-value"><?= number_format($totalSpent / 1000, 1) ?>K</div>
            <div class="kpi-sub">
                <span class="<?= $burnRate > 90 ? 'text-danger' : 'text-success' ?>"><?= $burnRate ?>%</span> Utilized
            </div>
            <i class="fa-solid fa-coins kpi-icon-bg"></i>
        </div>
    </div>

    <div class="content-grid">
        
        <div style="display:flex; flex-direction:column; gap:25px;">
            
            <div class="table-card">
                <div class="card-header">
                    <div class="card-title">Budget Performance by Department</div>
                </div>
                <div style="height: 300px;">
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>

            <div class="table-card">
                <div class="card-header">
                    <div class="card-title">Critical & High Priority Projects</div>
                    <a href="../operational_projects/index.php" class="btn-view-all">View All</a>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Manager</th>
                            <th>Progress</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($priorityProjects as $p): ?>
                        <tr>
                            <td>
                                <span class="proj-name"><?= htmlspecialchars($p['name']) ?></span>
                                <span class="proj-code"><?= $p['project_code'] ?></span>
                            </td>
                            <td>
                                <div class="manager-cell">
                                    <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $p['manager_avatar'] ?: 'default-profile.png' ?>" class="avatar-sm">
                                    <span><?= htmlspecialchars($p['manager_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center;">
                                    <div class="prog-track">
                                        <div class="prog-fill" style="width:<?= $p['progress_percentage'] ?>%; background:<?= $p['progress_percentage']==100?'#27ae60':'#2980b9' ?>"></div>
                                    </div>
                                    <span style="font-size:0.8rem; font-weight:600; color:#666;"><?= $p['progress_percentage'] ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php 
                                    $isLate = ($p['end_date'] < date('Y-m-d') && $p['status_name'] != 'Completed');
                                    echo $isLate ? "<span class='text-danger' style='font-weight:700;'>".date('M d', strtotime($p['end_date']))."</span>" : date('M d', strtotime($p['end_date']));
                                ?>
                            </td>
                            <td>
                                <span style="font-weight:600; font-size:0.85rem; color:<?= $p['status_color'] ?>">
                                    <span class="status-dot" style="background:<?= $p['status_color'] ?>"></span>
                                    <?= $p['status_name'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <div class="table-card">
            <div class="card-header">
                <div class="card-title">Portfolio Health</div>
            </div>
            <div style="height: 250px; position:relative;">
                <canvas id="statusChart"></canvas>
            </div>
            
            <div style="margin-top:30px;">
                <h4 style="font-size:0.9rem; color:#7f8c8d; margin-bottom:15px; text-transform:uppercase; font-weight:700;">Quick Actions</h4>
                <a href="../operational_projects/create.php" style="display:block; padding:15px; background:#fafafa; border:1px solid #eee; border-radius:10px; text-decoration:none; color:#2c3e50; font-weight:600; margin-bottom:10px; transition:0.2s;">
                    <i class="fa-solid fa-plus" style="color:#ff8c00; margin-right:10px;"></i> Initiate New Project
                </a>
                <a href="../reports/ceo_updates.php" style="display:block; padding:15px; background:#fafafa; border:1px solid #eee; border-radius:10px; text-decoration:none; color:#2c3e50; font-weight:600; transition:0.2s;">
                    <i class="fa-solid fa-file-contract" style="color:#2980b9; margin-right:10px;"></i> Review Reports
                </a>
            </div>
        </div>

    </div>

</div>
</div>

<script>
    // --- Budget Chart (Mixed Bar/Line) ---
    const ctxBudget = document.getElementById('budgetChart').getContext('2d');
    const budgetLabels = <?= json_encode(array_column($budgetChartData, 'name')) ?>;
    const budgetVals = <?= json_encode(array_column($budgetChartData, 'budget')) ?>;
    const spentVals = <?= json_encode(array_column($budgetChartData, 'spent')) ?>;

    new Chart(ctxBudget, {
        type: 'bar',
        data: {
            labels: budgetLabels,
            datasets: [
                {
                    label: 'Approved Budget',
                    data: budgetVals,
                    backgroundColor: '#e3f2fd',
                    borderColor: '#2980b9',
                    borderWidth: 1,
                    borderRadius: 6,
                    barPercentage: 0.6,
                    order: 2
                },
                {
                    label: 'Actual Spent',
                    data: spentVals,
                    backgroundColor: '#27ae60', // Green for spent
                    type: 'line', // Make spent a line for better comparison
                    borderColor: '#27ae60',
                    borderWidth: 2,
                    tension: 0.4,
                    pointRadius: 4,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { family: 'Varela Round' } } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    // --- Status Chart (Doughnut) ---
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    const statusLabels = <?= json_encode(array_column($statusChartData, 'name')) ?>;
    const statusCounts = <?= json_encode(array_column($statusChartData, 'count')) ?>;
    const statusColors = <?= json_encode(array_column($statusChartData, 'color')) ?>;

    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: statusColors,
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { family: 'Varela Round' } } } }
        }
    });
</script>

</body>
</html>