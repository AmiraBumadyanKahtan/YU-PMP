<?php
// modules/branches/form.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "branch_functions.php";

if (!Auth::can('sys_dept_branches')) die("Access Denied");

$id = $_GET['id'] ?? null;
$branch = $id ? getBranchById($id) : ['branch_code'=>'', 'branch_name'=>'', 'city'=>'', 'is_active'=>1];
$title = $id ? "Edit Branch" : "New Branch";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'code' => $_POST['code'],
        'name' => $_POST['name'],
        'city' => $_POST['city'],
        'is_active' => $_POST['is_active']
    ];

    if ($id) {
        $res = updateBranch($id, $data);
    } else {
        $res = createBranch($data);
    }

    if ($res['ok']) {
        header("Location: list.php");
        exit;
    } else {
        $error = $res['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/form.css">
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
            <i class="fa-solid fa-code-branch"></i> <?= $title ?>
        </h1>
        <a href="list.php" class="btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Cancel
        </a>
    </div>

    <div class="form-card">
        <?php if (isset($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Branch Code <span style="color:red">*</span> <small style="color:#999; font-weight:normal;">(Must be unique)</small></label>
                <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($branch['branch_code']) ?>" required placeholder="e.g. RYD">
            </div>

            <div class="form-group">
                <label class="form-label">Branch Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($branch['branch_name']) ?>" required placeholder="e.g. Riyadh Main Campus">
            </div>

            <div class="form-group">
                <label class="form-label">City <span style="color:red">*</span></label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($branch['city']) ?>" required placeholder="e.g. Riyadh">
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= $branch['is_active'] ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= !$branch['is_active'] ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-save"></i> Save Branch
            </button>
        </form>
    </div>

</div>
</div>
</body>
</html>