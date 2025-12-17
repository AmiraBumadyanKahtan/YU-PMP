<?php
// modules/users/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php"; // لاستخدام getUserById و updateUser

if (!Auth::can('manage_users')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

// 1. التحقق من ID المستخدم
$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<h2 style='text-align:center; margin-top:50px;'>Invalid User ID</h2>";
    exit;
}

// 2. جلب بيانات المستخدم باستخدام الدالة المحدثة
$user = getUserById($id);

if (!$user) {
    echo "<h2 style='text-align:center; margin-top:50px;'>User not found or deleted</h2>";
    exit;
}

// 3. جلب القوائم المنسدلة
$db = Database::getInstance()->pdo();
$roles = $db->query("SELECT id, role_name FROM roles ORDER BY role_name ASC")->fetchAll();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();

$message = "";

// 4. معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // معالجة رفع الصورة
        $avatarName = $user['avatar']; // الاحتفاظ بالصورة القديمة افتراضياً

        if (!empty($_FILES['avatar']['name'])) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newName = "user_" . $id . "_" . time() . "." . $ext;
                
                // المسار الفعلي (Absolute Path)
                $targetDir = __DIR__ . '/../../assets/uploads/avatars/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

                $uploadPath = $targetDir . $newName;

                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $avatarName = $newName;
                }
            }
        }

        // تجهيز البيانات للتحديث
        $updateData = [
            'username'        => $_POST['username'], // إضافة إمكانية تعديل اليوزر نيم
            'email'           => $_POST['email'],
            'full_name_en'    => $_POST['full_name_en'],
            'primary_role_id' => $_POST['role_id'],
            'department_id'   => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
            'phone'           => $_POST['phone'],
            'job_title'       => $_POST['job_title'],
            'is_active'       => $_POST['is_active'],
            'avatar'          => $avatarName
        ];

        // استدعاء دالة التحديث
        $result = updateUser($id, $updateData);

        // ... بعد السطر: $result = updateUser($id, $updateData); ...

        if ($result['ok']) {
            // ✅ تحديث الفروع
            $branches = $_POST['branches'] ?? [];
            updateUserBranches($id, $branches);

            $message = "success";
            // ...
        }

        if ($result['ok']) {
            $message = "success";
            // تحديث بيانات المستخدم المعروضة في الصفحة
            $user = getUserById($id); 
        } else {
            $message = "error";
            $errorDetail = $result['error']; // تفاصيل الخطأ (مثل تكرار الايميل)
        }

    } catch (Exception $e) {
        $message = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/edit.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .current-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid #ddd; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-user-pen"></i> Edit User: <span style="color:#666; font-size:0.8em;"><?= htmlspecialchars($user['username']) ?></span>
        </h1>

        <a href="list.php" class="btn-secondary action-btn">← Back</a>
    </div>

    <?php if ($message === "success"): ?>
        <div class="alert alert-success">
            <i class="fa-solid fa-check-circle"></i> User updated successfully.
        </div>
    <?php elseif ($message === "error"): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i> Failed to update user. 
            <?= isset($errorDetail) ? "($errorDetail)" : "" ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-card">

        <div class="form-grid">

            <div>
                <label>Username <span style="color:red">*</span></label>
                <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>">

                <label>Full Name (EN) <span style="color:red">*</span></label>
                <input type="text" name="full_name_en" required value="<?= htmlspecialchars($user['full_name_en']) ?>">

                <label>Email <span style="color:red">*</span></label>
                <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">

                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">

                <label>Job Title</label>
                <input type="text" name="job_title" value="<?= htmlspecialchars($user['job_title']) ?>">
            </div>

            <div>
                <label>Role <span style="color:red">*</span></label>
                <select name="role_id" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $r['id'] == $user['primary_role_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Department</label>
                <select name="department_id">
                    <option value="">— None —</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == $user['department_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Status</label>
                <select name="is_active">
                    <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Inactive</option>
                </select>

                <label>Avatar</label>
                <?php 
                    $avatarUrl = $user['avatar'] 
                        ? BASE_URL . 'assets/uploads/avatars/' . $user['avatar'] 
                        : BASE_URL . 'assets/uploads/avatars/default-profile.png';
                ?>
                <img src="<?= htmlspecialchars($avatarUrl) ?>" class="current-avatar" alt="Current Avatar">
                <input style="width: 95%;" type="file" name="avatar" accept="image/*">
                <p style="font-size: 0.85rem; color: #888; margin-top: 5px;">Leave empty to keep current avatar.</p>
            </div>
            <?php
            // جلب الفروع والفروع الحالية للمستخدم
            $allBranches = getAllBranches();
            $userBranches = getUserBranches($id);
            ?>

            <div style="grid-column: 1 / -1; margin-top:15px;">
                <label style="font-weight:bold; color:#555; display:block; margin-bottom:8px;">Assigned Branches</label>
                <div style="display:flex; flex-wrap:wrap; gap:15px; background:#f8f9fa; padding:15px; border-radius:5px; border:1px solid #eee;">
                    <?php foreach ($allBranches as $b): ?>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                            <input type="checkbox" name="branches[]" value="<?= $b['id'] ?>"
                                <?= in_array($b['id'], $userBranches) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <button type="submit" class="btn-primary" style="margin-top:20px;">Save Changes</button>

    </form>

</div>
</div>

</body>
</html>