<?php
// modules/dashboard/public_dashboard.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::check()) die("Access Denied");

$db = Database::getInstance()->pdo();

// --- 1. الإحصائيات السريعة ---
$totalProjects = $db->query("SELECT COUNT(*) FROM operational_projects WHERE is_deleted=0")->fetchColumn();
$activeStaff = $db->query("SELECT COUNT(DISTINCT user_id) FROM project_team WHERE is_active=1")->fetchColumn();
$avgProgress = $db->query("SELECT AVG(progress_percentage) FROM operational_projects WHERE status_id = 6 AND is_deleted=0")->fetchColumn();
$avgProgress = round($avgProgress ?: 0, 1);
$overdueTasks = $db->query("SELECT COUNT(*) FROM project_tasks WHERE due_date < CURDATE() AND status_id != 3 AND is_deleted=0")->fetchColumn();

// --- 2. البيانات للرسوم البيانية ---

// أ) أعلى 5 مشاريع إنجازاً
$topProjects = $db->query("SELECT name, progress_percentage FROM operational_projects WHERE status_id = 6 AND is_deleted=0 ORDER BY progress_percentage DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// ب) توزيع حالة المشاريع
$statusDist = $db->query("SELECT s.name, COUNT(p.id) as count FROM operational_projects p JOIN operational_project_statuses s ON s.id = p.status_id WHERE p.is_deleted=0 GROUP BY s.name")->fetchAll(PDO::FETCH_ASSOC);

// ج) [جديد] توزيع المشاريع حسب الأقسام
$deptDist = $db->query("SELECT d.name, COUNT(p.id) as count FROM operational_projects p JOIN departments d ON d.id = p.department_id WHERE p.is_deleted=0 GROUP BY d.name")->fetchAll(PDO::FETCH_ASSOC);

// د) [جديد] آخر الإنجازات (مراحل مكتملة)
$recentAchievements = $db->query("
    SELECT m.name as milestone, p.name as project, m.updated_at 
    FROM project_milestones m 
    JOIN operational_projects p ON p.id = m.project_id 
    WHERE m.status_id = 3 AND m.is_deleted = 0 
    ORDER BY m.updated_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// --- تحضير البيانات للـ JS ---
$chartLabels = []; $chartData = [];
foreach($topProjects as $p) { $chartLabels[] = substr($p['name'], 0, 20).'...'; $chartData[] = $p['progress_percentage']; }

$stLabels = []; $stData = []; $stColors = ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6'];
$i=0; foreach($statusDist as $s) { $stLabels[] = $s['name']; $stData[] = $s['count']; $i++; }

$deptLabels = []; $deptData = []; 
foreach($deptDist as $d) { $deptLabels[] = $d['name']; $deptData[] = $d['count']; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organization Insights</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: "Varela Round", sans-serif; background-color: #f4f7f6; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-title h1 { margin: 0; font-size: 1.8rem; color: #2c3e50; font-weight: 700; }
        .header-title p { color: #7f8c8d; margin: 5px 0 0; font-size: 0.95rem; }
        
        .btn-projects { 
            background: linear-gradient(135deg, #3498db, #2980b9); color: white; 
            padding: 12px 25px; border-radius: 8px; text-decoration: none; 
            font-weight: 600; display: flex; align-items: center; gap: 8px; 
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); transition: transform 0.2s;
        }
        .btn-projects:hover { transform: translateY(-2px); }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; }
        .stat-card::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; }
        
        .st-blue::after { background: #3498db; }
        .st-green::after { background: #2ecc71; }
        .st-orange::after { background: #f39c12; }
        .st-red::after { background: #e74c3c; }

        .stat-val { font-size: 2.2rem; font-weight: 800; color: #2c3e50; line-height: 1; }
        .stat-label { color: #95a5a6; font-size: 0.9rem; font-weight: 600; margin-top: 5px; text-transform: uppercase; }
        .stat-icon-bg { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; opacity: 0.15; }

        /* Charts Layout */
        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 25px; }
        .charts-lower { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
        
        .content-box { background: #fff; padding: 25px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); height: 100%; }
        .box-header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 15px; }
        .box-title { font-size: 1.1rem; font-weight: 700; color: #34495e; margin: 0; display: flex; align-items: center; gap: 8px; }

        /* Achievements List */
        .achieve-list { list-style: none; padding: 0; margin: 0; }
        .achieve-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px dashed #eee; }
        .achieve-item:last-child { border-bottom: none; }
        .achieve-icon { width: 35px; height: 35px; background: #e8f5e9; color: #2ecc71; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
        .achieve-info h5 { margin: 0; color: #333; font-size: 0.95rem; }
        .achieve-info span { font-size: 0.8rem; color: #888; }
        .achieve-date { margin-left: auto; font-size: 0.8rem; color: #aaa; background: #f9f9f9; padding: 2px 8px; border-radius: 4px; }

        @media (max-width: 1000px) { .charts-grid, .charts-lower { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="dashboard-header">
        <div class="header-title">
            <h1>Insights Dashboard</h1>
            <p>Real-time overview of organizational performance.</p>
        </div>
        
        <?php if (Auth::can('view_project')): ?>
            <a href="../operational_projects/index.php" class="btn-projects">
                <span>Manage Projects</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        <?php endif; ?>
    </div>

    <div class="stats-grid">
        <div class="stat-card st-blue">
            <div>
                <div class="stat-val"><?= $totalProjects ?></div>
                <div class="stat-label">Total Projects</div>
            </div>
            <div class="stat-icon-bg" style="background:#3498db; color:#3498db;"><i class="fa-solid fa-folder-tree"></i></div>
        </div>
        <div class="stat-card st-green">
            <div>
                <div class="stat-val"><?= $avgProgress ?>%</div>
                <div class="stat-label">Avg. Completion</div>
            </div>
            <div class="stat-icon-bg" style="background:#2ecc71; color:#2ecc71;"><i class="fa-solid fa-chart-line"></i></div>
        </div>
        <div class="stat-card st-orange">
            <div>
                <div class="stat-val"><?= $activeStaff ?></div>
                <div class="stat-label">Active Staff</div>
            </div>
            <div class="stat-icon-bg" style="background:#f39c12; color:#f39c12;"><i class="fa-solid fa-users-viewfinder"></i></div>
        </div>
        <div class="stat-card st-red">
            <div>
                <div class="stat-val"><?= $overdueTasks ?></div>
                <div class="stat-label">Overdue Tasks</div>
            </div>
            <div class="stat-icon-bg" style="background:#e74c3c; color:#e74c3c;"><i class="fa-solid fa-bell"></i></div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="content-box">
            <div class="box-header">
                <h3 class="box-title"><i class="fa-solid fa-trophy" style="color:#f1c40f;"></i> Top Performing Projects</h3>
            </div>
            <canvas id="projectsChart" height="120"></canvas>
        </div>

        <div class="content-box">
            <div class="box-header">
                <h3 class="box-title"><i class="fa-solid fa-pie-chart" style="color:#3498db;"></i> Project Status</h3>
            </div>
            <div style="position: relative; height: 200px; display:flex; justify-content:center;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="charts-lower">
        <div class="content-box">
            <div class="box-header">
                <h3 class="box-title"><i class="fa-solid fa-building-user" style="color:#9b59b6;"></i> Projects by Dept.</h3>
            </div>
            <div style="position: relative; height: 250px; display:flex; justify-content:center;">
                <canvas id="deptChart"></canvas>
            </div>
        </div>

        <div class="content-box">
            <div class="box-header">
                <h3 class="box-title"><i class="fa-solid fa-certificate" style="color:#2ecc71;"></i> Recent Achievements</h3>
            </div>
            
            <?php if(empty($recentAchievements)): ?>
                <div style="text-align:center; color:#ccc; padding:30px;">No recent milestones completed.</div>
            <?php else: ?>
                <ul class="achieve-list">
                    <?php foreach($recentAchievements as $ac): ?>
                        <li class="achieve-item">
                            <div class="achieve-icon"><i class="fa-solid fa-check"></i></div>
                            <div class="achieve-info">
                                <h5><?= htmlspecialchars($ac['milestone']) ?></h5>
                                <span>Project: <?= htmlspecialchars($ac['project']) ?></span>
                            </div>
                            <div class="achieve-date"><?= date('M d', strtotime($ac['updated_at'])) ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<script>
    // 1. Bar Chart (Top Projects)
    new Chart(document.getElementById('projectsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Completion %',
                data: <?= json_encode($chartData) ?>,
                backgroundColor: '#3498db',
                borderRadius: 6,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
        }
    });

    // 2. Donut Chart (Status)
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($stLabels) ?>,
            datasets: [{
                data: <?= json_encode($stData) ?>,
                backgroundColor: ['#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right', labels: { boxWidth: 10, usePointStyle: true } } },
            cutout: '75%'
        }
    });

    // 3. Polar Area Chart (Departments)
    new Chart(document.getElementById('deptChart'), {
        type: 'polarArea',
        data: {
            labels: <?= json_encode($deptLabels) ?>,
            datasets: [{
                data: <?= json_encode($deptData) ?>,
                backgroundColor: [
                    'rgba(52, 152, 219, 0.6)',
                    'rgba(46, 204, 113, 0.6)',
                    'rgba(241, 196, 15, 0.6)',
                    'rgba(231, 76, 60, 0.6)',
                    'rgba(155, 89, 182, 0.6)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            scales: { r: { ticks: { display: false } } }
        }
    });
</script>

</body>
</html>