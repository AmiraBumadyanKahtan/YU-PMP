<?php
// modules/project_roles/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::can('manage_project_roles')) die("Access Denied");

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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/create.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-id-badge"></i> New Project Role</h1>
        <a href="list.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
    </div>

    <div class="form-card">
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Role Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Quality Assurance" autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" rows="4" class="form-control" placeholder="Describe the responsibilities of this role..."></textarea>
            </div>
            
            <div style="margin-top: 30px;">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> Create Role
                </button>
            </div>
        </form>
    </div>

</div>
</div>
</body>
</html>