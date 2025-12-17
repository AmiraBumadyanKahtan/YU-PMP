<?php
// login.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/auth.php';

// 1. إذا كان المستخدم مسجل دخول بالفعل، وجهه للرئيسية
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = null;

// 2. معالجة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // الدالة Auth::login تقوم بالتحقق من قاعدة البيانات وفك التشفير
    if (Auth::login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password, or account is inactive.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Implementation System – Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/png" href="assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="container">

    <div class="left-section">
        <img src="assets/images/logo.png" class="yu-logo" alt="Logo">

        <h1 class="app-title">Strategic<br>Implementation<br>System</h1>
        <p class="app-sub">Al Yamamah University</p>

        <ul class="features">
            <li>Track Strategic Initiatives</li>
            <li>Collaborate with Teams</li>
            <li>Manage Tasks Efficiently</li>
            <li>Real-time Dashboards</li>
        </ul>
    </div>

    <div class="right-section">

        <h2 class="welcome">Welcome Back!</h2>

        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php 
        // رسالة تأكيد تسجيل الخروج
        if (isset($_GET['logout']) && $_GET['logout'] == 1) {
            echo '<div style="color: green; margin-bottom: 15px; font-weight: bold;">You have been logged out successfully.</div>';
        }
        ?>

        <form method="POST" class="login-form">

            <label>Email Address / Username</label>
            <input type="text" name="username" required autofocus>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit" class="btn-login">Login</button>

            <p class="version">Strategic Implementation Framework v1.3.0</p>
        </form>

    </div>

</div>

</body>
</html>