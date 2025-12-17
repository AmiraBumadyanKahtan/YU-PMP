<?php
// modules/departments/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "department_functions.php"; // الدوال الجاهزة

if (!Auth::can('manage_departments')) {
    die("Access denied.");
}

// 1. التحقق من ID القسم
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) die("Invalid Department ID.");

// 2. جلب بيانات القسم
$dept = dept_get($id);
if (!$dept) die("Department not found.");

// 3. جلب المدراء المحتملين
// نستخدم الاستعلام المباشر هنا لأننا نريد فقط المدراء، وليس كل المستخدمين
$db = Database::getInstance()->pdo();
$users = $db->query("
    SELECT id, full_name_en 
    FROM users
    WHERE primary_role_id IN (
        SELECT id FROM roles WHERE role_key = 'department_manager'
    )
    AND is_active = 1
    ORDER BY full_name_en ASC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Department</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="stylesheet" href="css/edit.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <h1 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i>
            Edit Department
        </h1>

        <div class="form-card">

            <form action="update.php" method="post">

                <input type="hidden" name="id" value="<?= $dept['id'] ?>">

                <div class="form-field">
                    <label>Department Name <span style="color:red">*</span></label>
                    <input 
                        type="text" 
                        name="name" 
                        required 
                        value="<?= htmlspecialchars($dept['name']) ?>">
                </div>

                <div class="form-field">
                    <label>Department Manager</label>
                    <select name="manager_id">
                        <option value="">-- None --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= ($dept['manager_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:#777;">Only users with 'Department Manager' role appear here.</small>
                </div>
                <?php
                $allBranches = getAllActiveBranches();
                $currentBranches = getDepartmentBranches($dept['id']); // مصفوفة الـ IDs
                ?>

                <div class="form-field">
                    <label style="display:block; margin-bottom:10px;">Assign Branches</label>
                    <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap:10px;">
                        <?php foreach ($allBranches as $b): ?>
                            <label style="background:#f9f9f9; padding:8px; border:1px solid #eee; border-radius:4px; cursor:pointer; display:flex; align-items:center; gap:5px;">
                                <input type="checkbox" name="branches[]" value="<?= $b['id'] ?>"
                                    <?= in_array($b['id'], $currentBranches) ? 'checked' : '' ?>>
                                <span style="font-size:0.9rem;"><?= htmlspecialchars($b['branch_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="list.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Update</button>
                </div>

            </form>

        </div>

    </div>
</div>

</body>
</html>