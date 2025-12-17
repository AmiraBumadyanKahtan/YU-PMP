<?php
// public_dashboard.php (في المجلد الرئيسي أو modules/public)
require_once "core/config.php";
require_once "core/auth.php";
require_once "core/Database.php";

if (!Auth::check()) { header("Location: login.php"); exit; }

// دالة مخصصة (أو يمكن وضعها في ملف)
$db = Database::getInstance()->pdo();
$publicProjects = $db->query("
    SELECT p.*, d.name AS dept_name, s.name AS status_name, s.color AS status_color
    FROM operational_projects p
    LEFT JOIN departments d ON d.id = p.department_id
    LEFT JOIN operational_project_statuses s ON s.id = p.status_id
    WHERE p.visibility = 'public' AND p.is_deleted = 0
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Public Projects Dashboard</title>
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/content.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .public-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .pub-card { background: #fff; padding: 20px; border-radius: 8px; border-top: 4px solid #3498db; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .pub-card:hover { transform: translateY(-5px); }
        .pub-status { float: right; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; color: #fff; }
        .pub-code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; color: #555; }
    </style>
</head>
<body style="margin:0;">

<?php include "layout/header.php"; ?>
<?php include "layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-globe"></i> Organization Projects (Transparency View)</h1>
    </div>

    <div class="public-grid">
        <?php foreach($publicProjects as $p): ?>
            <div class="pub-card">
                <div style="margin-bottom:10px;">
                    <span class="pub-code"><?= $p['project_code'] ?></span>
                    <span class="pub-status" style="background-color: <?= $p['status_color'] ?: '#777' ?>;">
                        <?= $p['status_name'] ?>
                    </span>
                </div>
                
                <h3 style="margin: 0 0 10px 0; color: #2c3e50;"><?= htmlspecialchars($p['name']) ?></h3>
                
                <p style="color: #777; font-size: 0.9rem; margin-bottom: 15px; height: 40px; overflow: hidden;">
                    <?= htmlspecialchars(substr($p['description'], 0, 100)) ?>...
                </p>

                <div style="font-size: 0.85rem; color: #555; border-top: 1px solid #eee; padding-top: 10px;">
                    <div><i class="fa-solid fa-building"></i> <?= htmlspecialchars($p['dept_name']) ?></div>
                    <div style="margin-top:5px;"><i class="fa-regular fa-calendar"></i> <?= $p['start_date'] ?> to <?= $p['end_date'] ?></div>
                    
                    <div style="margin-top:10px; background:#eee; height:6px; border-radius:3px; overflow:hidden;">
                        <div style="width:<?= $p['progress_percentage'] ?>%; background:#2ecc71; height:100%;"></div>
                    </div>
                    <div style="text-align:right; font-size:0.8rem; margin-top:2px;"><?= $p['progress_percentage'] ?>%</div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>
</body>
</html>