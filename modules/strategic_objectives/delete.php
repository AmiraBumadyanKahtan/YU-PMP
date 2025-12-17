<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "functions.php";
require_once "actions.php";

// صلاحيات الوصول لصفحة التفاصيل
$allowedRoles = ["super_admin", "strategy_office"];
if (!Auth::check() || !in_array($_SESSION['role_key'], $allowedRoles)) {
    die("Access denied");
}

$db = Database::getInstance()->pdo();

// Get ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch the objective
$objective = getStrategicObjectiveById($id);

if (!$objective) {
    $_SESSION['error'] = "Strategic Objective not found.";
    header("Location: list.php");
    exit;
}

// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $deleted = deleteStrategicObjective($id);

    if ($deleted) {
        $_SESSION['success'] = "Strategic Objective deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete – please try again.";
    }

    header("Location: list.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Strategic Objective</title>

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
            <i class="fa-solid fa-triangle-exclamation"></i> Delete Strategic Objective
        </h1>

        <div class="delete-box">

                <div class="delete-warning">
                    Are you sure you want to permanently delete this Objective?
                </div>

                <div class="detail-box">
                    <p><strong>Objective Code:</strong> <?= htmlspecialchars($objective['objective_code']); ?></p>
                    <p><strong>Objective:</strong> <?= htmlspecialchars($objective['objective_text']); ?></p>
                    <p><strong>Pillar ID:</strong> <?= htmlspecialchars($objective['pillar_id']); ?></p>
                </div>
                <p style="color:#b30000;font-weight:600;">
                    This action cannot be undone.
                </p>
                

                <form method="POST">
                    <div class="buttons">
                        <button type="submit" class="btn-delete">Yes, Delete</button>
                        <a href="list.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
        </div>

    </div>
</div>

</body>
</html>
