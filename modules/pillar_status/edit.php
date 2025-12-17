<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access denied");



if (!isset($_GET['id'])) {
    die("Invalid Request");
}

$id = intval($_GET['id']);
$status = getPillarStatusById($id);

if (!$status) {
    die("Status not found");
}

// Handle form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name']);
    $color = trim($_POST['color']);
    $sort_order = intval($_POST['sort_order']);

    $isUpdated = updatePillarStatus($id, $name, $color, $sort_order);

    if ($isUpdated) {
        header("Location: list.php?updated=1");
        exit;
    } else {
        $error = "Failed to update status.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Pillar Status</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/edit.css">
<link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <h1 class="page-title">
            <i class="fa-solid fa-pen"></i> Edit Pillar Status
        </h1>

        <div class="form-card">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="form-group">
                    <label>Status Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($status['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Color</label>
                    <input type="color" name="color" value="<?php echo $status['color'] ?: '#000000'; ?>"style="width: 6%; padding: 1px 10px;">
                </div>

                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo $status['sort_order']; ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="list.php" class="btn btn-secondary">Cancel</a>
                </div>

            </form>
        </div>
    </div>
</div>

</body>
</html>