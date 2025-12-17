<?php
// modules/departments/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "department_functions.php"; // استدعاء ملف الدوال

if (!Auth::can('manage_departments')) {
    die("Access denied.");
}

// ================================
// Search & Fetch
// ================================
$search = $_GET['search'] ?? "";
$departments = dept_all($search); 
$total = count($departments);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Departments</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/list.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="js/delete.js"></script> 
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-building"></i> Departments
            </h1>

            <a href="create.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> New Department
            </a>
        </div>

        <form method="get" class="filter-bar">
            <input
                type="text"
                class="filter-input"
                placeholder="Search by department name..."
                name="search"
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="submit" class="btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
            <a href="list.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
        </form>

        <div class="table-container">
            <?php if ($total == 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-building-circle-slash"></i>
                    <h3>No departments found</h3>
                    <p>Try adjusting your search or add a new department.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="35%">Department Name</th>
                            <th width="30%">Manager</th>
                            <th width="15%">Created</th>
                            <th width="15%" style="text-align:center;">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($departments as $i => $d): ?>
                        <tr>
                            <td style="color:#888;"><?= $i + 1 ?></td>
                            <td style="font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($d['name']) ?></td>
                            
                            <td>
                                <?php if($d['manager_name']): ?>
                                    <span style="display:flex; align-items:center; gap:5px;">
                                        <i class="fa-regular fa-user-circle" style="color:#aaa;"></i> 
                                        <?= htmlspecialchars($d['manager_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#999; font-style:italic;">— Unassigned —</span>
                                <?php endif; ?>
                            </td>

                            <td style="color: #777; font-size: 0.9rem;">
                                <i class="fa-regular fa-calendar" style="margin-right:4px;"></i>
                                <?= date('Y-m-d', strtotime($d['created_at'])) ?>
                            </td>

                            <td style="text-align:center;">
                                <a href="view.php?id=<?= $d['id'] ?>" class="action-btn btn-view-icon" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $d['id'] ?>" class="action-btn btn-edit-icon" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="#" class="action-btn btn-delete-icon" onclick="deleteDepartment(<?= $d['id'] ?>)" title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
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