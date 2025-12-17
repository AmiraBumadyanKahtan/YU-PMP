<?php
// index.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/auth.php';

// التحقق من تسجيل الدخول
if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

// اختبار الاتصال بقاعدة البيانات للعرض في الداشبورد
try {
    $db = Database::getInstance()->pdo();
    $db_status = "Database Connected Successfully";
    $status_color = "green";
} catch (Exception $e) {
    $db_status = "DB Error: " . $e->getMessage();
    $status_color = "red";
}

// جلب اسم المستخدم من الجلسة (تم تعريف full_name في auth.php)
$userName = $_SESSION['full_name'] ?? $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – Strategic PMS</title>
    <link rel="icon" type="image/png" href="assets/images/favicon-32x32.png">
    
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/content.css"> 
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body style="margin: 0;">

    <?php include "layout/header.php"; ?>

    <?php include "layout/sidebar.php"; ?>

    <div class="main-content">
        <h1 style="font-family: Varela Round, sans-serif; font-weight: 400; font-style: normal;">
            Welcome back, <span style="color:#f58220;"><?php echo htmlspecialchars($userName); ?></span>
        </h1>

        <p style="font-family: Varela Round, sans-serif; font-weight: 400; font-style: normal;font-size:1.2rem; margin-top:10px; color: <?php echo $status_color; ?>;">
            <i class="fa fa-database"></i> <?php echo $db_status; ?>
        </p>

        <div style="font-family: Varela Round, sans-serif; font-weight: 400; font-style: normal;margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
            <h3>System Status</h3>
            <p>This is your main dashboard. You are logged in as <strong><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'User'); ?></strong>.</p>
            <p>Use the sidebar to navigate to Initiatives, Projects, or Tasks.</p>
        </div>
    </div>

</body>
</html>