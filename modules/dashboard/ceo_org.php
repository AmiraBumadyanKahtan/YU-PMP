<?php
// modules/dashboard/ceo_org.php

require_once "../../core/config.php";
require_once "../../core/auth.php";

// 1. التحقق من الصلاحية
if (!in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office'])) {
    die("Access Denied");
}

$db = Database::getInstance()->pdo();

// ==========================================================
// 1. TOP KPI CARDS (Calculations)
// ==========================================================

// أ) إجمالي الموظفين
$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_deleted=0")->fetchColumn();
$newUsersThisMonth = $db->query("SELECT COUNT(*) FROM users WHERE is_deleted=0 AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();
$growthRate = ($totalUsers > 0) ? round(($newUsersThisMonth / $totalUsers) * 100, 1) : 0;

// ب) نسبة التفعيل
$activeUsers = $db->query("SELECT COUNT(*) FROM users WHERE is_active=1 AND is_deleted=0")->fetchColumn();
$activationRate = ($totalUsers > 0) ? round(($activeUsers / $totalUsers) * 100) : 0;
$weeklyActive = $db->query("SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_deleted=0")->fetchColumn();

// ج) الهيكل التنظيمي
$totalDepts = $db->query("SELECT COUNT(*) FROM departments WHERE is_deleted=0")->fetchColumn();
$totalBranches = $db->query("SELECT COUNT(*) FROM branches WHERE is_active=1")->fetchColumn();
$unassignedUsers = $db->query("SELECT COUNT(*) FROM users WHERE department_id IS NULL AND is_deleted=0")->fetchColumn();

// د) الموارد
$busyUsers = $db->query("SELECT COUNT(DISTINCT user_id) FROM project_team WHERE is_active=1")->fetchColumn();
$utilizationRate = ($totalUsers > 0) ? round(($busyUsers / $totalUsers) * 100) : 0;


// ==========================================================
// 2. DETAILED TABLES & CHARTS DATA
// ==========================================================

// أ) جدول الأقسام
$deptStats = $db->query("
    SELECT 
        d.name as dept_name,
        u.full_name_en as manager_name,
        u.avatar as manager_avatar,
        u.job_title as manager_title,
        (SELECT COUNT(*) FROM users WHERE department_id = d.id AND is_deleted=0) as emp_count,
        (SELECT COUNT(*) FROM operational_projects WHERE department_id = d.id AND is_deleted=0 AND status_id IN (2,5,6)) as active_projects,
        (SELECT COUNT(*) FROM operational_projects WHERE department_id = d.id AND is_deleted=0 AND status_id = 6) as in_progress_projects
    FROM departments d
    LEFT JOIN users u ON u.id = d.manager_id
    WHERE d.is_deleted = 0
    ORDER BY emp_count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ب) توزيع الأدوار
$rolesData = $db->query("
    SELECT r.role_name, COUNT(u.id) as count 
    FROM users u
    JOIN roles r ON r.id = u.primary_role_id
    WHERE u.is_deleted=0
    GROUP BY r.role_name
    ORDER BY count DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// ج) توزيع الفروع
$branchData = $db->query("
    SELECT b.branch_name, COUNT(ub.user_id) as count
    FROM branches b
    LEFT JOIN user_branches ub ON b.id = ub.branch_id
    WHERE b.is_active = 1
    GROUP BY b.branch_name
")->fetchAll(PDO::FETCH_ASSOC);

// د) آخر المنضمين
$newJoiners = $db->query("
    SELECT full_name_en, job_title, created_at, avatar 
    FROM users 
    WHERE is_deleted=0 
    ORDER BY created_at DESC 
    LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CEO Dashboard - Organization</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Theme Colors (Matches your provided style) --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* Header */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }
        .page-title { margin: 0; font-size: 2rem; font-weight: 700; color: #ff8c00; display: flex; align-items: center; gap: 12px; }
        .page-subtitle { margin: 5px 0 0; color: #7f8c8d; font-size: 0.95rem; }

        /* Tabs (Orange Theme) */
        .tabs-nav { background: #fff; padding: 5px; border-radius: 50px; display: inline-flex; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .tabs-nav a { padding: 10px 25px; text-decoration: none; color: #7f8c8d; border-radius: 40px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
        .tabs-nav a:hover { color: #ff8c00; }
        .tabs-nav a.active { background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3); }

        /* --- KPI Cards --- */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .kpi-card { 
            background: #fff; padding: 25px; border-radius: 14px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; 
            position: relative; overflow: hidden; transition: transform 0.2s;
            display: flex; flex-direction: column; justify-content: space-between; min-height: 140px;
        }
        .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }
        
        .kpi-label { font-size: 0.85rem; color: #95a5a6; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
        .kpi-value { font-size: 2.2rem; font-weight: 800; color: #2c3e50; margin-bottom: 5px; }
        .kpi-sub { font-size: 0.85rem; color: #7f8c8d; display: flex; align-items: center; gap: 5px; }
        .kpi-sub.up { color: #2ecc71; }
        .kpi-icon { position: absolute; right: -10px; bottom: -10px; font-size: 5rem; opacity: 0.05; transform: rotate(-15deg); }

        /* --- Content Grid --- */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px; }
        
        /* General Card */
        .content-card { background: #fff; border-radius: 14px; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; display: flex; flex-direction: column; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #f9f9f9; padding-bottom: 15px; }
        .card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #2c3e50; }

        /* Table (Matching your Orange Theme) */
        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .modern-table th { 
            background: #fff4e1; /* Light Orange Bg */
            padding: 12px 15px; font-size: 0.8rem; color: #c86a12; /* Dark Orange Text */
            font-weight: 700; text-align: left; border: none; 
        }
        .modern-table th:first-child { border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .modern-table th:last-child { border-top-right-radius: 10px; border-bottom-right-radius: 10px; }
        
        .modern-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; color: #555; font-size: 0.9rem; }
        .modern-table tr:last-child td { border-bottom: none; }
        .modern-table tr:hover td { background-color: #fffdf9; }

        .user-info { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        /* Badges */
        .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
        .badge-purple { background: #e3f2fd; color: #2980b9; } /* Blue for staff count */
        .badge-orange { background: #ffe5e5; color: #c0392b; } /* Red for vacant */

        /* Joiners List */
        .joiner-item { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f9f9f9; }
        .joiner-item:last-child { border-bottom: none; }
        .joiner-text h4 { margin: 0; font-size: 0.95rem; color: #2c3e50; font-weight: 600; }
        .joiner-text p { margin: 2px 0 0; font-size: 0.8rem; color: #95a5a6; }
        .joiner-date { margin-left: auto; font-size: 0.75rem; color: #bdc3c7; }

        /* Branch Stats */
        .branch-stat { text-align: center; flex: 1; padding: 10px; border-right: 1px solid #f1f5f9; }
        .branch-stat:last-child { border-right: none; }
        .branch-val { font-size: 1.8rem; font-weight: 800; color: #ff8c00; }
        .branch-lbl { font-size: 0.85rem; color: #7f8c8d; font-weight: 600; margin-top: 5px; }

        /* Utilization Bar */
        .util-container { margin-top: 10px; width: 100%; }
        .util-bar { height: 8px; background: #f0f0f0; border-radius: 5px; overflow: hidden; display: flex; margin-bottom: 8px; }
        .util-fill { height: 100%; background: #2ecc71; }
        .util-info { display: flex; justify-content: space-between; font-size: 0.8rem; color: #7f8c8d; }

        @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-sitemap"></i> Organization Overview</h1>
            <p class="page-subtitle">Real-time insights into workforce, structure, and activity.</p>
        </div>
        <div class="tabs-nav">
            <a href="ceo_org.php" class="active">Organization</a>
            <a href="ceo_projects.php">Projects & Ops</a>
            <a href="../reports/ceo_updates.php">Project Updates</a>
            <a href="ceo_summary.php">Summary</a>
        </div>
    </div>

    <div class="kpi-grid">
        
        <div class="kpi-card">
            <div class="kpi-label">Total Workforce</div>
            <div class="kpi-value"><?= number_format($totalUsers) ?></div>
            <div class="kpi-sub <?= $growthRate >= 0 ? 'up' : '' ?>">
                <i class="fa-solid fa-arrow-trend-up"></i> <?= $growthRate ?>% Growth
            </div>
            <i class="fa-solid fa-users kpi-icon" style="color: #3498db;"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">System Adoption</div>
            <div class="kpi-value"><?= $activationRate ?>%</div>
            <div class="kpi-sub">
                <i class="fa-solid fa-laptop-code"></i> <?= $weeklyActive ?> Active this week
            </div>
            <i class="fa-solid fa-fingerprint kpi-icon" style="color: #9b59b6;"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Departments</div>
            <div class="kpi-value"><?= $totalDepts ?></div>
            <div class="kpi-sub" style="color: #e67e22;">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= $unassignedUsers ?> Unassigned
            </div>
            <i class="fa-solid fa-building kpi-icon" style="color: #e67e22;"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Branches</div>
            <div class="kpi-value"><?= $totalBranches ?></div>
            <div class="kpi-sub" style="color:#7f8c8d;">
                <i class="fa-solid fa-location-dot"></i> Active Locations
            </div>
            <i class="fa-solid fa-map-location-dot kpi-icon" style="color: #7f8c8d;"></i>
        </div>

        <div class="kpi-card">
            <div class="kpi-label">Resource Utilization</div>
            <div class="kpi-value"><?= $utilizationRate ?>%</div>
            <div class="util-container">
                <div class="util-bar">
                    <div class="util-fill" style="width: <?= $utilizationRate ?>%"></div>
                </div>
                <div class="util-info">
                    <span><?= $busyUsers ?> Assigned</span>
                </div>
            </div>
            <i class="fa-solid fa-chart-pie kpi-icon" style="color: #2ecc71;"></i>
        </div>
    </div>

    <div class="content-grid">
        
        <div style="display:flex; flex-direction:column; gap:25px;">
            
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Department Performance Overview</h3>
                </div>
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th width="35%">Department / Manager</th>
                            <th width="20%">Headcount</th>
                            <th width="20%">Projects</th>
                            <th width="25%">Capacity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($deptStats as $dept): 
                            $capacity = min(100, ($dept['active_projects'] * 15)); 
                            $capColor = $capacity > 80 ? '#2ecc71' : '#3498db';
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight: 700; color:#2c3e50; margin-bottom: 5px;"><?= htmlspecialchars($dept['dept_name']) ?></div>
                                <div class="user-info">
                                    <?php if($dept['manager_name']): ?>
                                        <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $dept['manager_avatar'] ?: 'default-profile.png' ?>" class="avatar">
                                        <div>
                                            <div style="font-size:0.85rem; font-weight:600;"><?= htmlspecialchars($dept['manager_name']) ?></div>
                                            <div style="font-size:0.75rem; color:#aaa;"><?= htmlspecialchars($dept['manager_title']) ?></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-orange">Vacant</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-purple"><?= $dept['emp_count'] ?> Staff</span>
                            </td>
                            <td>
                                <span style="font-weight:700; color:#2c3e50;"><?= $dept['active_projects'] ?></span> 
                                <small style="color:#999; font-size:0.7rem;">(<?= $dept['in_progress_projects'] ?> Active)</small>
                            </td>
                            <td>
                                <div style="width: 100%; height: 6px; background:#f1f5f9; border-radius:3px;">
                                    <div style="width: <?= $capacity ?>%; height:100%; background: <?= $capColor ?>; border-radius:3px;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Geographical Distribution</h3>
                </div>
                <div style="display:flex; justify-content:space-around;">
                    <?php if(empty($branchData)): ?>
                        <div style="color:#aaa; padding:20px;">No branch data available.</div>
                    <?php else: ?>
                        <?php foreach($branchData as $branch): ?>
                            <div class="branch-stat">
                                <div class="branch-val"><?= $branch['count'] ?></div>
                                <div class="branch-lbl"><?= htmlspecialchars($branch['branch_name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div style="display: flex; flex-direction: column; gap: 25px;">
            
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Workforce Roles</h3>
                </div>
                <div style="height: 250px; position:relative;">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Latest Joiners</h3>
                </div>
                <?php foreach($newJoiners as $user): ?>
                <div class="joiner-item">
                    <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $user['avatar'] ?: 'default-profile.png' ?>" class="avatar">
                    <div class="joiner-text">
                        <h4><?= htmlspecialchars($user['full_name_en']) ?></h4>
                        <p><?= htmlspecialchars($user['job_title'] ?: 'New Member') ?></p>
                    </div>
                    <div class="joiner-date"><?= date('M d', strtotime($user['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

</div>
</div>

<script>
    // --- Role Distribution Chart ---
    const ctx = document.getElementById('roleChart').getContext('2d');
    
    // PHP Data to JS
    const labels = <?= json_encode(array_column($rolesData, 'role_name')) ?>;
    const data = <?= json_encode(array_column($rolesData, 'count')) ?>;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#ff8c00', '#3498db', '#2ecc71', '#f1c40f', '#e74c3c', '#9b59b6'
                ],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, font: { size: 11, family: 'Varela Round' } }
                }
            },
            layout: { padding: 10 }
        }
    });
</script>

</body>
</html>