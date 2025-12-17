<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

// user role
$roleKey = $_SESSION['role_key'] ?? "";

// Can Edit/Delete?
$canManage = in_array($roleKey, ["super_admin", "strategy_office", "ceo"]);

// Fetch pillars (for filter)
$pillars = $db->query("SELECT id, name, color, icon FROM pillars ORDER BY pillar_number ASC")->fetchAll();

// Fetch strategic objectives
$query = "
    SELECT so.*, p.name AS pillar_name, p.color AS pillar_color, p.icon AS pillar_icon
    FROM strategic_objectives so
    JOIN pillars p ON p.id = so.pillar_id
    ORDER BY so.id DESC
";
$data = $db->query($query)->fetchAll();

// Toast session
$toast_success = $_SESSION['toast_success'] ?? null;
$toast_error   = $_SESSION['toast_error'] ?? null;
unset($_SESSION['toast_success'], $_SESSION['toast_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Objectives</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="stylesheet" href="css/list.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-bullseye"></i> Strategic Objectives
            </h1>

            <?php if ($canManage): ?>
            <a href="create.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Objective
            </a>
            <?php endif; ?>
        </div>

        <!-- Filters -->
        <!-- Search + Filters Bar -->
        <div class="filter-bar">

            <input 
                type="text" 
                id="searchInput" 
                class="filter-input" 
                placeholder="Search objectives..."
            >

            <select id="pillarFilter" class="filter-select">
                <option value="">All Pillars</option>
                <?php foreach ($pillars as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button id="applyFilterBtn" class="btn-primary">
                <i class="fa-solid fa-filter"></i> Apply
            </button>

            <button id="resetFilterBtn" class="btn-reset">
                Reset
            </button>

        </div>


        <!-- TABLE -->
        <div class="table-container">
            <table class="smart-table" id="objectivesTable">
                <thead>
                    <tr>
                        <th>Pillar</th>
                        <th>Objective Code</th>
                        <th>Objective Description</th>
                        <th>Created At</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($data as $row): ?>
                    <tr data-pillar="<?= $row['pillar_id'] ?>">
                        <td>
                            <div class="pillar-tag" style="border-left-color: <?= $row['pillar_color'] ?>;">
                                <i class="fa-solid <?= $row['pillar_icon'] ?>"></i>
                                <a href="/strategic-project-system/modules/pillars/details.php?id=<?= $row['pillar_id'] ?>" 
                            class="link-pillar">
                                <span><?= htmlspecialchars($row['pillar_name']) ?></span>
                            </div>
                        </td>


                        <td><strong><?= htmlspecialchars($row['objective_code']) ?></strong></td>

                        <td><?= htmlspecialchars($row['objective_text']) ?></td>

                        <td><?= date("Y-m-d", strtotime($row['created_at'])) ?></td>

                        <td class="actions">
                            <a href="view.php?id=<?= $row['id'] ?>" class="action-btn view">
                                <i class="fa-solid fa-eye"></i>
                            </a>

                            <?php if ($canManage): ?>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="action-btn edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <a href="delete.php?id=<?= $row['id'] ?>" class="action-btn delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="../../assets/js/toast.js"></script>

<?php if ($toast_success): ?>
<script> showToast("<?= $toast_success ?>", "success"); </script>
<?php endif; ?>

<?php if ($toast_error): ?>
<script> showToast("<?= $toast_error ?>", "error"); </script>
<?php endif; ?>

<script src="js/list.js"></script>

</body>
</html>
