<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once "functions.php";

if (!is_super_admin()) {
    die("Access denied");
}

$db = Database::getInstance()->pdo();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$kpi = get_kpi_by_id($id);

if (!$kpi) {
    $_SESSION['error'] = "KPI not found.";
    header("Location: list.php");
    exit;
}

/* ======================================================
   Parent Name Fetch
====================================================== */

$parent_label = "-";
$parent_name  = "-";

if ($kpi['parent_type'] === "initiative" && !empty($kpi['parent_id'])) {
    $stmt = $db->prepare("SELECT initiative_code, name FROM initiatives WHERE id = ?");
    $stmt->execute([$kpi['parent_id']]);
    $parent = $stmt->fetch();

    if ($parent) {
        $parent_label = "Initiative";
        $parent_name  = $parent['initiative_code'] . " - " . $parent['name'];
    }
}

if ($kpi['parent_type'] === "project" && !empty($kpi['parent_id'])) {
    $stmt = $db->prepare("SELECT project_code, name FROM projects WHERE id = ?");
    $stmt->execute([$kpi['parent_id']]);
    $parent = $stmt->fetch();

    if ($parent) {
        $parent_label = "Project";
        $parent_name  = $parent['project_code'] . " - " . $parent['name'];
    }
}

/* ======================================================
   Check if KPI has related records (progress values)
====================================================== */

$stmt = $db->prepare("SELECT COUNT(*) FROM kpi_updates WHERE kpi_id = ?");
$stmt->execute([$id]);
$related_updates = $stmt->fetchColumn();

/* ======================================================
   Handle Delete (Soft)
====================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $db->prepare("UPDATE kpis SET is_deleted = 1, updated_at = NOW() WHERE id = ?")
        ->execute([$id]);

    // Log delete
    $logStmt = $db->prepare("
        INSERT INTO activity_log (user_id, action, entity_type, entity_id, old_value, new_value, ip_address)
        VALUES (?, 'deleted', 'kpi', ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $_SESSION['user_id'],
        $id,
        json_encode($kpi, JSON_UNESCAPED_UNICODE),
        json_encode(['is_deleted' => 1], JSON_UNESCAPED_UNICODE),
        $_SERVER['REMOTE_ADDR']
    ]);

    $_SESSION['toast_success'] = "KPI deleted successfully.";
    header("Location: list.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete KPI</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

    <style>
        .page-wrapper {
            padding: 2rem;
            font-family: "Times New Roman", serif;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #b30000;
            margin-bottom: 24px;
            display: flex;
            gap: 12px;
        }

        .delete-box {
            background: #fff;
            padding: 2rem;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            max-width: 900px;
        }

        .delete-warning {
            color: #b30000;
            font-weight: 800;
            margin-bottom: 25px;
            font-size: 18px;
        }

        .detail-box {
            padding: 1rem;
            background: #faf3f3;
            border-left: 5px solid #b30000;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .detail-box p {
            margin: 6px 0;
            font-size: 16px;
        }

        .danger-hint {
            background: #fff2f2;
            border-left: 4px solid #dd0000;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #990000;
            font-size: 15px;
        }

        .buttons {
            margin-top: 1.8rem;
            display: flex;
            gap: 15px;
        }

        .btn-delete {
            background: #c00000;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 15px;
            border: none;
        }

        .btn-delete:hover {
            background: #9d0000;
        }

        .btn-cancel {
            background: #ddd;
            color: #333;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: var(--primary-orange);
            color: white;
        }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <!-- Header -->
        <h1 class="page-title">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Delete KPI
        </h1>

        <div class="delete-box">

            <div class="delete-warning">
                Are you sure you want to permanently delete this KPI?
            </div>

            <?php if ($related_updates > 0): ?>
                <div class="danger-hint">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    This KPI has <strong><?= $related_updates ?></strong> update records.  
                    These logs will remain for audit history.
                </div>
            <?php endif; ?>

            <div class="detail-box">
                <p><strong>KPI Name:</strong> <?= htmlspecialchars($kpi['name']); ?></p>
                <p><strong>Target Value:</strong> <?= htmlspecialchars($kpi['target_value']); ?></p>
                <p><strong>Frequency:</strong> <?= htmlspecialchars($kpi['frequency']); ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($kpi['status_id']); ?></p>
                <p><strong>Owner:</strong> <?= htmlspecialchars($kpi['owner_id']); ?></p>
                <p><strong>Parent Type:</strong> <?= ucfirst($kpi['parent_type']); ?></p>
                <p><strong><?= $parent_label ?>:</strong> <?= htmlspecialchars($parent_name); ?></p>
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

<script src="../../assets/js/toast.js"></script>

<?php if (!empty($_SESSION['toast_success'])): ?>
<script> showToast("<?= $_SESSION['toast_success'] ?>", "success"); </script>
<?php unset($_SESSION['toast_success']); endif; ?>

<?php if (!empty($_SESSION['toast_error'])): ?>
<script> showToast("<?= $_SESSION['toast_error'] ?>", "error"); </script>
<?php unset($_SESSION['toast_error']); endif; ?>

</body>
</html>
