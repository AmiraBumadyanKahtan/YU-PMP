<?php
// modules/users/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once 'user_functions.php';

// ✅ التعديل: استخدام صلاحية العرض المحددة
if (!Auth::can('sys_user_view')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// Load filters from GET
$filters = [
    'search'        => $_GET['search'] ?? '',
    'role_id'       => $_GET['role_id'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'status'        => $_GET['status'] ?? ''
];

$users = getFilteredUsers($filters);

// Get dropdown data
$db = Database::getInstance()->pdo();

$roles = $db->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted = 0 ORDER BY name")->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users List</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/users.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    <style>
        .alert-success {
            background: #dff5e3;
            border-left: 5px solid #4CAF50;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #2b7a38;
            font-weight: 600;
        }

        .alert-error {
            background: #ffe1e1;
            border-left: 5px solid #d9534f;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #b53838;
            font-weight: 600;
        }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'user_deleted'): ?>
            <div class="alert-success">User deleted successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_delete_self'): ?>
            <div class="alert-error">You cannot delete your own account.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
            <div class="alert-error">Failed to delete user. Try again.</div>
        <?php endif; ?>

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-users"></i> Users
            </h1>

            <?php if (Auth::can('sys_user_create')): ?>
                <a href="create.php" class="btn-primary">
                    + Add User
                </a>
            <?php endif; ?>
        </div>

        <form method="get" class="filter-bar" style="margin-bottom: 20px; display:flex; gap:15px; align-items:center;">

            <input 
                type="text" 
                name="search" 
                class="filter-input" 
                placeholder="Search name or email..." 
                value="<?= htmlspecialchars($filters['search']) ?>"
            >

            <select name="role_id" class="filter-select">
                <option value="">All Roles</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" 
                        <?= ($filters['role_id'] == $r['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="department_id" class="filter-select">
                <option value="">All Departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" 
                        <?= ($filters['department_id'] == $d['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Status</option>
                <option value="1" <?= ($filters['status'] === "1") ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= ($filters['status'] === "0") ? 'selected' : '' ?>>Inactive</option>
            </select>

            <button type="submit" class="btn-primary" style="padding:10px 18px;">
                <i class="fa-solid fa-filter"></i> Apply
            </button>

            
             <a href="list.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
        </form>


        <div class="user-table-wrapper" style="margin-top: 20px;">
            <table class="user-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Avatar</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($users) === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:30px;color:#777;">
                            No users found.
                        </td>
                    </tr>

                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php 
                            // تحضير رابط الصورة
                            $avatarRel = $u['avatar'] ? 'assets/uploads/avatars/' . $u['avatar'] : 'assets/uploads/avatars/default-profile.png';
                            $avatarUrl = (strpos($u['avatar'] ?? '', 'http') === 0) ? $u['avatar'] : BASE_URL . $avatarRel;
                            // تحضير الاسم
                            $fullName = $u['full_name_ar'] ?: $u['full_name_en'] ?: $u['username'];
                        ?>
                        <tr>
                            <td><?= $u['id'] ?></td>

                            <td>
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" class="user-avatar" alt="Avatar">
                            </td>

                            <td><?= htmlspecialchars($fullName) ?></td>

                            <td><?= htmlspecialchars($u['email']) ?></td>

                            <td><?= htmlspecialchars($u['role_name'] ?? '-') ?></td>

                            <td><?= htmlspecialchars($u['department_name'] ?? '-') ?></td>

                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>

                            <td class="actions">
                                <a href="view.php?id=<?= $u['id'] ?>" class="action-btn btn-view">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                
                                <?php if (Auth::can('sys_user_edit')): ?>
                                    <a href="edit.php?id=<?= $u['id'] ?>" class="action-btn btn-edit">
                                         <i class="fa-solid fa-pen"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('sys_user_delete')): ?>
                                    <a href="#" class="action-btn btn-delete"
                                       onclick="deleteUser(<?= $u['id'] ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ملاحظة: تأكد أن ملف delete.php موجود ويعالج الحذف
function deleteUser(id) {
    Swal.fire({
        title: 'Delete User?',
        text: 'This will archive the user (Soft Delete)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch('delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire('Deleted', 'User archived successfully', 'success')
                .then(() => location.reload());
            } else if (data.status === 'blocked') {
                Swal.fire('Blocked', data.message, 'error');
            } else {
                Swal.fire('Error', data.message || 'Unknown error', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Request failed', 'error');
            console.error(err);
        });
    });
}
</script>

</body>
</html>