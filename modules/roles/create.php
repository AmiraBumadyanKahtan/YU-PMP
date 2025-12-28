<?php
// modules/roles/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "role_functions.php";

// ✅ التعديل: استخدام صلاحية إدارة الأدوار المحددة
if (!Auth::can('sys_role_manage')) {
    header("Location: ../../error/403.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['role_name'] ?? '');
    $key  = trim($_POST['role_key'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($name) || empty($key)) {
        $error = "Role Name and Key are required.";
    } else {
        // تنظيف المفتاح (أحرف إنجليزية صغيرة، أرقام، وشرطة سفلية فقط)
        $key = strtolower(str_replace(' ', '_', $key));
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        
        $res = createRole($name, $key, $desc);
        
        if ($res['ok']) {
            // الانتقال لصفحة التعديل لإضافة الصلاحيات لهذا الدور الجديد
            header("Location: edit.php?id=" . $res['id']); 
            exit;
        } else {
            $error = $res['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Role</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/create.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-shield-plus"></i> Create New Role</h1>
        <a href="list.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
    </div>

    <div class="form-card">
        <?php if ($error): ?>
            <div class="error-box">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Role Name <span style="color:#e53e3e">*</span></label>
                <input type="text" name="role_name" class="form-input" required placeholder="e.g. HR Manager" autofocus value="<?= htmlspecialchars($_POST['role_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Role Key <span style="color:#e53e3e">*</span></label>
                <input type="text" name="role_key" class="form-input" required placeholder="e.g. hr_manager" value="<?= htmlspecialchars($_POST['role_key'] ?? '') ?>">
                <span class="helper-text"><i class="fa-solid fa-circle-info"></i> Must be unique. Use letters and underscores only (no spaces).</span>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="4" placeholder="Briefly describe the purpose of this role..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-submit">
                    Create & Assign Permissions <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

</div>
</div>

</body>
</html>