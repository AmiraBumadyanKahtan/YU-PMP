<?php
// modules/branches/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "branch_functions.php";

if (!Auth::can('sys_dept_branches')) die("Access Denied");

// Handle Delete Action
if (isset($_POST['delete_id'])) {
    $res = deleteBranch($_POST['delete_id']);
    if ($res['ok']) $msg = "Branch deleted successfully";
    else $err = $res['error'];
}

$branches = getBranches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branches</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/list.css">
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
        <h1 class="page-title"><i class="fa-solid fa-map-location-dot"></i> Branches</h1>
        <a href="form.php" class="btn-primary"><i class="fa-solid fa-plus"></i> New Branch</a>
    </div>

    <?php if (isset($msg)): ?> <script>Swal.fire({icon: 'success', title: 'Success', text: '<?= $msg ?>', timer: 2000, showConfirmButton: false});</script> <?php endif; ?>
    <?php if (isset($err)): ?> <script>Swal.fire({icon: 'error', title: 'Error', text: '<?= $err ?>'});</script> <?php endif; ?>

    <div class="table-container">
        <?php if(empty($branches)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-building-circle-slash"></i>
                <h3>No branches found</h3>
                <p>Start by adding a new branch to the system.</p>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="15%">Code</th>
                        <th width="30%">Name</th>
                        <th width="25%">City</th>
                        <th width="15%">Status</th>
                        <th width="15%" style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $b): ?>
                    <tr>
                        <td style="font-weight:700; color:#2c3e50; font-family:monospace; font-size:1rem;"><?= htmlspecialchars($b['branch_code']) ?></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($b['branch_name']) ?></td>
                        <td><i class="fa-solid fa-location-dot" style="color:#aaa; margin-right:5px;"></i> <?= htmlspecialchars($b['city']) ?></td>
                        <td>
                            <?php if($b['is_active']): ?>
                                <span class="status-badge status-active">Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <a href="form.php?id=<?= $b['id'] ?>" class="action-btn btn-edit" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this branch?');">
                                <input type="hidden" name="delete_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="action-btn btn-delete" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</div>

</body>
</html>