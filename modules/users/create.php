<?php
// modules/users/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php"; // لاستخدام دالة createUser

// التحقق من الصلاحية
if (!Auth::can('manage_users')) {
    echo "<h2 style='color:red; text-align:center; margin-top:50px;'>Access Denied</h2>";
    exit;
}

$db = Database::getInstance()->pdo();

// جلب القوائم المنسدلة (الأدوار والأقسام)
// تعديل: استخدام جدول roles
$roles = $db->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted = 0 ORDER BY name")->fetchAll();

$alert = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // معالجة رفع الصورة
        $avatarName = null;
        if (!empty($_FILES['avatar']['name'])) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            // تسمية فريدة للصورة
            $avatarName = "user_" . time() . "_" . rand(1000, 9999) . "." . $ext;
            
            // تحديد مسار الرفع (يجب أن يكون المجلد موجوداً)
            // نستخدم المسار النسبي بناءً على موقع الملف الحالي
            $targetDir = __DIR__ . '/../../assets/uploads/avatars/';
            
            // إنشاء المجلد إذا لم يكن موجوداً
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $uploadPath = $targetDir . $avatarName;
            
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to upload avatar.");
            }
        }

        // تجهيز البيانات لإرسالها لدالة createUser
        // ملاحظة: قمنا بتوليد username تلقائياً من الإيميل كما في الكود السابق
        $userData = [
            'username'        => explode('@', $_POST['email'])[0],
            'email'           => $_POST['email'],
            'password'        => $_POST['password'],
            'full_name_en'    => $_POST['full_name_en'],
            'full_name_ar'    => null, // يمكن إضافته لاحقاً للفورم
            'primary_role_id' => $_POST['role_id'], // ربط الحقل بـ primary_role_id
            'department_id'   => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
            'phone'           => $_POST['phone'],
            'job_title'       => $_POST['job_title'],
            'avatar'          => $avatarName,
            'is_active'       => $_POST['is_active']
        ];

        // استدعاء دالة الإنشاء
        $result = createUser($userData);
        // ... بعد السطر: $result = createUser($userData); ...

        if ($result['ok']) {
            // ✅ إضافة: حفظ الفروع للمستخدم الجديد
            if (isset($_POST['branches'])) {
                updateUserBranches($result['id'], $_POST['branches']);
            }
            
            $alert = "<div class='alert alert-success'>User created successfully! ...";
            $_POST = []; 
        }

        if ($result['ok']) {
            $alert = "<div class='alert alert-success'>User created successfully! <button onclick=\"this.parentElement.remove()\">×</button></div>";
            // تصفير البيانات بعد الإضافة الناجحة (اختياري)
            $_POST = []; 
        } else {
            // عرض الخطأ القادم من الدالة (مثل تكرار الاسم أو الإيميل)
            throw new Exception($result['error']);
        }

    } catch (Exception $e) {
        $alert = "<div class='alert alert-error'>Error: " . htmlspecialchars($e->getMessage()) . " <button onclick=\"this.parentElement.remove()\">×</button></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/create.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    
    <style>
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; position: relative; }
        .alert-success { background-color: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .alert-error { background-color: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .alert button { position: absolute; right: 10px; top: 10px; background: none; border: none; cursor: pointer; font-size: 16px; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-user-plus"></i> Add User</h1>
        <a href="list.php" class="btn-secondary">← Back</a>
    </div>

    <?= $alert ?>

    <div class="user-form-wrapper">
    <form method="post" enctype="multipart/form-data" class="user-form">

        <div class="form-grid">

            <div class="form-group">
                <label>Full Name (EN) <span style="color:red">*</span></label>
                <input type="text" name="full_name_en" required value="<?= htmlspecialchars($_POST['full_name_en'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Email <span style="color:red">*</span></label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Password <span style="color:red">*</span></label>
                <input type="password" name="password" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Job Title</label>
                <input type="text" name="job_title" value="<?= htmlspecialchars($_POST['job_title'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Role <span style="color:red">*</span></label>
                <select name="role_id" required>
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $r['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['role_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Department</label>
                <select name="department_id">
                    <option value="">None</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (isset($_POST['department_id']) && $_POST['department_id'] == $d['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="is_active">
                    <option value="1" <?= (isset($_POST['is_active']) && $_POST['is_active'] == '1') ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (isset($_POST['is_active']) && $_POST['is_active'] == '0') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label>Avatar</label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            <?php
            // جلب الفروع للعرض
            $allBranches = getAllBranches();
            ?>
            <div class="form-group" style="grid-column: 1 / -1;"> <label>Assigned Branches</label>
                <div style="display:flex; flex-wrap:wrap; gap:15px; background:#f8f9fa; padding:15px; border-radius:5px; border:1px solid #eee;">
                    <?php foreach ($allBranches as $b): ?>
                        <label style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                            <input type="checkbox" name="branches[]" value="<?= $b['id'] ?>">
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small style="color:#777;">Leave empty if the user is not restricted to specific branches.</small>
            </div>

        </div>

        <button type="submit" class="save-btn">Create User</button>

    </form>
    </div>

</div>
</div>

</body>
</html>