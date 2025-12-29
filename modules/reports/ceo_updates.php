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
// FILTERS & MAIN QUERY
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
    <title>Project Updates</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Theme Colors & Layout --- */
        body { background-color: #f8f9fa; font-family: 'Varela Round', sans-serif; color: #2d3436; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* --- Header Section --- */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-title { font-size: 1.8rem; font-weight: 800; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }
        .page-title i { color: #ff8c00; }
        
        /* Tabs */
        .tabs-nav { background: #fff; padding: 5px; border-radius: 50px; display: inline-flex; border: 1px solid #eee; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .tabs-nav a { padding: 8px 20px; text-decoration: none; color: #636e72; border-radius: 40px; font-weight: 600; font-size: 0.9rem; transition: all 0.3s ease; }
        .tabs-nav a:hover { color: #ff8c00; background: #fff8e1; }
        .tabs-nav a.active { background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; box-shadow: 0 2px 8px rgba(255, 140, 0, 0.3); }

        /* --- Filter Bar (Redesigned) --- */
        .filter-bar { 
            background: #fff; padding: 20px 25px; border-radius: 16px; margin-bottom: 30px; 
            display: flex; gap: 20px; align-items: flex-end; box-shadow: 0 5px 20px rgba(0,0,0,0.03); 
            border: 1px solid #f1f2f6; flex-wrap: wrap;
        }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-weight: 700; margin-bottom: 8px; font-size: 0.85rem; color: #636e72; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-control { 
            width: 100%; height: 45px; padding: 0 15px; border: 2px solid #f1f2f6; border-radius: 10px; 
            background: #fdfdfd; font-family: inherit; font-size: 0.95rem; outline: none; transition: 0.2s; color: #2d3436;
        }
        .filter-control:focus { border-color: #ff8c00; background: #fff; }

        .btn-filter {  
            height: 45px; padding: 0 30px; font-weight: 700;
         gap: 8px; transition: 0.2s; 

            font-family: "Varela Round", sans-serif;
    background: linear-gradient(135deg, #ff8c00, #e67e00);
    color: white;
    border: none;
    border-radius: 30px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
        }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        .btn-reset { 
            background: #fff0f0; color: #c0392b; height: 45px; padding: 0 20px; border-radius: 30px; 
            text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; transition: 0.2s;
        }
        .btn-reset:hover { background: #ffebee; }

        /* --- Updates Grid --- */
        .update-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        
        .update-card { 
            background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #f0f0f0; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); transition: transform 0.2s; 
            display: flex; flex-direction: column; position: relative;
        }
        .update-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(0,0,0,0.08); border-color: #ffcc80; }

        .card-header { 
            padding: 15px 20px; background: #fff; border-bottom: 1px solid #f5f6fa; 
            display: flex; justify-content: space-between; align-items: center; 
        }
        .proj-code { font-size: 0.75rem; background: #f1f2f6; color: #636e72; padding: 4px 10px; border-radius: 20px; font-weight: 700; }
        .dept-badge { font-size: 0.75rem; background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 20px; font-weight: 700; }
        .update-date { font-size: 0.8rem; color: #b2bec3; font-weight: 600; }

        .card-body { padding: 25px; flex: 1; display: flex; flex-direction: column; gap: 15px; }
        
        .proj-title { 
            margin: 0; font-size: 1.1rem; font-weight: 800; color: #2d3436; line-height: 1.4; 
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .proj-title a { text-decoration: none; color: inherit; transition: color 0.2s; }
        .proj-title a:hover { color: #ff8c00; }

        .manager-row { display: flex; align-items: center; justify-content: space-between; }
        .manager-info { display: flex; align-items: center; gap: 10px; }
        .manager-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .manager-text h4 { margin: 0; font-size: 0.9rem; color: #2d3436; }
        .manager-text p { margin: 2px 0 0; font-size: 0.75rem; color: #95a5a6; }

        /* Progress Circle */
        .progress-mini { 
            width: 45px; height: 45px; border-radius: 50%; 
            background: conic-gradient(#ff8c00 var(--p), #f0f0f0 0); 
            display: flex; align-items: center; justify-content: center; 
        }
        .progress-mini::after { 
            content: attr(data-val) '%'; position: absolute; background: #fff; 
            width: 35px; height: 35px; border-radius: 50%; display: flex; 
            align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 800; color: #2d3436;
        }

        .update-excerpt { 
            color: #636e72; font-size: 0.9rem; line-height: 1.5; 
            background: #f8f9fa; padding: 12px; border-radius: 10px; 
            height: 60px; overflow: hidden; position: relative; font-style: italic;
        }

        .card-footer { 
            padding: 15px 20px; border-top: 1px dashed #eee; 
            display: flex; justify-content: space-between; align-items: center; background: #fff; 
        }
        
        .status-badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; 
            display: flex; align-items: center; gap: 6px; text-transform: uppercase;
        }
        .st-pending { background: #fff3e0; color: #f39c12; }
        .st-viewed { background: #e8f5e9; color: #27ae60; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

        .btn-action { 
            padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 0.8rem; font-weight: 700; transition: 0.2s; 
        }
        .btn-review { background: #ff8c00; color: #fff; box-shadow: 0 4px 10px rgba(255, 140, 0, 0.3); }
        .btn-review:hover { background: #e67e00; transform: translateY(-2px); }
        .btn-details { background: #f1f2f6; color: #636e72; }
        .btn-details:hover { background: #e2e6ea; color: #2d3436; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px; background: #fff; border-radius: 16px; border: 2px dashed #e0e0e0; color: #b2bec3; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-layer-group"></i> CEO Project Updates</h1>
        <div class="tabs-nav">
            <a href="../dashboard/ceo_org.php">Organization</a>
            <a href="../dashboard/ceo_projects.php">Projects & Ops</a>
            <a href="ceo_updates.php" class="active">Project Updates</a>
            <a href="../dashboard/ceo_summary.php">Summary</a>
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
                        <?= htmlspecialchars($p['name']) ?>
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
        
        <button type="submit" class="btn-filter">
            <i class="fa-solid fa-filter"></i> Apply
        </button>
        
        <?php if($filter_project || $filter_status || $filter_dept): ?>
            <a href="ceo_updates.php" class="btn-reset"><i class="fa-solid fa-xmark"></i> Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($updates)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open" style="font-size:4rem; margin-bottom:15px; opacity:0.5;"></i>
            <h3>No updates found</h3>
            <p>Adjust filters or check back later.</p>
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
                        <span class="update-date"><?= date('M d', strtotime($upd['created_at'])) ?></span>
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
                            <div class="progress-mini" style="--p: <?= $upd['progress_percent'] ?>%" data-val="<?= $upd['progress_percent'] ?>"></div>
                        </div>

                        <div class="update-excerpt">
                            <?= strip_tags(htmlspecialchars_decode($upd['description'])) ?>
                        </div>
                    </div>

                    <div class="card-footer">
                        <?php if($isPending): ?>
                            <span class="status-badge st-pending"><span class="dot"></span> Pending</span>
                            <a href="ceo_review.php?id=<?= $upd['id'] ?>" class="btn-action btn-review">Review</a>
                        <?php else: ?>
                            <span class="status-badge st-viewed"><span class="dot"></span> Viewed</span>
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