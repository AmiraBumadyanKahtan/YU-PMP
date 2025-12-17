<?php
// modules/departments/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";
require_once "department_functions.php"; // التأكد من وجود الدالة هنا

if (!Auth::can('manage_departments')) {
    die("Access denied.");
}

$db = Database::getInstance()->pdo();

/* جلب المدراء المحتملين */
$stmt = $db->prepare("
    SELECT id, full_name_en
    FROM users
    WHERE primary_role_id IN (
        SELECT id FROM roles WHERE role_key = 'department_manager'
    )
    AND is_active = 1
    ORDER BY full_name_en ASC
");
$stmt->execute();
$users = $stmt->fetchAll();

// جلب الفروع
$allBranches = getAllActiveBranches();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Department</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/create.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-building-circle-check"></i> Create Department
            </h1>
            <a href="list.php" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="form-card">

            <form action="save.php" method="post">

                <div class="form-group">
                    <label class="form-label">Department Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Information Technology">
                </div>

                <div class="form-group">
                    <label class="form-label">Department Manager</label>
                    <select name="manager_id" class="form-control">
                        <option value="">-- Select Manager --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['full_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-hint"><i class="fa-solid fa-circle-info"></i> Only users with 'Department Manager' role appear here.</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Assign Branches (Locations)</label>
                    <div class="checkbox-grid">
                        <?php foreach ($allBranches as $b): ?>
                            <label class="checkbox-card">
                                <input type="checkbox" name="branches[]" value="<?= $b['id'] ?>">
                                <span class="checkbox-label"><?= htmlspecialchars($b['branch_name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="list.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Create Department
                    </button>
                </div>

            </form>

        </div>

    </div>
</div>

</body>
</html>