<?php
// modules/operational_projects/index.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::can('view_project')) die("Access Denied");

// Filters
$filters = [
    'search'        => $_GET['search'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'status_id'     => $_GET['status_id'] ?? '',
    'manager_id'    => '' 
];

// Fetch Data
$projects = getProjects($filters);

// Dropdowns
$db = Database::getInstance()->pdo();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();
$statuses = $db->query("SELECT id, name FROM operational_project_statuses ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operational Projects</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/projects_index.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-diagram-project"></i> Operational Projects
        </h1>
        
        <?php if (Auth::can('create_project')): ?>
            <a href="create.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> New Project
            </a>
        <?php endif; ?>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="filter-input" placeholder="Search Code or Name..." value="<?= htmlspecialchars($filters['search']) ?>">
        
        <select name="department_id" class="filter-select">
            <option value="">All Departments</option>
            <?php foreach($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $filters['department_id'] == $d['id'] ? 'selected':'' ?>><?= $d['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status_id" class="filter-select">
            <option value="">All Statuses</option>
            <?php foreach($statuses as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filters['status_id'] == $s['id'] ? 'selected':'' ?>><?= $s['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-primary" style="height: 42px;">Filter</button>
        
        <?php if(!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['status_id'])): ?>
            <a href="index.php" class="btn-reset">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Project Name</th>
                    <th>Department</th>
                    <th>Manager</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Visibility</th>
                    <th>Start Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 40px; color: #999;">
                            <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; display: block; color: #ddd;"></i>
                            No projects found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($projects as $p): ?>
                        <tr>
                            <td style="font-weight: bold; color: #555;"><?= htmlspecialchars($p['project_code']) ?></td>
                            <td style="text-align: left; font-weight: 600;">
                                <a href="view.php?id=<?= $p['id'] ?>" style="color:#2c3e50; text-decoration:none;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($p['department_name'] ?? '-') ?></td>
                            <td>
                                <span style="font-size: 0.9rem; color: #555;">
                                    <i class="fa-solid fa-user-circle" style="color:#ff9c31;"></i> 
                                    <?= htmlspecialchars($p['manager_name'] ?? 'Unassigned') ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge" style="background-color: <?= $p['status_color'] ?: '#95a5a6' ?>;">
                                    <?= htmlspecialchars($p['status_name']) ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $prioClass = 'prio-' . strtolower($p['priority']); 
                                    echo "<span class='$prioClass'>" . ucfirst($p['priority']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <?php if ($p['visibility'] == 'public'): ?>
                                    <span class="vis-badge vis-public">
                                        <i class="fa-solid fa-globe"></i> Public
                                    </span>
                                <?php else: ?>
                                    <span class="vis-badge">
                                        <i class="fa-solid fa-lock"></i> Private
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#777; font-size:0.9rem;"><?= $p['start_date'] ?></td>
                            <td>
                                <a href="view.php?id=<?= $p['id'] ?>" class="action-btn btn-view" title="View Details"><i class="fa-solid fa-eye"></i></a>
                                <?php if (Auth::can('edit_project')): ?>
                                    <a href="edit.php?id=<?= $p['id'] ?>" class="action-btn btn-edit" title="Edit Project"><i class="fa-solid fa-pen"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <script>
        const msg = "<?= $_GET['msg'] ?>";
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        if(msg == 'created') Toast.fire({icon: 'success', title: 'Project created successfully'});
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Project updated successfully'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Project deleted successfully'});
    </script>
<?php endif; ?>

</body>
</html>