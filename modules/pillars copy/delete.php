<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    $_SESSION['toast_error'] = "Invalid pillar ID.";
    header("Location: list.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM pillars WHERE id = ?");
$stmt->execute([$id]);
$pillar = $stmt->fetch();

if (!$pillar) {
    $_SESSION['toast_error'] = "Pillar not found.";
    header("Location: list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $delete = $db->prepare("DELETE FROM pillars WHERE id = ?");
        $delete->execute([$id]);

        $_SESSION['toast_success'] = "Pillar deleted successfully.";
        header("Location: list.php");
        exit;

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['toast_error'] = "Cannot delete pillar because linked initiatives exist.";
        } else {
            $_SESSION['toast_error'] = "Failed to delete pillar.";
        }
        header("Location: list.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delete Pillar</title>

<link rel="stylesheet" href="../../assets/css/layout.css">
<link rel="stylesheet" href="../../assets/css/toast.css">
<link rel="stylesheet" href="css/delete.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">
<h1 class="delete-title">
        <i class="fa-solid fa-triangle-exclamation"></i>
        Delete Pillar
    </h1>
<div class="delete-wrapper">

    <div class="confirm-question">Are you sure?</div>
    <div class="small-text">You are about to delete the pillar:</div>

    <div class="pillar-box">
        <div>
            <div class="pillar-name">
                <?= htmlspecialchars($pillar['name']) ?>
            </div>
        </div>
    </div>

    <div class="warning-text">This action cannot be undone.</div>

    <form class="btn" method="POST">
        <button type="submit" class="btn-danger">Delete Permanently</button>
        <a href="list.php" class="btn-cancel">Cancel</a>
    </form>

</div>

</div>
</div>

<script src="../../assets/js/toast.js"></script>

<?php if (!empty($_SESSION['toast_success'])): ?>
<script>showToast("<?= $_SESSION['toast_success'] ?>", "success")</script>
<?php unset($_SESSION['toast_success']); endif; ?>

<?php if (!empty($_SESSION['toast_error'])): ?>
<script>showToast("<?= $_SESSION['toast_error'] ?>", "error")</script>
<?php unset($_SESSION['toast_error']); endif; ?>

</body>
</html>
