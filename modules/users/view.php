<?php
// modules/users/view.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php";

// التحقق من الدخول للنظام أولاً
if (!Auth::check()) {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid user ID");

// تحديد هل المستخدم هو صاحب الحساب؟
$isMe = ($_SESSION['user_id'] == $id);

// هل يملك صلاحية عرض المستخدمين؟
$canViewUsers = Auth::can('sys_user_view');

// السماح بالدخول إذا كان يملك صلاحية العرض أو هو صاحب الحساب
if (!$canViewUsers && !$isMe) {
    header("Location: ../../error/403.php");
    exit;
}

// جلب بيانات المستخدم
$user = getUserById($id);
if (!$user) die("User not found");

// إحصائيات المستخدم
$stats = getUserStats($id);

// تحديد صلاحيات التعديل والحذف
$canEdit   = Auth::can('sys_user_edit');
$canDelete = Auth::can('sys_user_delete');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Details</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/users.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* CSS retained from your code */
        .btn { padding: 8px 15px; border-radius: 8px; font-size: .9rem; text-decoration: none; border: none; cursor: pointer; font-family: "Varela Round", sans-serif; display: inline-flex; align-items: center; gap: 5px; }
        .btn-back { background: #eee; color: #444; } .btn-back:hover { background: #ff8c00; color: #444; }
        .btn-edit { background: #def5e7; color: #1b7f3a; } .btn-edit:hover{ color:#def5e7; background: #1b7f3a; }
        .btn-delete { background: #ffe4e4; color: #ad1c1c; } .btn-delete:hover{ color: #ffe4e4; background: #ad1c1c; }
        
        /* تنسيقات إضافية بسيطة للبادجات في صفحة التفاصيل */
        .badge-active { background: #def5e7; color: #1a7f37; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
        .badge-inactive { background: #ffe4e4; color: #c21d1d; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: bold; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-user"></i> User Details
        </h1>

        <div>
            <?php if ($canViewUsers): ?>
                <a href="list.php" class="btn btn-back" style="margin-right:10px;">← Back</a>
            <?php else: ?>
                <a href="../../index.php" class="btn btn-back" style="margin-right:10px;">← Dashboard</a>
            <?php endif; ?>

            <?php if ($canEdit || $isMe): ?>
                <a href="edit.php?id=<?= $id ?>" class="btn btn-edit" style="margin-right:10px;">
                    <i class="fa-solid fa-pen"></i> Edit
                </a>
            <?php endif; ?>

            <?php if ($canDelete): ?>
                <a href="delete.php?id=<?= $id ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to archive this user?')">
                    <i class="fa-solid fa-trash"></i> Delete
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="user-details-card">

        <div class="user-profile-left">
            <img src="<?= $user['avatar'] ? '../../assets/uploads/avatars/'.$user['avatar'] : '../../assets/uploads/avatars/default-profile.png' ?>" class="user-profile-avatar">
            <h2><?= htmlspecialchars($user['full_name_en']) ?></h2>
            <p class="role-label"><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></p>
            <p class="dept-label"><?= htmlspecialchars($user['department_name'] ?? '—') ?></p>
        </div>

        <div class="user-profile-info">

            <div class="info-row">
                <label>Username:</label>
                <span><?= htmlspecialchars($user['username']) ?></span>
            </div>

            <div class="info-row">
                <label>Email:</label>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>

            <div class="info-row">
                <label>Phone:</label>
                <span><?= htmlspecialchars($user['phone'] ?? '—') ?></span>
            </div>

            <div class="info-row">
                <label>Status:</label>
                <span>
                    <?= $user['is_active'] ? "<span class='badge-active'>Active</span>" : "<span class='badge-inactive'>Inactive</span>" ?>
                </span>
            </div>

            <div class="info-row">
                <label>Job Title:</label>
                <span><?= htmlspecialchars($user['job_title'] ?? '—') ?></span>
            </div>

            <div class="info-row">
                <label>Created At:</label>
                <span><?= date('d M Y, h:i A', strtotime($user['created_at'])) ?></span>
            </div>

            <div class="info-row">
                <label>Last Updated:</label>
                <span><?= date('d M Y, h:i A', strtotime($user['updated_at'])) ?></span>
            </div>

        </div>
    </div>

    <h2 class="section-title" style="margin-top:30px; margin-bottom:15px; font-weight:700; color:#444;">User Statistics</h2>

    <div class="stats-grid">

        <div class="stat-card">
            <i class="fa-solid fa-lightbulb stat-icon"></i>
            <div class="stat-number"><?= $stats['initiatives'] ?></div>
            <div class="stat-label">Initiatives Owned</div>
        </div>

        <div class="stat-card">
            <i class="fa-solid fa-diagram-project stat-icon"></i>
            <div class="stat-number"><?= $stats['projects'] ?></div>
            <div class="stat-label">Operational Projects</div>
        </div>

        <div class="stat-card">
            <i class="fa-solid fa-handshake stat-icon"></i>
            <div class="stat-number"><?= $stats['collaborations'] ?></div>
            <div class="stat-label">Collaborations</div>
        </div>

        <div class="stat-card">
            <i class="fa-solid fa-users-viewfinder stat-icon"></i>
            <div class="stat-number"><?= $stats['team_memberships'] ?></div>
            <div class="stat-label">Teams Involved</div>
        </div>

        <div class="stat-card">
            <i class="fa-solid fa-clock stat-icon"></i>
            <div class="stat-number">
                <?= $stats['last_login'] ? date('d M', strtotime($stats['last_login'])) : "—" ?>
            </div>
            <div class="stat-label">Last Login</div>
        </div>

    </div>

</div>
</div>

</body>
</html>