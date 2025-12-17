<?php
// modules/departments/view.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::can('manage_departments')) {
    die("Access denied.");
}

$db = Database::getInstance()->pdo();

// Get Department Data
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $db->prepare("
    SELECT d.*, 
           u.full_name_en AS manager_name,
           u.id AS manager_id
    FROM departments d
    LEFT JOIN users u ON d.manager_id = u.id
    WHERE d.id = :id
");
$stmt->execute([':id' => $id]);
$dept = $stmt->fetch();

if (!$dept) {
    die("Department not found.");
}

// Stats
$stats = [
    "projects" => $db->query("SELECT COUNT(*) FROM operational_projects WHERE department_id = {$dept['id']} AND (is_deleted=0 OR is_deleted IS NULL)")->fetchColumn(),
    "collabs"  => $db->query("SELECT COUNT(*) FROM collaborations WHERE department_id = {$dept['id']}")->fetchColumn(),
    "users"    => $db->query("SELECT COUNT(*) FROM users WHERE department_id = {$dept['id']} AND (is_deleted=0 OR is_deleted IS NULL)")->fetchColumn(),
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Details</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/view.css">
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
                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($dept['name']) ?>
            </h1>
            <a href="list.php" class="btn btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="stats-grid">
            <div class="stats-card st-orange">
                <div class="stats-icon" style="color:#f39c12;"><i class="fa-solid fa-diagram-project"></i></div>
                <div class="value"><?= $stats['projects'] ?></div>
                <div class="label">Active Projects</div>
            </div>

            <div class="stats-card st-blue">
                <div class="stats-icon" style="color:#3498db;"><i class="fa-solid fa-handshake"></i></div>
                <div class="value"><?= $stats['collabs'] ?></div>
                <div class="label">Collaborations</div>
            </div>

            <div class="stats-card st-green">
                <div class="stats-icon" style="color:#2ecc71;"><i class="fa-solid fa-users"></i></div>
                <div class="value"><?= $stats['users'] ?></div>
                <div class="label">Staff Members</div>
            </div>
        </div>

        <div class="details-card">
            <div class="details-grid">
                
                <div class="details-row">
                    <div class="details-label">Department Name</div>
                    <div class="details-value" style="font-size: 1.3rem; color: #2c3e50; font-weight: 700;">
                        <?= htmlspecialchars($dept['name']); ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Manager</div>
                    <div class="details-value">
                        <?php if ($dept['manager_name']): ?>
                            <a href="../users/view.php?id=<?= $dept['manager_id'] ?>" class="manager-link">
                                <i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($dept['manager_name']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#999; font-style:italic;">Not Assigned</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Created At</div>
                    <div class="details-value">
                        <i class="fa-regular fa-calendar" style="color:#aaa; margin-right:5px;"></i>
                        <?= date('d M Y', strtotime($dept['created_at'])) ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Last Updated</div>
                    <div class="details-value">
                        <i class="fa-solid fa-clock-rotate-left" style="color:#aaa; margin-right:5px;"></i>
                        <?= date('d M Y, h:i A', strtotime($dept['updated_at'])) ?>
                    </div>
                </div>

            </div>

            <hr class="divider">

            <div class="details-actions">
                <a href="edit.php?id=<?= $dept['id'] ?>" class="btn btn-edit">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Details
                </a>
                
                <button class="btn btn-delete" onclick="deleteDepartment(<?= $dept['id'] ?>)">
                    <i class="fa-solid fa-trash-can"></i> Delete Department
                </button>
            </div>

        </div>

    </div>
</div>

</body>
</html>