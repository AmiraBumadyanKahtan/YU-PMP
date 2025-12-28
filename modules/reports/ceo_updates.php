<?php
// modules/reports/ceo_updates.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../modules/operational_projects/project_functions.php"; 

if (!Auth::check() || !in_array($_SESSION['role_key'], ['ceo', 'super_admin', 'strategy_office'])) {
    die("Access Denied: Executives Only");
}

$db = Database::getInstance()->pdo();

// ==========================================================
// 1. CALCULATE STATS (STRICTLY FROM project_updates TABLE)
// ==========================================================
// نقوم بحساب العدادات هنا باستعلامات منفصلة ودقيقة تتجاهل المشاريع المحذوفة

// أ) عدد التحديثات المعلقة (Pending)
$pendingCount = $db->query("
    SELECT COUNT(u.id) 
    FROM project_updates u
    JOIN operational_projects p ON p.id = u.project_id
    WHERE u.status = 'pending' AND p.is_deleted = 0
")->fetchColumn();

// ب) عدد التحديثات التي تمت مراجعتها (Viewed)
$reviewedCount = $db->query("
    SELECT COUNT(u.id) 
    FROM project_updates u
    JOIN operational_projects p ON p.id = u.project_id
    WHERE u.status = 'viewed' AND p.is_deleted = 0
")->fetchColumn();

// ج) الإجمالي الكلي
$totalUpdatesCount = $pendingCount + $reviewedCount;


// ==========================================================
// 2. FILTERS & MAIN QUERY
// ==========================================================
$filter_project = $_GET['project_id'] ?? '';
$filter_dept    = $_GET['department_id'] ?? '';
$filter_status  = $_GET['status'] ?? '';

$sql = "
    SELECT u.*, 
           p.name as project_name, 
           p.project_code, 
           d.name as department_name,
           us.full_name_en as manager_name,
           us.avatar
    FROM project_updates u
    JOIN operational_projects p ON p.id = u.project_id
    LEFT JOIN departments d ON d.id = p.department_id
    JOIN users us ON us.id = u.user_id
    WHERE p.is_deleted = 0
";

$params = [];
if (!empty($filter_project)) { $sql .= " AND u.project_id = ?"; $params[] = $filter_project; }
if (!empty($filter_dept)) { $sql .= " AND p.department_id = ?"; $params[] = $filter_dept; }
if (!empty($filter_status)) { $sql .= " AND u.status = ?"; $params[] = $filter_status; }

$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$updates = $stmt->fetchAll();

// --- Dropdowns ---
$projectsList = $db->query("SELECT id, name, project_code FROM operational_projects WHERE is_deleted=0 ORDER BY name")->fetchAll();
$departmentsList = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CEO Updates Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Premium Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* Header */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }
        
        /* Tabs */
        .tabs-nav { background: #fff; padding: 5px; border-radius: 50px; display: inline-flex; border: 1px solid #eee; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .tabs-nav a { padding: 10px 25px; text-decoration: none; color: #7f8c8d; border-radius: 40px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
        .tabs-nav a:hover { color: #ff8c00; }
        .tabs-nav a.active { background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3); }

        /* Stats Cards (Mini) */
        .stats-container { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { 
            background: #fff; padding: 20px; border-radius: 14px; border: 1px solid #f9f9f9; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.04); display: flex; align-items: center; justify-content: space-between; 
            border-left: 5px solid transparent; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-num { font-size: 2rem; font-weight: 800; color: #2c3e50; }
        .stat-label { color: #95a5a6; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; }
        .stat-icon { font-size: 2.5rem; opacity: 0.15; }

        /* Filter Bar */
        .filter-bar { 
            background: #fff; padding: 20px; border-radius: 14px; margin-bottom: 30px; 
            display: flex; gap: 15px; align-items: flex-end; box-shadow: 0 4px 10px rgba(0,0,0,0.06); flex-wrap: wrap; 
        }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-weight: 700; margin-bottom: 8px; font-size: 0.85rem; color: #7f8c8d; }
        .filter-control { 
            width: 100%; height: 42px; padding: 0 12px; border: 1px solid #ddd; border-radius: 8px; 
            background: #fafafa; font-family: inherit; font-size: 0.9rem; outline: none; transition: 0.2s;
        }
        .filter-control:focus { border-color: #ff9c31; background: #fff; box-shadow: 0 0 5px rgba(255, 156, 49, 0.3); }

        .btn-primary { 
            background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; border: none; 
            height: 42px; padding: 0 25px; border-radius: 8px; font-weight: 600; cursor: pointer; 
            display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3); }

        .btn-reset { 
            background: #ffe5e5; color: #c0392b; height: 42px; padding: 0 20px; border-radius: 8px; 
            text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; transition: 0.2s;
        }
        .btn-reset:hover { background: #ffcccc; }

        /* Updates Grid */
        .update-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 25px; }
        
        .update-card { 
            background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #f0f0f0; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s, box-shadow 0.2s; 
            display: flex; flex-direction: column; position: relative;
        }
        .update-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.08); }

        .card-header { padding: 18px; background: #fdfdfd; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; }
        .proj-code { font-size: 0.75rem; background: #34495e; color: #fff; padding: 4px 8px; border-radius: 6px; font-weight: 700; }
        .dept-badge { font-size: 0.75rem; background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 6px; font-weight: 600; }
        .update-date { font-size: 0.8rem; color: #95a5a6; font-weight: 600; }

        .card-body { padding: 25px; flex: 1; }
        .proj-title { margin: 0 0 20px 0; font-size: 1.2rem; font-weight: 700; color: #2c3e50; line-height: 1.4; }
        .proj-title a { text-decoration: none; color: inherit; transition: color 0.2s; }
        .proj-title a:hover { color: #ff8c00; }

        .manager-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .manager-info { display: flex; align-items: center; gap: 12px; }
        .manager-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .manager-text h4 { margin: 0; font-size: 0.95rem; color: #34495e; }
        .manager-text p { margin: 2px 0 0; font-size: 0.75rem; color: #95a5a6; text-transform: uppercase; letter-spacing: 0.5px; }

        .progress-circle { 
            width: 55px; height: 55px; border-radius: 50%; 
            background: conic-gradient(#3498db var(--p), #f0f0f0 0); 
            display: flex; align-items: center; justify-content: center; position: relative; 
        }
        .progress-circle::after { 
            content: attr(data-val) '%'; position: absolute; background: #fff; 
            width: 45px; height: 45px; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800; color: #3498db;
        }

        .update-text { 
            color: #636e72; font-size: 0.9rem; line-height: 1.6; 
            background: #f8f9fa; padding: 15px; border-radius: 10px; border: 1px solid #f1f2f6;
            height: 80px; overflow: hidden; position: relative;
        }
        .update-text::after {
            content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 30px;
            background: linear-gradient(to bottom, transparent, #f8f9fa);
        }

        .card-footer { padding: 15px 25px; border-top: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; background: #fff; }
        
        .status-pill { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; gap: 6px; }
        .status-pending { background: #fff7ed; color: #e67e22; border: 1px solid #ffe0b2; }
        .status-viewed { background: #e8f5e9; color: #2ecc71; border: 1px solid #c8e6c9; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

        .btn-action { 
            padding: 8px 20px; border-radius: 30px; text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.2s; 
        }
        .btn-review { background: #3498db; color: #fff; box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3); }
        .btn-review:hover { background: #2980b9; transform: translateY(-2px); }
        
        .btn-details { background: #f1f2f6; color: #7f8c8d; }
        .btn-details:hover { background: #e2e6ea; color: #2c3e50; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px; background: #fff; border-radius: 16px; border: 1px solid #f0f0f0; box-shadow: 0 4px 10px rgba(0,0,0,0.03); color: #b2bec3; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-chart-pie"></i> CEO Project Updates</h1>
        <div class="tabs-nav">
            <a href="../dashboard/ceo_org.php">Organization</a>
            <a href="../dashboard/ceo_projects.php">Projects & Ops</a>
            <a href="ceo_updates.php" class="active">Project Updates</a>
            <a href="../dashboard/ceo_summary.php">Summary</a>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card" style="border-left-color: #f39c12;">
            <div>
                <div class="stat-num"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <i class="fa-solid fa-hourglass-half stat-icon" style="color: #f39c12;"></i>
        </div>
        <div class="stat-card" style="border-left-color: #2ecc71;">
            <div>
                <div class="stat-num"><?= $reviewedCount ?></div>
                <div class="stat-label">Reviewed Updates</div>
            </div>
            <i class="fa-solid fa-check-double stat-icon" style="color: #2ecc71;"></i>
        </div>
        <div class="stat-card" style="border-left-color: #3498db;">
            <div>
                <div class="stat-num"><?= $totalUpdatesCount ?></div>
                <div class="stat-label">Total Received</div>
            </div>
            <i class="fa-solid fa-inbox stat-icon" style="color: #3498db;"></i>
        </div>
    </div>

    <form class="filter-bar" method="GET">
        <div class="filter-group">
            <label>Department</label>
            <select name="department_id" class="filter-control">
                <option value="">All Departments</option>
                <?php foreach($departmentsList as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $filter_dept == $dept['id'] ? 'selected' : '' ?>><?= htmlspecialchars($dept['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Project</label>
            <select name="project_id" class="filter-control">
                <option value="">All Projects</option>
                <?php foreach($projectsList as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $filter_project == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['name']) ?> (<?= $p['project_code'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Status</label>
            <select name="status" class="filter-control">
                <option value="">All Statuses</option>
                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pending Review</option>
                <option value="viewed" <?= $filter_status == 'viewed' ? 'selected' : '' ?>>Viewed</option>
            </select>
        </div>
        
        <button type="submit" class="btn-primary">
            <i class="fa-solid fa-filter"></i> Apply Filter
        </button>
        
        <?php if($filter_project || $filter_status || $filter_dept): ?>
            <a href="ceo_updates.php" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($updates)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open" style="font-size:4rem; margin-bottom:15px; opacity:0.5;"></i>
            <h3>No updates found</h3>
            <p>Try adjusting your filters or check back later.</p>
        </div>
    <?php else: ?>
        <div class="update-grid">
            <?php foreach ($updates as $upd): 
                $isPending = ($upd['status'] == 'pending');
                $avatarPath = !empty($upd['avatar']) ? BASE_URL.'assets/uploads/avatars/'.$upd['avatar'] : BASE_URL.'assets/uploads/avatars/default-profile.png';
            ?>
                <div class="update-card">
                    <div class="card-header">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <span class="proj-code"><?= $upd['project_code'] ?></span>
                            <?php if(!empty($upd['department_name'])): ?>
                                <span class="dept-badge"><?= htmlspecialchars($upd['department_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="update-date"><i class="fa-regular fa-clock"></i> <?= date('M d, Y', strtotime($upd['created_at'])) ?></span>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="proj-title">
                            <a href="../../modules/operational_projects/view.php?id=<?= $upd['project_id'] ?>">
                                <?= htmlspecialchars($upd['project_name']) ?>
                            </a>
                        </h3>

                        <div class="manager-row">
                            <div class="manager-info">
                                <img src="<?= $avatarPath ?>" class="manager-avatar" alt="Manager">
                                <div class="manager-text">
                                    <h4><?= htmlspecialchars($upd['manager_name']) ?></h4>
                                    <p>Project Manager</p>
                                </div>
                            </div>
                            <div class="progress-circle" style="--p: <?= $upd['progress_percent'] ?>%" data-val="<?= $upd['progress_percent'] ?>"></div>
                        </div>

                        <div class="update-text">
                            <?= strip_tags(htmlspecialchars_decode($upd['description'])) ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <?php if($isPending): ?>
                            <span class="status-pill status-pending"><span class="dot"></span> Pending</span>
                            <a href="ceo_review.php?id=<?= $upd['id'] ?>" class="btn-action btn-review">Review Now</a>
                        <?php else: ?>
                            <span class="status-pill status-viewed"><span class="dot"></span> Viewed</span>
                            <a href="ceo_review.php?id=<?= $upd['id'] ?>" class="btn-action btn-details">Details</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</div>

</body>
</html>