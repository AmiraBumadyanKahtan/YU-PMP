<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access denied");
$success = $error = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $sort_order = trim($_POST['sort_order'] ?? 0);

    if ($name === '') {
        $error = "Status name is required.";
    } else {
        if (createPillarStatus($name, $color, $sort_order)) {
            $success = "Pillar status created successfully.";
        } else {
            $error = "Failed to create pillar status. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Pillar Status</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/pillar_status_form.css">
<link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <h1 class="page-title">
            <i class="fa-solid fa-flag"></i> Add Pillar Status
        </h1>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="post">

                <div class="form-group">
                    <label>Status Name <span class="req">*</span></label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Color (Hex)</label>
                    <input type="color" name="color" value="#FF8C00" style="width: 6%; padding: 1px 10px;">
                </div>

                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" min="0" value="0">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Create
                    </button>

                    <a href="list.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

    </div>
</div>

</body>
</html>
