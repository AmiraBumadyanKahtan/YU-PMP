<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php"; // مهم — يعرّف الـ Database

// Allow only super admin
if (!Auth::check()) die("Access denied");
// Connect to DB
$db = Database::getInstance()->pdo();

// Get ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch record
$stmt = $db->prepare("SELECT * FROM pillar_statuses WHERE id = ?");
$stmt->execute([$id]);
$status = $stmt->fetch();

if (!$status) {
    $_SESSION['error'] = "Pillar status not found.";
    header("Location: list.php");
    exit;
}

// If user confirms deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $delete = $db->prepare("DELETE FROM pillar_statuses WHERE id = ?");
    $delete->execute([$id]);

    $_SESSION['success'] = "Pillar status deleted successfully.";
    header("Location: list.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Pillar Status</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/delete.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">
        <h1 class="page-title" style="color:#b30000;">
            <i class="fa-solid fa-triangle-exclamation"></i> Delete Pillar Status
        </h1>

        <div class="page-container">

            <div class="card delete-card">

                <p class="warning-text">Are you sure you want to delete this status?</p>

                <div class="delete-details">
                    <strong>Status Name:</strong> <?= htmlspecialchars($status['name']); ?><br>
                    <strong>Color:</strong> <span style="color: <?= $status['color']; ?>;"><?= $status['color']; ?></span><br>
                    <strong>Sort Order:</strong> <?= $status['sort_order']; ?>
                </div>

                <form method="POST" class="delete-form">
                    <button type="submit" class="btn-delete">Yes, Delete</button>
                    <a href="list.php" class="btn-cancel">Cancel</a>
                </form>
            </div>

        </div>

    </div>
</div>

</body>
</html>
