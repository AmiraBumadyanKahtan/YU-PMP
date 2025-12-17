<?php
// modules/resources/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "resource_functions.php";

if (!Auth::check()) die("Access denied");

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['type_name'] ?? '');
    $category = $_POST['category'] ?? '';
    $desc     = trim($_POST['description'] ?? '');
    // Checkbox handling: if checked, it sends a value, otherwise nothing
    $active   = isset($_POST['is_active']) ? 1 : 0;

    if ($name === "") {
        $errors[] = "Resource name is required.";
    }

    $validCategories = ["material", "software", "service", "human", "other"];
    if (!in_array($category, $validCategories)) {
        $errors[] = "Invalid category.";
    }

    if (empty($errors)) {
        // استدعاء الدالة من resource_functions.php
        if (createResource($name, $category, $desc, $active)) {
            $success = "Resource created successfully.";
            // تصفير البيانات بعد النجاح
            $_POST = [];
        } else {
            $errors[] = "Failed to create resource. Name might already exist.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Resource</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/resource_form.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <style>
        /* تنسيقات إضافية للرسائل */
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-error { background: #f2dede; color: #a94442; border: 1px solid #ebccd1; }
        .alert-success { background: #dff0d8; color: #3c763d; border: 1px solid #d6e9c6; }
        .alert ul { margin: 0; padding-left: 20px; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-plus"></i> Add Resource</h1>
        <a href="list.php" class="btn-secondary">← Back</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>


    <form method="POST" class="resource-form">

        <div class="form-group">
            <label>Resource Name <span style="color:red">*</span></label>
            <input type="text" name="type_name" required value="<?= htmlspecialchars($_POST['type_name'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Category <span style="color:red">*</span></label>
            <select name="category" required>
                <option value="">Select...</option>
                <?php 
                $cats = ["material", "software", "service", "human", "other"];
                foreach($cats as $c): 
                    $selected = (isset($_POST['category']) && $_POST['category'] === $c) ? 'selected' : '';
                ?>
                    <option value="<?= $c ?>" <?= $selected ?>><?= ucfirst($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group-inline" style="margin-top:15px; display:flex; align-items:center; gap:8px;">
            <label class="checkbox-label" style="cursor:pointer; display:flex; align-items:center; gap:5px;">
                <input type="checkbox" name="is_active" value="1" <?= (!isset($_POST) || empty($_POST) || isset($_POST['is_active'])) ? 'checked' : '' ?>>
                Active Status
            </label>
        </div>

        <button type="submit" class="btn-primary btn-submit" style="margin-top:20px;">Create Resource</button>

    </form>

</div>
</div>

</body>
</html>