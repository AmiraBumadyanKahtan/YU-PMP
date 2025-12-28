<?php
// modules/departments/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "department_functions.php";

// ✅ 1. التحقق من صلاحية المشاهدة
if (!Auth::can('sys_dept_view')) {
    header("Location: ../../error/403.php");
    exit;
}

// Search & Fetch
$search = $_GET['search'] ?? "";
$departments = dept_all($search); 
$total = count($departments);

// Helper arrays
$allBranches = [];
$rawBranches = getAllActiveBranches();
foreach($rawBranches as $b) {
    $allBranches[$b['id']] = $b['branch_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departments List</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/list.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <script src="js/delete.js"></script> 

    <style>
        .branch-badge {
            display: inline-block; background-color: #e8f4fd; color: #3498db;
            padding: 3px 8px; border-radius: 12px; font-size: 0.75rem;
            margin-right: 4px; margin-bottom: 4px; border: 1px solid #d6eaf8;
        }
        .empty-branches { color: #ccc; font-style: italic; font-size: 0.8rem; }
        
        /* تعطيل الأزرار بصرياً إذا لم تكن هناك صلاحية (احتياط) */
        .btn-disabled { opacity: 0.5; pointer-events: none; cursor: not-allowed; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <?php if (isset($_SESSION['success'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success', title: 'Success!', text: '<?= $_SESSION['success'] ?>',
                        timer: 3000, showConfirmButton: false
                    });
                });
            </script>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-building"></i> Departments
            </h1>

            <?php if (Auth::can('sys_dept_create')): ?>
                <a href="create.php" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> New Department
                </a>
            <?php endif; ?>
        </div>

        <form method="get" class="filter-bar">
            <input type="text" class="filter-input" placeholder="Search by department name..." name="search" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <?php if(!empty($search)): ?>
                <a href="list.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <?php if ($total == 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-building-circle-slash" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
                    <h3>No departments found</h3>
                    <p style="color: #888;">Try adjusting your search or add a new department.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Department Name</th>
                            <th width="25%">Locations (Branches)</th>
                            <th width="20%">Manager</th>
                            <th width="10%">Created</th>
                            <th width="15%" style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($departments as $i => $d): ?>
                        <?php $deptBranchIds = getDepartmentBranches($d['id']); ?>
                        <tr>
                            <td style="color:#888;"><?= $i + 1 ?></td>
                            <td style="font-weight: 600; color: #2c3e50;">
                                <?= htmlspecialchars($d['name']) ?>
                            </td>
                            <td>
                                <?php if (!empty($deptBranchIds)): ?>
                                    <?php foreach($deptBranchIds as $bid): ?>
                                        <?php if(isset($allBranches[$bid])): ?>
                                            <span class="branch-badge">
                                                <i class="fa-solid fa-location-dot" style="font-size: 0.7em;"></i> 
                                                <?= htmlspecialchars($allBranches[$bid]) ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="empty-branches">No branches assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($d['manager_name']): ?>
                                    <span style="display:flex; align-items:center; gap:8px;">
                                        <div style="width: 25px; height: 25px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8em; color: #666;">
                                            <?= strtoupper(substr($d['manager_name'], 0, 1)) ?>
                                        </div>
                                        <?= htmlspecialchars($d['manager_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#999; font-style:italic;">— Unassigned —</span>
                                <?php endif; ?>
                            </td>
                            <td style="color: #777; font-size: 0.9rem;">
                                <?= date('M d, Y', strtotime($d['created_at'])) ?>
                            </td>
                            <td style="text-align:center;">
                                <div class="action-buttons">
                                    <a href="view.php?id=<?= $d['id'] ?>" class="action-btn btn-view-icon" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <?php if (Auth::can('sys_dept_edit')): ?>
                                        <a href="edit.php?id=<?= $d['id'] ?>" class="action-btn btn-edit-icon" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (Auth::can('sys_dept_delete')): ?>
                                        <button class="action-btn btn-delete-icon" onclick="deleteDepartment(<?= $d['id'] ?>)" title="Archive Department">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if($total > 0): ?>
            <div style="padding:15px; background:#fafafa; border-top:1px solid #eee; color:#666; font-size:0.9rem;">
                <strong>Total Departments:</strong> <?= $total ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>