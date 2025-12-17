<?php
require_once "../../core/init.php";
require_once "functions.php";

if (!Auth::can('send_progress_update')) {
    die("Access denied");
}

$userId = $_SESSION['user_id'];

// جلب مشاريع المستخدم كمدير أو عضو
$stmt = db()->prepare("
    SELECT id, name 
    FROM operational_projects 
    WHERE manager_id = ?
");
$stmt->execute([$userId]);
$projects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Project Update</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/project_updates.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <h1 class="page-title">
        <i class="fa-solid fa-rotate"></i> Send Project Update
    </h1>

    <form action="save.php" method="POST" class="update-form">

        <div class="form-group">
            <label>Project *</label>
            <select name="project_id" required>
                <option value="">-- Select Project --</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>">
                        <?= htmlspecialchars($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Reported Progress (For CEO Notification Only)</label>

            <input type="number" name="progress_percent" min="0" max="100" required>
        </div>

        <div class="form-group">
            <label>Update Description *</label>
            <textarea name="description" rows="4" required></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Submit Update
            </button>
        </div>

    </form>

</div>
</div>

</body>
</html>
