<?php
// modules/users/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";
require_once "user_functions.php";

// ✅ التعديل: استخدام صلاحية إنشاء المستخدمين المحددة
if (!Auth::can('sys_user_create')) {
    header("Location: ../../error/403.php");
    exit;
}

$db = Database::getInstance()->pdo();
$roles = $db->query("SELECT id, role_name FROM roles ORDER BY role_name")->fetchAll();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted = 0 ORDER BY name")->fetchAll();
$allBranches = getAllBranches();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User</title>

      <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/create.css">
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
            <h1 class="page-title"><i class="fa-solid fa-user-plus"></i> Add New User</h1>
            <a href="list.php" class="btn-secondary">Cancel</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <script>
                Swal.fire({ icon: 'error', title: 'Error', text: '<?= $_SESSION['error'] ?>' });
            </script>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="user-form-wrapper">
            <form action="save.php" method="post" enctype="multipart/form-data" id="createUserForm">
                
                <div class="avatar-upload">
                    <div class="avatar-preview">
                        <div id="imagePreview" style="width:100%; height:100%; border-radius:50%; background-size:cover; background-position:center; background-image: url('<?php echo BASE_URL; ?>assets/images/default-avatar.png');"></div>
                    </div>
                    <div class="avatar-edit">
                        <input type='file' name="avatar" id="imageUpload" accept=".png, .jpg, .jpeg" />
                        <label for="imageUpload"><i class="fa-solid fa-camera"></i></label>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name (English) <span style="color:red">*</span></label>
                        <input type="text" name="full_name_en" class="form-control" required placeholder="John Doe">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address <span style="color:red">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="user@example.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password <span style="color:red">*</span></label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
                            <i class="fa-solid fa-eye toggle-password" onclick="togglePassword()"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="05xxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label class="form-label">System Role <span style="color:red">*</span></label>
                        <select name="role_id" class="form-control" required>
                            <option value="">-- Select Role --</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-control">
                            <option value="">-- None (General) --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-control" placeholder="e.g. Software Engineer">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Account Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label">Allowed Branches <small class="text-muted">(Optional)</small></label>
                    <div class="branch-grid">
                        <?php foreach ($allBranches as $b): ?>
                            <div class="branch-option">
                                <input type="checkbox" name="branches[]" id="br_<?= $b['id'] ?>" value="<?= $b['id'] ?>">
                                <label for="br_<?= $b['id'] ?>" class="branch-label">
                                    <i class="fa-solid fa-building-user"></i> <?= htmlspecialchars($b['branch_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

                <div class="form-actions">
                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-user-check"></i> Create User
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
    // 1. معاينة الصورة
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').style.backgroundImage = 'url('+e.target.result+')';
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    document.getElementById("imageUpload").addEventListener("change", function() {
        readURL(this);
    });

    // 2. إظهار/إخفاء الباسورد
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const icon = document.querySelector('.toggle-password');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // 3. التحقق قبل الإرسال (اختياري)
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        // يمكنك إضافة شروط إضافية هنا إذا أردت
    });
</script>

</body>
</html>