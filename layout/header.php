<?php
// layout/header.php

require_once __DIR__ . '/../modules/operational_projects/project_functions.php';
if (!defined('BASE_URL')) { require_once __DIR__ . '/../core/config.php'; }
require_once __DIR__ . '/../modules/todos/todo_functions.php';

$fullName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$roleName = $_SESSION['role_name'] ?? 'Team Member';
$userId   = $_SESSION['user_id'] ?? 0;

// استخدام الأفاتار المولد تلقائياً بألوان دافئة
$avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=FFF4E6&color=F58220&bold=true";
if (!empty($_SESSION['avatar'])) {
    $avatarUrl = BASE_URL . 'assets/uploads/avatars/' . $_SESSION['avatar'];
}

$pendingCount = 0;
if (isset($_SESSION['user_id'])) { $pendingCount = countPendingTodos($_SESSION['user_id']); }
?>

<header class="top-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>

        <div class="header-brand">
            <img src="<?php echo BASE_URL; ?>assets/images/logo.png" class="header-logo" alt="Logo">
            <div class="header-titles">
                <h1 class="app-name">Al Yamamah PMS</h1>
                <span class="app-subtitle">Strategic Management</span>
            </div>
        </div>
    </div>

    <div class="header-right">
        <div class="lang-switcher">
            <button class="lang-btn active">EN</button>
            <button class="lang-btn">AR</button>
        </div>

        <a href="<?php echo BASE_URL; ?>modules/todos/index.php" class="header-icon-btn" title="My Tasks">
            <i class="fa-regular fa-bell"></i>
            <?php if ($pendingCount > 0): ?>
                <span class="notification-badge"><?= $pendingCount > 9 ? '9+' : $pendingCount ?></span>
            <?php endif; ?>
        </a>

        <div class="user-dropdown" onclick="toggleUserMenu()">
            <button class="user-dropdown-toggle">
                
                <img src="<?php echo $avatarUrl; ?>" class="user-avatar-small" alt="Profile">
                <div class="user-text-info">
                    <span class="u-name"><?= htmlspecialchars($fullName) ?></span>
                    <span class="u-role"><?= htmlspecialchars($roleName) ?></span>
                </div>
                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </button>

            <div class="user-dropdown-menu">
                <a href="<?php echo BASE_URL; ?>modules/users/view.php?id=<?= (int)$userId ?>">
                    <i class="fa-regular fa-user"></i> My Profile
                </a>
                <a href="<?php echo BASE_URL; ?>modules/users/edit.php?id=<?= (int)$userId ?>">
                    <i class="fa-solid fa-sliders"></i> Settings
                </a>
                <div class="menu-divider"></div>
                <a href="<?php echo BASE_URL; ?>logout.php" class="logout-link">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleUserMenu() {
    document.querySelector(".user-dropdown").classList.toggle("active");
}
window.onclick = function(event) {
    if (!event.target.closest('.user-dropdown')) {
        document.querySelector(".user-dropdown").classList.remove('active');
    }
}
function toggleSidebar() {
    document.body.classList.toggle("sidebar-collapsed");
}
</script>