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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Unified Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 1.8rem; color: #2c3e50; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #ff8c00; }

        /* Form Card */
        .form-card { 
            max-width: 600px; background: #fff; padding: 40px; border-radius: 12px; 
            margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #f0f0f0; 
        }

        /* Inputs & Labels */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; font-size: 0.95rem; }
        .form-control { 
            width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 8px; 
            box-sizing: border-box; font-family: inherit; font-size: 0.95rem; transition: all 0.2s;
        }
        .form-control:focus { border-color: #ff8c00; outline: none; box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1); }
        
        /* Buttons */
        .btn-save { 
            font-family: "Varela Round", sans-serif;
            width: 100%; padding: 12px; background: linear-gradient(135deg, #ff8c00, #e67e00); 
            color: #fff; border: none; border-radius: 30px; font-size: 1rem; font-weight: 700; 
            cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
            display: flex; justify-content: center; align-items: center; gap: 8px;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3); }

        .btn-secondary { 
            background: #f1f2f6; color: #555; padding: 10px 20px; border-radius: 30px; 
            text-decoration: none; font-weight: 600; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-secondary:hover { background: #e2e6ea; color: #ff8c00; }

        /* Error Alert */
        .alert-error { 
            background: #fff5f5; color: #c0392b; padding: 15px; border-radius: 8px; 
            border: 1px solid #feb2b2; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
        }
    </style>
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