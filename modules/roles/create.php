<?php
// modules/roles/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "role_functions.php";

// التحقق من الصلاحية
if (!Auth::can('manage_rbac')) die("Access Denied");

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['role_name']);
    $key  = trim($_POST['role_key']);
    $desc = trim($_POST['description']);

    if (empty($name) || empty($key)) {
        $error = "Role Name and Key are required.";
    } else {
        // تنظيف المفتاح: حروف صغيرة، استبدال المسافات بـ _
        $key = strtolower(str_replace(' ', '_', $key));
        // إزالة أي رموز غير مسموح بها (أمان إضافي)
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        
        $res = createRole($name, $key, $desc);
        if ($res['ok']) {
            // النجاح: الانتقال لصفحة التعديل لإضافة الصلاحيات
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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* استخدام نفس التنسيقات الموحدة */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; }
        .page-wrapper { padding: 2rem; }
        
        .form-card { 
            background: #fff; padding: 30px; border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; 
            max-width: 700px; margin: 0 auto; 
        }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.95rem; }
        .form-input, .form-textarea { 
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; 
            font-size: 1rem; transition: border-color 0.2s; box-sizing: border-box;
        }
        .form-input:focus, .form-textarea:focus { border-color: #ff8c00; outline: none; }
        
        .helper-text { font-size: 0.85rem; color: #888; margin-top: 5px; display: block; }
        
        .btn-submit { 
            background: #ff8c00; color: #fff; border: none; padding: 12px 25px; 
            border-radius: 8px; font-weight: bold; cursor: pointer; transition: 0.2s; 
            font-size: 1rem; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-submit:hover { background: #e67e00; }
        
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; max-width: 700px; margin-left: auto; margin-right: auto; }
        .page-title { margin: 0; font-size: 1.5rem; color: #2c3e50; font-weight: 700; }
        .btn-secondary { background: #eee; color: #333; text-decoration: none; padding: 8px 15px; border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: 0.2s; }
        .btn-secondary:hover { background: #e0e0e0; }
        
        .error-box { background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-shield-halved"></i> Create New Role</h1>
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
                <label class="form-label">Role Name <span style="color:red">*</span></label>
                <input type="text" name="role_name" class="form-input" required placeholder="e.g. Project Manager" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Role Key <span style="color:red">*</span></label>
                <input type="text" name="role_key" class="form-input" required placeholder="e.g. project_manager">
                <span class="helper-text"><i class="fa-solid fa-circle-info"></i> Must be unique. Use letters and underscores only (no spaces).</span>
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="4" placeholder="Briefly describe the purpose of this role..."></textarea>
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