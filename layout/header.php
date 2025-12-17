<?php
// layout/header.php

// في أعلى الملف
require_once __DIR__ . '/../modules/operational_projects/project_functions.php';
checkAndSendProjectReminders();

// تأكد من وجود الثوابت (في حال تم تضمين الهيدر بشكل منفصل)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../core/config.php';
}
// 1. استدعاء ملف المهام لجلب العدد
// ملاحظة: نستخدم __DIR__ للوصول النسبي الصحيح للملف
require_once __DIR__ . '/../modules/todos/todo_functions.php';

// 1. جلب البيانات من الجلسة بناءً على ما تم حفظه في auth.php
// استخدمنا full_name في auth.php
$fullName = $_SESSION['full_name'] 
         ?? $_SESSION['username'] 
         ?? 'User';

// استخدمنا role_name في auth.php
$roleName = $_SESSION['role_name'] ?? null;
$userId   = $_SESSION['user_id'] ?? 0;

// معالجة الصورة الرمزية (Avatar)
// نستخدم خدمة UI Avatars كبديل في حال عدم وجود صورة، مع استخدام الاسم الصحيح
$avatarUrl = "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=f58220&color=fff";

// إذا كان هناك صورة مخزنة في الجلسة (تتطلب تعديل auth.php لحفظها، أو نعتمد الافتراضي حالياً)
if (!empty($_SESSION['avatar'])) {
    $avatarUrl = BASE_URL . 'assets/uploads/avatars/' . $_SESSION['avatar'];
}
// جلب عدد المهام المعلقة
$pendingCount = 0;
if (isset($_SESSION['user_id'])) {
    $pendingCount = countPendingTodos($_SESSION['user_id']);
}
?>
<style>
.header-icon-btn {
    position: relative;
    color: #555;
    font-size: 1.2rem;
    margin-right: 15px;
    text-decoration: none;
    padding: 5px;
}
.header-icon-btn:hover { color: #f58220; }
.notification-badge {
    position: absolute;
    top: -2px;
    right: -5px;
    background: #e74c3c;
    color: white;
    font-size: 0.7rem;
    padding: 2px 5px;
    border-radius: 10px;
    min-width: 15px;
    text-align: center;
    font-weight: bold;
}
</style>

<header class="top-header">
    <div class="header-left">

        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>

        <div class="header-brand">
            <img src="<?php echo BASE_URL; ?>assets/images/logo.png" class="header-logo" alt="Logo">
            <h1 class="header-title">Al Yamamah PMS</h1>
        </div>
    </div>

    <div class="header-right">
        <div class="lang-switcher">
            <button class="lang-btn active">EN</button>
            <button class="lang-btn">AR</button>
        </div>
        <a href="<?php echo BASE_URL; ?>modules/todos/index.php" class="header-icon-btn" title="My Tasks">
            <i class="fa-solid fa-check-circle"></i>
            <?php if ($pendingCount > 0): ?>
                <span class="notification-badge"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>

        <div class="user-dropdown" onclick="toggleUserMenu()">
            <button class="user-dropdown-toggle">
                <img src="<?php echo $avatarUrl; ?>" class="user-avatar-small" alt="Profile">
                
                <div class="user-info-small">
                    <div class="user-name-small"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($roleName): ?>
                        <div class="user-role-small"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>

                <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
            </button>

            <div class="user-dropdown-menu">
                <a href="<?php echo BASE_URL; ?>modules/users/view.php?id=<?= (int)$userId ?>">Profile</a>
                <a href="<?php echo BASE_URL; ?>modules/users/edit.php?id=<?= (int)$userId ?>">Settings</a>
                <a href="<?php echo BASE_URL; ?>logout.php">Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
function toggleUserMenu() {
    document.querySelector(".user-dropdown").classList.toggle("active");
}

function toggleSidebar() {
    document.body.classList.toggle("sidebar-collapsed");
}
</script>