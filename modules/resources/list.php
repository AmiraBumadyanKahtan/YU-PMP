<?php
// modules/resources/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "resource_functions.php";

if (!Auth::check()) die("Access denied");

// تجميع الفلاتر
$filters = [
    'search'   => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'status'   => $_GET['status'] ?? ''
];

// جلب البيانات
$resources = getFilteredResources($filters);

// التصنيفات للقائمة المنسدلة
$categories = ["material", "software", "service", "human", "other"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resources List</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/resources.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <style>
        .no-data { text-align: center; padding: 30px; color: #777; font-style: italic; }
        .badge-active { background: #dff0d8; color: #3c763d; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .badge-inactive { background: #f2dede; color: #a94442; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title"><i class="fa-solid fa-box"></i> Resources</h1>

            <a href="create.php" class="btn-primary action-btn">
                + Add Resource
            </a>
        </div>

        <form method="get" class="filter-bar">

            <input type="text" name="search" class="filter-input" 
                   placeholder="Search by name..."
                   value="<?= htmlspecialchars($filters['search']) ?>">

            <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $filters['category'] === $cat ? "selected" : "" ?>>
                        <?= ucfirst($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Status</option>
                <option value="1" <?= $filters['status'] === "1" ? "selected" : "" ?>>Active</option>
                <option value="0" <?= $filters['status'] === "0" ? "selected" : "" ?>>Inactive</option>
            </select>

            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-filter"></i> Apply
            </button>

            <a href="list.php" class="btn-reset">Reset</a>
        </form>


        <div class="resource-table-wrapper">
            <table class="resource-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($resources) === 0): ?>
                    <tr>
                        <td colspan="7" class="no-data">No resources found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($resources as $r): ?>
                        <tr>
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['type_name']) ?></td>
                            <td><?= ucfirst($r['category']) ?></td>
                            <td><?= htmlspecialchars($r['description'] ?: "-") ?></td>

                            <td>
                                <?= $r['is_active'] 
                                    ? "<span class='badge-active'>Active</span>" 
                                    : "<span class='badge-inactive'>Inactive</span>" ?>
                            </td>

                            <td><?= date('Y-m-d', strtotime($r['created_at'])) ?></td>

                            <td class="actions">
                                <a href="view.php?id=<?= $r['id'] ?>" class="action-btn btn-view">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $r['id'] ?>" class="action-btn btn-edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="delete.php?id=<?= $r['id'] ?>" class="action-btn btn-delete"
                                   onclick="return confirm('Delete this resource? This action cannot be undone if used in projects.');">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

</body>
</html>