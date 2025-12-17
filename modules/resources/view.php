<?php
// modules/resources/view.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "resource_functions.php";

if (!Auth::check()) die("Access denied");

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid Resource ID");

// Fetch resource
$resource = getResourceById($id);
if (!$resource) die("Resource not found");

// Stats (optional function)
$usage_count = getResourceUsageCount($id); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resource Details</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/resource_view.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .badge-active { background: #dff0d8; color: #3c763d; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .badge-inactive { background: #f2dede; color: #a94442; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem; }
        .badge-category { background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; text-transform: capitalize; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-box"></i> Resource Details</h1>

        <div>
            <a href="list.php" class="btn-secondary" style="margin-right: 5px;">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
            <a href="edit.php?id=<?= $id ?>" class="btn-primary" style="margin-right: 5px;">
                <i class="fa-solid fa-pen"></i> Edit
            </a>
            <a href="delete.php?id=<?= $id ?>" class="btn-delete" style="background:#dc3545; color:white; padding:8px 12px; border-radius:4px; text-decoration:none;"
               onclick="return confirm('Are you sure you want to delete this resource?')">
                <i class="fa-solid fa-trash"></i> Delete
            </a>
        </div>
    </div>

    <div class="resource-card" style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); margin-bottom:20px;">

        <div class="resource-info">

            <div class="info-row" style="margin-bottom:10px;">
                <label style="font-weight:bold; width:120px; display:inline-block;">Name:</label>
                <span><?= htmlspecialchars($resource['type_name']) ?></span>
            </div>

            <div class="info-row" style="margin-bottom:10px;">
                <label style="font-weight:bold; width:120px; display:inline-block;">Category:</label>
                <span class="badge-category"><?= htmlspecialchars($resource['category']) ?></span>
            </div>

            <div class="info-row" style="margin-bottom:10px;">
                <label style="font-weight:bold; width:120px; display:inline-block;">Description:</label>
                <span><?= $resource['description'] ? nl2br(htmlspecialchars($resource['description'])) : "—" ?></span>
            </div>

            <div class="info-row" style="margin-bottom:10px;">
                <label style="font-weight:bold; width:120px; display:inline-block;">Status:</label>
                <span>
                    <?= $resource['is_active'] 
                        ? "<span class='badge-active'>Active</span>" 
                        : "<span class='badge-inactive'>Inactive</span>" ?>
                </span>
            </div>

            <div class="info-row" style="margin-bottom:10px;">
                <label style="font-weight:bold; width:120px; display:inline-block;">Created At:</label>
                <span><?= $resource['created_at'] ?></span>
            </div>

            <div class="info-row" style="margin-bottom:10px;">
                <label style="font-weight:bold; width:120px; display:inline-block;">Last Updated:</label>
                <span><?= $resource['updated_at'] ?></span>
            </div>

        </div>
    </div>

    <h2 class="section-title" style="font-size:1.2rem; margin-bottom:15px;">Resource Statistics</h2>

    <div class="stats-grid" style="display:flex; gap:20px;">

        <div class="stat-card" style="background:#fff; padding:20px; border-radius:8px; flex:1; text-align:center; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
            <i class="fa-solid fa-layer-group stat-icon" style="font-size:2rem; color:#f58220; margin-bottom:10px;"></i>
            <div class="stat-number" style="font-size:1.5rem; font-weight:bold;"><?= $usage_count ?></div>
            <div class="stat-label" style="color:#777;">Times Used in Projects</div>
        </div>

    </div>

</div>
</div>

</body>
</html>