<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php";



if (!Auth::can('manage_users')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid user ID");

// جلب بيانات المستخدم
$user = getUserById($id);
if (!$user) die("User not found");

// إحصائيات المستخدم
$stats = getUserStats($id);
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        .btn {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: .9rem;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: #eee;
            color: #444;
        }
        .btn-back:hover {
            background: #ff8c00;
            color: #444;
        }

        .btn-edit { font-family: "Varela Round", sans-serif;
  font-weight: 400;
  font-style: normal;background: #def5e7; color: #1b7f3a; }
        .btn-edit:hover{
            color:#def5e7;
            background: #1b7f3a;
        }
        .btn-delete {font-family: "Varela Round", sans-serif;
  font-weight: 400;
  font-style: normal; background: #ffe4e4; color: #ad1c1c; }
        .btn-delete:hover{
            color: #ffe4e4;
            background: #ad1c1c;
        }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <!-- PAGE HEADER -->
    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-user"></i> User Details
        </h1>

        <div>
            <a href="list.php" class="btn btn-back" style="margin-right:10px;">← Back</a>
            <a href="edit.php?id=<?= $id ?>" class="btn btn-edit" style="margin-right:10px;">Edit</a>
            <a href="delete.php?id=<?= $id ?>"class="btn btn-delete"
               onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
        </div>
    </div>

    <!-- MAIN USER CARD -->
    <div class="user-details-card">

        <div class="user-profile-left">
            <img src="<?= $user['avatar'] ? '../../assets/uploads/avatars/'.$user['avatar'] : '../../assets/uploads/avatars/default-profile.png' ?>"
                 class="user-profile-avatar">
            <h2><?= htmlspecialchars($user['full_name_en']) ?></h2>
            <p class="role-label"><?= htmlspecialchars($user['role_name']) ?></p>
            <p class="dept-label"><?= $user['department_name'] ?: '—' ?></p>
        </div>

        <div class="user-profile-info">

            <div class="info-row">
                <label>Email:</label>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>

            <div class="info-row">
                <label>Status:</label>
                <span>
                    <?= $user['is_active'] ? "<span class='badge-active'>Active</span>" : "<span class='badge-inactive'>Inactive</span>" ?>
                </span>
            </div>

            <div class="info-row">
                <label>Created At:</label>
                <span><?= $user['created_at'] ?></span>
            </div>

            <div class="info-row">
                <label>Last Updated:</label>
                <span><?= $user['updated_at'] ?></span>
            </div>

        </div>
    </div>

    <!-- USER STATISTICS -->
    <h2 class="section-title">User Statistics</h2>

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
            <div class="stat-number"><?= $stats['last_login'] ?: "—" ?></div>
            <div class="stat-label">Last Login</div>
        </div>

    </div>

</div>
</div>

</body>
</html>
