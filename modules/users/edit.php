<?php
// modules/users/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php";

// 1. التحقق من الدخول
if (!Auth::check()) {
    header("Location: ../../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    die("Invalid User ID");
}

// تحديد الصلاحيات
$isMe = ($_SESSION['user_id'] == $id);
$canManageUsers = Auth::can('sys_user_edit'); // هل هو مدير يملك صلاحية تعديل المستخدمين؟

// إذا لم يكن المدير ولا صاحب الحساب، يمنع من الدخول
if (!$canManageUsers && !$isMe) {
    header("Location: ../../error/403.php");
    exit;
}

// 2. جلب البيانات
$user = getUserById($id);
if (!$user) die("User not found");

// 3. جلب القوائم
$db = Database::getInstance()->pdo();
$roles = $db->query("SELECT id, role_name FROM roles ORDER BY role_name ASC")->fetchAll();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();
$allBranches = getAllBranches();
$userBranches = getUserBranches($id);

$message = "";
$errorDetail = "";

// 4. معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $avatarName = $user['avatar']; 
        
        // رفع الصورة (مسموح للجميع)
        if (!empty($_FILES['avatar']['name'])) {
            $file = $_FILES['avatar'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newName = "user_" . $id . "_" . time() . "." . $ext;
                $targetDir = __DIR__ . '/../../assets/uploads/avatars/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                if (move_uploaded_file($file['tmp_name'], $targetDir . $newName)) {
                    $avatarName = $newName;
                }
            }
        }

        $updateData = [];
        $updateData['avatar'] = $avatarName;

        // تحديث البيانات (للمدير فقط)
        // إذا كان المستخدم العادي يحاول تعديل بياناته، لن نأخذ القيم من الـ POST للحقول المحمية
        if ($canManageUsers) {
            $updateData['username']        = $_POST['username'];
            $updateData['email']           = $_POST['email'];
            $updateData['full_name_en']    = $_POST['full_name_en'];
            $updateData['primary_role_id'] = $_POST['role_id'];
            $updateData['department_id']   = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
            $updateData['phone']           = $_POST['phone'];
            $updateData['job_title']       = $_POST['job_title'];
            $updateData['is_active']       = $_POST['is_active'];
        } else {
            // للمستخدم العادي: يمكنه تحديث بياناته الأساسية فقط (اختياري، هنا سمحت بالهاتف فقط كمثال)
            // يمكنك إضافة full_name_en هنا إذا أردت السماح له بتغيير اسمه
             $updateData['phone'] = $_POST['phone']; 
        }

        $result = updateUser($id, $updateData);

        if ($result['ok']) {
            // تحديث الفروع (فقط للمدير)
            if ($canManageUsers) {
                $branches = $_POST['branches'] ?? [];
                updateUserBranches($id, $branches);
            }
            $message = "success";
            $user = getUserById($id); // Refresh data
            $userBranches = getUserBranches($id);
        } else {
            $message = "error";
            $errorDetail = $result['error'] ?? 'Unknown error';
        }

    } catch (Exception $e) {
        $message = "error";
        $errorDetail = $e->getMessage();
    }
}

// خاصية التعطيل (Disabled Attribute)
// إذا كان مديراً، الحقل مفعّل. إذا لم يكن، الحقل معطل.
$disabledAttr = $canManageUsers ? '' : 'disabled';
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>

<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-user-pen"></i> Edit Profile
        </h1>
        <?php if($canManageUsers): ?>
            <a href="list.php" class="btn-secondary">← Back to List</a>
        <?php else: ?>
            <a href="../../index.php" class="btn-secondary">← Back to Dashboard</a>
        <?php endif; ?>
    </div>

    <?php if ($message === "success"): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Saved!',
                text: 'User profile updated successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    <?php elseif ($message === "error"): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?= addslashes($errorDetail) ?>'
            });
        </script>
    <?php endif; ?>

    <?php if (!$canManageUsers): ?>
        <div class="readonly-alert" style="background:#fff3cd; color:#856404; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ffeeba;">
            <i class="fa-solid fa-lock"></i>
            <span>You are viewing in restricted mode. You can only update your <b>Avatar</b> and <b>Phone</b>. Contact admin for other changes.</span>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-card">
        
        <div class="form-grid">
            
            <div class="col-left">
                <div class="form-group">
                    <label>Username <span style="color:red">*</span></label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($user['username']) ?>" <?= $disabledAttr ?>>
                </div>

                <div class="form-group">
                    <label>Full Name (EN) <span style="color:red">*</span></label>
                    <input type="text" name="full_name_en" required value="<?= htmlspecialchars($user['full_name_en']) ?>" <?= $disabledAttr ?>>
                </div>

                <div class="form-group">
                    <label>Email Address <span style="color:red">*</span></label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>" <?= $disabledAttr ?>>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
            </div>

            <div class="col-right">
                
                <div class="form-group">
                    <label>Profile Avatar</label>
                    <div class="avatar-section">
                        <?php 
                            $avatarUrl = $user['avatar'] 
                                ? BASE_URL . 'assets/uploads/avatars/' . $user['avatar'] 
                                : BASE_URL . 'assets/uploads/avatars/default-profile.png';
                        ?>
                        <div class="avatar-preview-box">
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" id="avatarPreview" alt="Avatar">
                        </div>
                        <div class="file-input-wrapper">
                            <input type="file" name="avatar" id="avatarInput" accept="image/*">
                            <div style="font-size:0.8rem; color:#888; margin-top:5px;">Max size: 2MB</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" name="job_title" value="<?= htmlspecialchars($user['job_title']) ?>" <?= $disabledAttr ?>>
                </div>

                <div class="form-group">
                    <label>Role <span style="color:red">*</span></label>
                    <select name="role_id" required <?= $disabledAttr ?>>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $r['id'] == $user['primary_role_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" <?= $disabledAttr ?>>
                        <option value="">— None —</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $d['id'] == $user['department_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" <?= $disabledAttr ?>>
                        <option value="1" <?= $user['is_active'] ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= !$user['is_active'] ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

            </div>
        </div>

        <div class="form-group" style="margin-top: 30px;">
            <label>Assigned Branches</label>
            <div class="branch-grid">
                <?php foreach ($allBranches as $b): ?>
                    <div class="branch-option">
                        <input type="checkbox" name="branches[]" id="br_<?= $b['id'] ?>" value="<?= $b['id'] ?>"
                            <?= in_array($b['id'], $userBranches) ? 'checked' : '' ?>
                            <?= $disabledAttr ?>>
                        
                        <label for="br_<?= $b['id'] ?>" class="branch-label">
                            <i class="fa-solid fa-building-user"></i>
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:20px; width:100%;">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
        </button>

    </form>

</div>
</div>

<script>
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');

    avatarInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>