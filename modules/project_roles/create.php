<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::can('manage_rbac')) die("Access Denied");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = createProjectRole($_POST['name'], $_POST['description']);
    if ($id) {
        header("Location: edit.php?id=$id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Project Role</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
</head>
<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>
<div class="main-content">
<div class="page-wrapper">
    <h1>New Project Role</h1>
    <form method="POST" style="background:#fff; padding:30px; max-width:500px; border-radius:8px;">
        <div style="margin-bottom:15px;">
            <label>Role Name</label>
            <input type="text" name="name" required style="width:100%; padding:10px; border:1px solid #ddd;">
        </div>
        <div style="margin-bottom:15px;">
            <label>Description</label>
            <textarea name="description" rows="3" style="width:100%; padding:10px; border:1px solid #ddd;"></textarea>
        </div>
        <button type="submit" class="btn-primary">Create</button>
    </form>
</div>
</div>
</body>
</html>