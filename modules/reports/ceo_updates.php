<?php
// modules/reports/ceo_updates.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../modules/operational_projects/project_functions.php"; 

// التحقق من الصلاحية (CEO أو Super Admin)
if (!Auth::check() || !in_array($_SESSION['role_key'], ['ceo', 'super_admin'])) {
    die("Access Denied: Executives Only");
}

$db = Database::getInstance()->pdo();

// --- استقبال الفلاتر ---
$filter_project = $_GET['project_id'] ?? '';
$filter_dept    = $_GET['department_id'] ?? ''; // ✅ فلتر القسم الجديد
$filter_status  = $_GET['status'] ?? '';

// --- بناء الاستعلام ---
// قمنا بعمل JOIN مع جدول departments لجلب اسم القسم ولتفعيل الفلترة
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
    WHERE 1=1
";

$params = [];

if (!empty($filter_project)) {
    $sql .= " AND u.project_id = ?";
    $params[] = $filter_project;
}

if (!empty($filter_dept)) { // ✅ تطبيق فلتر القسم
    $sql .= " AND p.department_id = ?";
    $params[] = $filter_dept;
}

if (!empty($filter_status)) {
    $sql .= " AND u.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$updates = $stmt->fetchAll();

// --- جلب البيانات للقوائم المنسدلة ---
$projectsList = $db->query("SELECT id, name, project_code FROM operational_projects WHERE is_deleted=0 ORDER BY name")->fetchAll();
$departmentsList = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll(); // ✅ قائمة الأقسام

// إحصائيات سريعة
$pendingCount = 0;
foreach($updates as $up) { if($up['status']=='pending') $pendingCount++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CEO Updates Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .filter-bar { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-end; box-shadow: 0 2px 5px rgba(0,0,0,0.05); flex-wrap: wrap; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 0.9rem; color: #555; }
        .filter-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        
        .stats-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { flex: 1; background: #fff; padding: 20px; border-radius: 8px; border-left: 5px solid #3498db; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; }
        .stat-icon { font-size: 2.5rem; opacity: 0.2; }
        .stat-num { font-size: 2rem; font-weight: bold; color: #333; }
        .stat-label { color: #777; font-size: 0.9rem; }
        
        .update-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .update-card { background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #eee; transition: transform 0.2s; position: relative; display: flex; flex-direction: column; }
        .update-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        .card-header { padding: 15px; background: #f9f9f9; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .project-code-badge { font-size: 0.75rem; background: #34495e; color: #fff; padding: 3px 8px; border-radius: 4px; font-weight: 600; }
        .dept-badge { font-size: 0.75rem; background: #e3f2fd; color: #1565c0; padding: 3px 8px; border-radius: 4px; border: 1px solid #bbdefb; }
        
        .card-body { padding: 20px; flex: 1; }
        .manager-info { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        /* ✅ إصلاح الصورة: إضافة object-fit و background */
        .manager-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background-color: #eee; border: 1px solid #ddd; }
        
        .progress-circle { width: 50px; height: 50px; border-radius: 50%; background: conic-gradient(#3498db var(--p), #f0f0f0 0); display: flex; align-items: center; justify-content: center; position: relative; }
        .progress-circle::after { content: attr(data-val) '%'; position: absolute; background: #fff; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; }

        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .dot-pending { background: #f39c12; box-shadow: 0 0 5px #f39c12; }
        .dot-viewed { background: #2ecc71; }

        .card-footer { padding: 15px; border-top: 1px solid #eee; text-align: right; background: #fff; margin-top: auto; }
        .btn-review { background: #3498db; color: #fff; padding: 8px 20px; border-radius: 20px; text-decoration: none; font-size: 0.9rem; transition: background 0.2s; display: inline-block; }
        .btn-review:hover { background: #2980b9; }
        
        /* ✅ جعل زر التفاصيل قابلاً للنقر */
        .btn-viewed { background: #f8f9fa; color: #555; border: 1px solid #ddd; cursor: pointer; }
        .btn-viewed:hover { background: #e9ecef; color: #333; border-color: #ccc; }
    </style>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-chart-pie"></i> CEO Project Updates</h1>
    </div>

    <div class="stats-container">
        <div class="stat-card" style="border-color: #f39c12;">
            <div>
                <div class="stat-num"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <i class="fa-solid fa-hourglass-half stat-icon" style="color: #f39c12;"></i>
        </div>
        <div class="stat-card" style="border-color: #2ecc71;">
            <div>
                <div class="stat-num"><?= count($updates) - $pendingCount ?></div>
                <div class="stat-label">Reviewed Updates</div>
            </div>
            <i class="fa-solid fa-check-double stat-icon" style="color: #2ecc71;"></i>
        </div>
        <div class="stat-card">
            <div>
                <div class="stat-num"><?= count($updates) ?></div>
                <div class="stat-label">Total Updates Received</div>
            </div>
            <i class="fa-solid fa-list stat-icon" style="color: #3498db;"></i>
        </div>
    </div>

    <form class="filter-bar" method="GET">
        <div class="filter-group">
            <label>Department</label>
            <select name="department_id" class="filter-control">
                <option value="">All Departments</option>
                <?php foreach($departmentsList as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $filter_dept == $dept['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['name']) ?>
                    </option>
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
        
        <button type="submit" class="btn-primary" style="height: 42px; padding: 0 25px;">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        
        <?php if($filter_project || $filter_status || $filter_dept): ?>
            <a href="ceo_updates.php" class="btn-secondary" style="height: 42px; padding: 0 15px; display:inline-flex; align-items:center; text-decoration:none; justify-content:center;">Reset</a>
        <?php endif; ?>
    </form>

    <?php if (empty($updates)): ?>
        <div style="text-align:center; padding:50px; background:#fff; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <i class="fa-solid fa-inbox" style="font-size:3rem; color:#ddd; margin-bottom:15px;"></i>
            <p style="color:#777;">No updates found matching your criteria.</p>
        </div>
    <?php else: ?>
        <div class="update-grid">
            <?php foreach ($updates as $upd): ?>
                <?php 
                    $isPending = ($upd['status'] == 'pending');
                    
                    // ✅ إصلاح منطق الصورة: التأكد من المسار ووجود الصورة
                    $avatarPath = BASE_URL . '/assets/uploads/avatars/default-profile.png'; // الصورة الافتراضية
                    if (!empty($upd['avatar'])) {
                        // هنا نفترض أن الصور ترفع في هذا المسار
                        $avatarPath = BASE_URL . '/assets/uploads/avatars/' . $upd['avatar'];
                    }
                ?>
                <div class="update-card">
                    <div class="card-header">
                        <div style="display:flex; gap:5px;">
                            <span class="project-code-badge"><?= $upd['project_code'] ?></span>
                            <?php if(!empty($upd['department_name'])): ?>
                                <span class="dept-badge"><?= htmlspecialchars($upd['department_name']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:0.8rem; color:#888;">
                            <?= date('M d, Y', strtotime($upd['created_at'])) ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h3 style="margin-top:0; margin-bottom:15px; font-size:1.1rem; color:#333; line-height:1.4;">
                            <a href="../../modules/operational_projects/view.php?id=<?= $upd['project_id'] ?>" style="text-decoration:none; color:inherit;">
                                <?= htmlspecialchars($upd['project_name']) ?>
                            </a>
                        </h3>

                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                            <div class="manager-info">
                                <img src="<?= $avatarPath ?>" class="manager-avatar" alt="Manager">
                                <div>
                                    <div style="font-weight:bold; font-size:0.9rem;"><?= htmlspecialchars($upd['manager_name']) ?></div>
                                    <div style="font-size:0.75rem; color:#777;">Project Manager</div>
                                </div>
                            </div>
                            <div class="progress-circle" style="--p: <?= $upd['progress_percent'] ?>%" data-val="<?= $upd['progress_percent'] ?>"></div>
                        </div>

                        <p style="color:#666; font-size:0.9rem; line-height:1.5; height:60px; overflow:hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                            <?= strip_tags(htmlspecialchars_decode($upd['description'])) ?>
                        </p>
                    </div>

                    <div class="card-footer">
                        <?php if($isPending): ?>
                            <span style="float:left; margin-top:5px; font-size:0.8rem; color:#f39c12; font-weight:bold;">
                                <span class="status-dot dot-pending"></span> Pending Review
                            </span>
                            <a href="ceo_review.php?id=<?= $upd['id'] ?>" class="btn-review">Review Update</a>
                        <?php else: ?>
                            <span style="float:left; margin-top:5px; font-size:0.8rem; color:#2ecc71; font-weight:bold;">
                                <span class="status-dot dot-viewed"></span> Viewed
                            </span>
                            <a href="ceo_review.php?id=<?= $upd['id'] ?>" class="btn-review btn-viewed">Details</a>
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