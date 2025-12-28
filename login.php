<?php
// login.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/auth.php';

if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid credentials or inactive account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategic Project Management – Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="icon" type="image/png" href="assets/images/favicon-32x32.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body>

<div class="animated-bg">
    <div class="shape shape-1"></div>
    <div class="shape shape-2"></div>
    <div class="shape shape-3"></div>
</div>

<div class="container fade-in-up">

    <div class="left-section">
        <div class="overlay">
            
            <div class="brand-header">
                <img src="assets/images/logo.png" class="yu-logo" alt="YU Logo">
            </div>

            <div class="text-content">
                <h1 class="app-title">Strategic<br>Project Management</h1>
                <p class="app-sub">Al Yamamah University System</p>
                
                <div class="divider"></div>
                
                <div class="features-container">
                    <ul class="features">
                        <li>
                            <i class="fa-solid fa-chess-queen"></i>
                            <span>Strategic Implementation</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-list-check"></i>
                            <span>Project Portfolio Mgmt</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>KPIs & Performance</span>
                        </li>
                        <li>
                            <i class="fa-solid fa-users-viewfinder"></i>
                            <span>Team Collaboration</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <p class="copyright">© <?php echo date('Y'); ?> Al Yamamah University</p>
        </div>
    </div>

    <div class="right-section">
        <div class="login-wrapper">
            <h2 class="welcome">Welcome Back</h2>
            <p class="instruction">Please login to your dashboard</p>

            <?php if ($error): ?>
                <div class="login-alert error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['logout'])): ?>
                <div class="login-alert success">
                    <i class="fa-solid fa-check-circle"></i>
                    You have been logged out successfully.
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                
                <div class="input-group">
                    <label>Username / Email</label>
                    <div class="input-field">
                        <i class="fa-regular fa-user"></i>
                        <input type="text" name="username" placeholder="Enter your ID" required autofocus>
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-field">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="actions">
                    <label class="remember-me">
                        <input type="checkbox"> <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-pass">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login">
                    <span>Login System</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

                <p class="version">SPM System v2.0 - Stable</p>
            </form>
        </div>
    </div>

</div>

</body>
</html>