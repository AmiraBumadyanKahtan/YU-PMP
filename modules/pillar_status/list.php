<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

// Fetch all statuses
$statuses = $db->query("
    SELECT * FROM pillar_statuses 
    ORDER BY sort_order ASC, id ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pillar Statuses</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/pillar_status.css">
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
            <i class="fa-solid fa-flag"></i> Pillar Statuses
        </h1>

        <a href="create.php" class="btn-primary">
            + Add Status
        </a>
    </div>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Status Name</th>
                    <th>Color</th>
                    <th>Sort Order</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($statuses) == 0): ?>
                    <tr>
                        <td colspan="5" class="no-data">No statuses found.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($statuses as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><?= htmlspecialchars($s['name']) ?></td>

                        <td>
                            <span class="color-box" style="background: <?= $s['color'] ?>"></span>
                            <?= $s['color'] ?>
                        </td>

                        <td><?= $s['sort_order'] ?></td>

                        <td class="actions">
                            <a href="edit.php?id=<?= $s['id'] ?>" class="action-btn btn-edit">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <a href="delete.php?id=<?= $s['id'] ?>" 
                               class="action-btn btn-delete"
                               onclick="return confirm('Are you sure you want to delete this status?');">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

</div>
</div>

</body>
</html>
