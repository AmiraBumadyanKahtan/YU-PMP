<?php
// modules/branches/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "branch_functions.php";

if (!Auth::can('manage_departments')) die("Access Denied");

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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Unified Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; }
        .page-wrapper {
        padding: 30px;
        font-family: "Varela Round", sans-serif;
        font-weight: 400;
        font-style: normal;
        }

        /* Page Header */
        .page-header-flex {
            font-family: "Varela Round", sans-serif;
            font-weight: 400;
            font-style: normal;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        
        /* Header */
       /* .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }*/
       .page-title {
            font-family: "Varela Round", sans-serif;
            font-weight: 400;
            font-style: normal;
            font-size: 2rem;
            font-weight: 700;
            color: #ff8c00 !important;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        } 
       .page-title i { color: #ff8c00; }

        /* Button */
        .btn-primary { 
            background: linear-gradient(135deg, #ff8c00, #e67e00); color: white; border: none; 
            padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; 
            display: inline-flex; align-items: center; gap: 8px; transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(255, 140, 0, 0.3); }

        /* Table */
        .table-container { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; border: 1px solid #f0f0f0; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead { background: #f9f9f9; border-bottom: 2px solid #eee; }
        .data-table th { padding: 15px; text-align: left; font-size: 0.9rem; color: #c86a12; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;background: #fff4e1;  }
        .data-table td { padding: 15px; border-bottom: 1px solid #f5f5f5; color: #444; font-size: 0.95rem; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tr:hover { background-color: #fafafa; }

        /* Status Badges */
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-active { background: #e8f5e9; color: #2e7d32; }
        .status-inactive { background: #ffebee; color: #c62828; }

        /* Actions */
        .action-btn { 
            display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; 
            border-radius: 6px; text-decoration: none; margin-right: 5px; transition: 0.2s; border: none; cursor: pointer; background: transparent;
        }
        .btn-edit { color: #1b7f3a; background: #def5e7; }
        .btn-edit:hover { background: #1b7f3a; color: #def5e7; }
        .btn-delete { color: #ad1c1c; background: #ffe4e4; }
        .btn-delete:hover { background: #ad1c1c; color: #ffe4e4; }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 50px; color: #a0aec0; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; opacity: 0.5; }
    </style>
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