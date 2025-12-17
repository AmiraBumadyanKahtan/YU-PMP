<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "resource_functions.php";

if (!Auth::check()) die("Access denied");

$id = $_GET['id'] ?? null;
if (!$id) die("Invalid Resource ID");

$resource = getResourceById($id);
if (!$resource) die("Resource not found");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (deleteResource($id)) {
        header("Location: list.php?msg=deleted");
        exit;
    } else {
        $error = "Failed to delete resource.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Resource</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/resource_delete.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
     <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <h1 class="page-title" style="color:#b30000;">
        <i class="fa-solid fa-triangle-exclamation"></i> Delete Resource
    </h1>

    <div class="delete-box">

        <h2>Are you sure?</h2>
        <p>You are about to delete the resource:</p>

        <div class="delete-item">
            <strong><?= htmlspecialchars($resource['type_name']) ?></strong>
            <span class="cat"><?= htmlspecialchars($resource['category']) ?></span>
        </div>

        <p style="color:#b30000;font-weight:600;">
            This action cannot be undone.
        </p>

        <?php if (!empty($error)): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="delete-form">
            <button type="submit" class="btn-delete-big">Delete Permanently</button>
            <a href="list.php" class="btn-secondary">Cancel</a>
        </form>

    </div>

</div>
</div>

</body>
</html>
