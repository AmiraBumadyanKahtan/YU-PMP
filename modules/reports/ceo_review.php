<?php
// modules/reports/ceo_review.php

require_once "../../core/config.php";
require_once "../../core/auth.php";

// استدعاء دالة التحديثات الموجودة في project_functions
require_once "../../modules/operational_projects/project_functions.php";

if (!Auth::check() || $_SESSION['role_key'] != 'ceo') {
    // يمكنك السماح للسوبر ادمن أيضاً إذا أردت
    if($_SESSION['role_key'] != 'super_admin') die("Access Denied: CEO Only");
}

$updateId = $_GET['id'] ?? 0;
if (!$updateId) die("Invalid Update ID");

// 1. وضع التحديث كـ "تمت المشاهدة" (Viewed)
// هذه الدالة موجودة في project_updates.php الذي تم تضمينه عبر project_functions.php
markUpdateAsViewed($updateId);

// 2. جلب تفاصيل التحديث للعرض
$db = Database::getInstance()->pdo();
$update = $db->query("
    SELECT u.*, p.name as project_name, p.project_code, us.full_name_en as manager_name 
    FROM project_updates u
    JOIN operational_projects p ON p.id = u.project_id
    JOIN users us ON us.id = u.user_id
    WHERE u.id = $updateId
")->fetch();

if (!$update) die("Update not found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Update: <?= htmlspecialchars($update['project_name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .review-container { max-width: 700px; margin: 50px auto; background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        .check-icon { font-size: 5rem; color: #2ecc71; margin-bottom: 20px; animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .project-title { color: #34495e; margin-bottom: 5px; }
        .update-box { background: #f8f9fa; border: 1px solid #eee; border-radius: 8px; padding: 20px; text-align: left; margin: 30px 0; }
        .update-box h4 { margin-top: 0; color: #555; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
        
        @keyframes popIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body style="background-color: #f4f6f9; margin:0;">

<?php include "../../layout/header.php"; ?>

<div class="main-content">
    <div class="review-container">
        
        <i class="fa-solid fa-circle-check check-icon"></i>
        
        <h1 style="margin-top:0;">Update Marked as Viewed</h1>
        <p style="color:#777; font-size:1.1rem;">
            You have successfully reviewed the progress update for:
        </p>
        <h2 class="project-title"><?= htmlspecialchars($update['project_name']) ?> <span style="font-size:0.6em; color:#999;">(<?= $update['project_code'] ?>)</span></h2>

        <div class="update-box">
            <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
                <span><strong>Manager:</strong> <?= htmlspecialchars($update['manager_name']) ?></span>
                <span><strong>Date:</strong> <?= date('d M Y', strtotime($update['created_at'])) ?></span>
            </div>
            
            <div style="margin-bottom:15px;">
                <strong>Reported Progress:</strong> 
                <span style="font-size:1.2rem; font-weight:bold; color:#3498db; margin-left:10px;">
                    <?= $update['progress_percent'] ?>%
                </span>
            </div>

            <h4>Manager's Note:</h4>
            <p style="line-height:1.6; color:#444; white-space: pre-line;">
                <?= htmlspecialchars($update['description']) ?>
            </p>
        </div>

        <a href="ceo_updates.php" class="btn-primary" style="padding:12px 30px; text-decoration:none;">
            Back to Dashboard
        </a>
        
        <a href="../../modules/operational_projects/view.php?id=<?= $update['project_id'] ?>" class="btn-secondary" style="margin-left:10px; padding:12px 30px; text-decoration:none;">
            Go to Project
        </a>

    </div>
</div>

</body>
</html>