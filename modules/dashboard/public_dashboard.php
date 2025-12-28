<?php
// public_projects.php

// 1. تصحيح المسارات لأن الملف في الـ Root
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::check()) { header("Location: login.php"); exit; }

$db = Database::getInstance()->pdo();

// جلب المشاريع العامة
$publicProjects = $db->query("
    SELECT p.*, 
           d.name AS dept_name, 
           s.name AS status_name, s.color AS status_color,
           u.full_name_en as manager_name, u.avatar as manager_avatar
    FROM operational_projects p
    LEFT JOIN departments d ON d.id = p.department_id
    LEFT JOIN operational_project_statuses s ON s.id = p.status_id
    LEFT JOIN users u ON u.id = p.manager_id
    WHERE p.visibility = 'public' AND p.is_deleted = 0
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Public Projects Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">

    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
<style>
        /* --- Artistic Theme Override --- */
        body { font-family: "Varela Round", sans-serif; background-color: #f8f9fa; color: #2d3436; margin: 0; }
        .page-wrapper {
            padding: 30px;
            font-family: "Varela Round", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        /* Page Header */
        .page-header-flex {
            font-family: "Varela Round", sans-serif;
            font-weight: 400;
            font-style: normal;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .page-title {
            font-family: "Varela Round", sans-serif;
            font-weight: 400;
            font-style: normal;
            font-size: 2rem;
            font-weight: 700;
            color: #ff8c00;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Header */
        .page-title i { color: #ff8c00; }
        .header-desc { color: #7f8c8d; font-size: 1rem; margin-top: 5px; }

        /* Grid System */
        .public-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); 
            gap: 30px; 
        }
        
        /* --- Artistic Card Design --- */
        .art-card { 
            background: #ffffff; 
            border-radius: 24px; 
            padding: 30px; 
            position: relative; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); /* Bouncy effect */
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid #f0f2f5;
            display: flex; flex-direction: column;
            overflow: hidden;
            height: 100%;
        }
        
        .art-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 20px 40px rgba(255, 140, 0, 0.15); /* Orange Glow */
            border-color: rgba(255, 140, 0, 0.3);
        }

        /* Decorative Top Line */
        .art-card::after {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 6px;
            background: linear-gradient(90deg, #ff8c00, #ffeb3b);
            opacity: 0; transition: 0.3s;
        }
        .art-card:hover::after { opacity: 1; }
        
        /* Header Section */
        .art-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .art-code { 
            font-size: 0.75rem; color: #b2bec3; font-weight: 700; letter-spacing: 1px; 
            text-transform: uppercase; background: #f8f9fa; padding: 5px 10px; border-radius: 8px; 
        }
        
        /* Status Badge */
        .art-status { 
            padding: 6px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 800; 
            text-transform: uppercase; display: inline-block;
        }

        /* Content */
        .art-title { 
            font-size: 1.4rem; font-weight: 800; color: #2d3436; margin: 0 0 10px 0; 
            line-height: 1.3; transition: 0.2s;
        }
        .art-card:hover .art-title { color: #ff8c00; }
        
        .art-dept { font-size: 0.85rem; color: #636e72; display: flex; align-items: center; gap: 8px; margin-bottom: 15px; font-weight: 600; }
        .art-desc { 
            font-size: 0.9rem; color: #95a5a6; line-height: 1.6; margin-bottom: 25px; flex: 1;
            display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;
        }

        /* Manager & Progress */
        .art-meta-row { 
            display: flex; justify-content: space-between; align-items: flex-end; 
            margin-top: auto; padding-top: 20px; border-top: 1px dashed #eee;
        }
        
        .manager-info { display: flex; align-items: center; gap: 10px; }
        .manager-img { 
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover; 
            border: 2px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); 
        }
        .manager-details div { line-height: 1.2; }
        .m-name { font-size: 0.85rem; font-weight: 700; color: #2d3436; }
        .m-label { font-size: 0.7rem; color: #b2bec3; }

        /* Progress Circle */
        .prog-circle-wrapper { position: relative; width: 50px; height: 50px; }
        .prog-svg { transform: rotate(-90deg); width: 100%; height: 100%; }
        .prog-bg { fill: none; stroke: #f0f0f0; stroke-width: 4; }
        .prog-val { 
            fill: none; stroke-width: 4; stroke-linecap: round; 
            stroke-dasharray: 125; /* 2 * PI * R (approx 20) */
            transition: stroke-dashoffset 1s ease;
        }
        .prog-text { 
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            font-size: 0.75rem; font-weight: 700; color: #2d3436; 
        }

        /* View Button (Full Width Bottom) */
        .art-btn {
            display: block; width: 100%; text-align: center; padding: 14px 0;
            margin-top: 20px; background: #fff; color: #2d3436; 
            font-weight: 700; text-decoration: none; border-radius: 12px;
            border: 2px solid #f1f2f6; transition: 0.3s;
        }
        .art-btn:hover {
            background: linear-gradient(135deg, #ff8c00, #ffba00);
            color: #fff; border-color: transparent;
            box-shadow: 0 10px 20px rgba(255, 140, 0, 0.3);
        }

        /* Empty State */
        .empty-art { 
            grid-column: 1 / -1; text-align: center; padding: 80px; 
            background: #fff; border-radius: 30px; box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        }
        .empty-art i { font-size: 5rem; color: #f1f2f6; margin-bottom: 20px; }
        .empty-art h3 { color: #b2bec3; font-weight: 400; }

    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-globe"></i> Transparency Portal</h1>
            <p class="header-desc">Public overview of our organization's ongoing strategic projects.</p>
        </div>
    </div>

    <div class="public-grid">
        <?php if(empty($publicProjects)): ?>
            <div class="empty-art">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No public projects available at the moment.</h3>
            </div>
        <?php else: ?>
            <?php foreach($publicProjects as $p): 
                // Setup
                $avatar = !empty($p['manager_avatar']) ? '../../assets/uploads/avatars/'.$p['manager_avatar'] : '../../assets/uploads/avatars/default-profile.png';
                
                $percent = $p['progress_percentage'];
                $radius = 20;
                $circumference = 2 * 3.14159 * $radius;
                $offset = $circumference - ($percent / 100) * $circumference;
                $statusColor = $p['status_color'] ?: '#999';
                $viewLink = BASE_URL."modules/operational_projects/view.php?id=" . $p['id'];
                
                // Status Badge Background (Lighter version of status color)
                // This is a CSS trick, using opacity if hex is simple, or just a default light gray with colored text
                $badgeStyle = "color: $statusColor; background: rgba(0,0,0,0.05); border: 1px solid $statusColor;";
            ?>
                <div class="art-card">
                    
                    <div class="art-header">
                        <span class="art-code"><?= $p['project_code'] ?></span>
                        <span class="art-status" style="<?= $badgeStyle ?>">
                            <?= $p['status_name'] ?>
                        </span>
                    </div>
                    
                    <h3 class="art-title"><?= htmlspecialchars($p['name']) ?></h3>
                    
                    <div class="art-dept">
                        <i class="fa-solid fa-building-columns" style="color:#dfe6e9;"></i>
                        <?= htmlspecialchars($p['dept_name'] ?? 'General') ?>
                    </div>

                    <div class="art-desc">
                        <?= htmlspecialchars(strip_tags($p['description'])) ?>
                    </div>

                    <div class="art-meta-row">
                        <div class="manager-info">
                            <img src="<?= $avatar ?>" class="manager-img" alt="Manager">
                            <div class="manager-details">
                                <div class="m-name"><?= htmlspecialchars($p['manager_name'] ?? 'N/A') ?></div>
                                <div class="m-label">Project Manager</div>
                            </div>
                        </div>

                        <div class="prog-circle-wrapper" title="<?= $percent ?>% Completed">
                            <svg class="prog-svg" viewBox="0 0 50 50">
                                <circle class="prog-bg" cx="25" cy="25" r="<?= $radius ?>"></circle>
                                <circle class="prog-val" cx="25" cy="25" r="<?= $radius ?>" 
                                        stroke="<?= $statusColor ?>" 
                                        stroke-dasharray="<?= $circumference ?>" 
                                        stroke-dashoffset="<?= $offset ?>"></circle>
                            </svg>
                            <div class="prog-text"><?= $percent ?>%</div>
                        </div>
                    </div>

                    <a href="<?= $viewLink ?>" class="art-btn">
                        View Project Details <i class="fa-solid fa-arrow-right" style="margin-left:5px;"></i>
                    </a>

                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</div>
</body>
</html>