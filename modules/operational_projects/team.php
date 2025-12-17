<?php
// modules/operational_projects/team.php
require_once "php/team_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/team.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* Project Progress Donut (The Request) */
        .stat-donut {
            position: relative; width: 50px; height: 50px; border-radius: 50%;
            background: conic-gradient(#27ae60 <?= $progPercent ?>%, #ecf0f1 0); /* Green for Progress */
            display: flex; align-items: center; justify-content: center;
        }
        .stat-donut::after { content: ""; position: absolute; width: 40px; height: 40px; border-radius: 50%; background: #fff; }
        .donut-text { position: absolute; z-index: 1; font-size: 0.8rem; font-weight: bold; color: #27ae60; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <?php if ($canManageTeam): ?>
    <div class="add-box">
        <h3 style="margin-top:0; color:#333; margin-bottom:15px;"><i class="fa-solid fa-user-plus" style="color:#ff8c00;"></i> Add Member</h3>
        <form method="POST" style="display:flex; gap:15px;">
            <select name="user_id" class="form-select" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                <option value="">-- User --</option>
                <?php foreach($availableUsers as $u): ?> <option value="<?= $u['id'] ?>"><?= $u['full_name_en'] ?></option> <?php endforeach; ?>
            </select>
            <select name="role_id" class="form-select" style="flex:1; padding:10px; border-radius:8px; border:1px solid #ddd;" required>
                <option value="">-- Role --</option>
                <?php foreach($projectRoles as $r): ?> <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option> <?php endforeach; ?>
            </select>
            <button type="submit" name="add_member" class="btn-primary">Add</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="team-grid">
        <div class="member-card" style="border-left: 4px solid #ff8c00;">
            <img src="<?= BASE_URL ?>/assets/uploads/avatars/default-profile.png" class="member-avatar">
            <div>
                <h4 style="margin:0;"><?= htmlspecialchars($project['manager_name']) ?></h4>
                <p style="margin:5px 0; color:#888; font-size:0.85rem;">Project Manager</p>
                <span class="role-badge role-pm">Owner</span>
            </div>
        </div>
        <?php foreach($teamMembers as $m): ?>
            <div class="member-card">
                <img src="<?= $m['avatar'] ? BASE_URL.'/assets/uploads/avatars/'.$m['avatar'] : BASE_URL.'/assets/uploads/avatars/default-profile.png' ?>" class="member-avatar">
                <div>
                    <h4 style="margin:0;"><?= htmlspecialchars($m['full_name_en']) ?></h4>
                    <p style="margin:5px 0; color:#888; font-size:0.85rem;"><?= htmlspecialchars($m['email']) ?></p>
                    <span class="role-badge"><?= htmlspecialchars($m['role_name']) ?></span>
                </div>
                <?php if ($canManageTeam): ?>
                    <a href="?id=<?= $id ?>&remove_uid=<?= $m['user_id'] ?>" onclick="return confirm('Remove?')" style="position:absolute; top:15px; right:15px; color:#e74c3c;">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
<script>
    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: 'success', title: 'Action Successful' });
</script>
<?php endif; ?>

</body>
</html>