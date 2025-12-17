<?php
// modules/users/delete.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php";

// التحقق من الصلاحية
if (!Auth::can('manage_users')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
    die("Access Denied");
}

// =========================================================
// معالجة طلب الحذف (POST via AJAX)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            throw new Exception("Invalid user ID");
        }

        // 1. منع حذف الحساب الشخصي
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['status' => 'blocked', 'message' => 'You cannot delete your own account']);
            exit;
        }

        // 2. التحقق من الارتباطات
        if (!canDeleteUser($id)) {
            echo json_encode([
                'status' => 'blocked', 
                'message' => 'Cannot delete: User is linked to active Projects, Initiatives, or Teams. Please reassign their work first.'
            ]);
            exit;
        }

        // 3. تنفيذ الحذف الناعم
        if (softDeleteUser($id)) {
            
            // محاولة تسجيل النشاط (دون إيقاف العملية إذا فشل السجل)
            try {
                $db = Database::getInstance()->pdo();
                $logStmt = $db->prepare("
                    INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, ip_address, created_at)
                    VALUES (?, 'soft_delete', 'user', ?, 'soft deleted', ?, NOW())
                ");
                $ip = $_SERVER['REMOTE_ADDR'] ?? '::1';
                $logStmt->execute([$_SESSION['user_id'], $id, $ip]);
            } catch (Exception $e) { /* تجاهل خطأ السجل */ }

            echo json_encode(['status' => 'success']);
            exit;
        } else {
            throw new Exception("Database failed to update record.");
        }

    } catch (Exception $e) {
        // اصطياد أي خطأ وإرساله كـ JSON
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// =========================================================
// عرض صفحة التأكيد (GET Request)
// =========================================================
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid ID");

$user = getUserById($id);
if (!$user) die("User not found or already deleted");

$avatarUrl = $user['avatar'] 
    ? BASE_URL . 'assets/uploads/avatars/' . $user['avatar'] 
    : BASE_URL . 'assets/uploads/avatars/default-profile.png';

$fullName = $user['full_name_ar'] ?: $user['full_name_en'] ?: $user['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete User</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/user_delete.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-trash"></i> Delete User
        </h1>
        <a href="list.php" class="btn-secondary">← Cancel</a>
    </div>

    <div class="delete-card">

        <h2 style="color: #d32f2f; font-family: Varela Round, sans-serif;font-weight: 400; font-style: normal;">Are you sure?</h2>
        <p>You are about to archive this user. This action can be undone by an admin later.</p>

        <div class="delete-user-info">
            <img src="<?= htmlspecialchars($avatarUrl) ?>" class="delete-avatar" alt="Avatar">
            <p class="delete-name"><?= htmlspecialchars($fullName) ?></p>
            <p class="delete-role"><?= htmlspecialchars($user['role_name'] ?? 'No Role') ?></p>
        </div>

        <div class="delete-actions">
            <a href="list.php" class="btn-cancel">Cancel</a>
            <button type="button" class="btn-delete-final" onclick="confirmDeletion(<?= $id ?>)">
                <i class="fa-solid fa-trash"></i> Confirm Delete
            </button>
        </div>

    </div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDeletion(id) {
    fetch('delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(response => {
        // التحقق من أن الاستجابة هي JSON صالح
        if (!response.ok) {
            throw new Error("HTTP Error: " + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                title: 'Deleted!',
                text: 'User has been archived.',
                icon: 'success'
            }).then(() => {
                window.location.href = 'list.php?success=user_deleted';
            });
        } else if (data.status === 'blocked') {
            Swal.fire('Cannot Delete', data.message, 'warning');
        } else {
            Swal.fire('Error', data.message || 'Something went wrong', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('Error', 'Request failed: ' + err.message, 'error');
    });
}
</script>

</body>
</html>