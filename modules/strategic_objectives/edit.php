<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "actions.php";

// Only strategy roles can edit
$allowedRoles = ["super_admin", "strategy_office", "strategy_employee"];
if (!Auth::check() || !in_array($_SESSION['role_key'], $allowedRoles)) {
    die("Access denied");
}

$db = Database::getInstance()->pdo();

// Ensure ID exists
if (!isset($_GET['id'])) {
    die("Invalid request");
}

$objective_id = (int) $_GET['id'];

// Fetch objective
$stmt = $db->prepare("SELECT * FROM strategic_objectives WHERE id = ?");
$stmt->execute([$objective_id]);
$objective = $stmt->fetch();

if (!$objective) {
    die("Objective not found");
}

// Fetch pillars
$pillars = $db->query("SELECT id, pillar_number, name FROM pillars ORDER BY pillar_number ASC")->fetchAll();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pillar_id      = $_POST['pillar_id'] ?? null;
    $objective_text = trim($_POST['objective_text']);

    if (!$pillar_id) {
        $errors[] = "Please select a pillar.";
    }

    if ($objective_text === "") {
        $errors[] = "Objective text is required.";
    }

    if (empty($errors)) {

        // Detect if pillar changed
        $pillarChanged = ($pillar_id != $objective['pillar_id']);

        if ($pillarChanged) {
            // Get pillar number
            $pStmt = $db->prepare("SELECT pillar_number FROM pillars WHERE id = ?");
            $pStmt->execute([$pillar_id]);
            $pillarNumber = $pStmt->fetchColumn();

            // Count existing objectives in the new pillar
            $countStmt = $db->prepare("SELECT COUNT(*) FROM strategic_objectives WHERE pillar_id = ?");
            $countStmt->execute([$pillar_id]);
            $count = $countStmt->fetchColumn();
            $index = $count + 1;

            $new_code = "OBJ-".$pillarNumber.".".$index;
        } else {
            $new_code = $objective['objective_code']; // no change
        }

        try {
            $stmt = $db->prepare("
                UPDATE strategic_objectives
                SET pillar_id = ?, objective_code = ?, objective_text = ?
                WHERE id = ?
            ");
            $stmt->execute([$pillar_id, $new_code, $objective_text, $objective_id]);

            // log
            logObjectiveAction(
                $objective_id,
                "objective_updated",
                ["old_text" => $old['objective_text'], "old_pillar" => $old['pillar_id']],
                ["new_text" => $objective_text, "new_pillar" => $pillar_id]
            );

            $_SESSION['toast_success'] = "Objective updated successfully.";
            header("Location: list.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['toast_error'] = "Error updating objective.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Strategic Objective</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="stylesheet" href="css/objective_form.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

</head>

<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title"><i class="fa-solid fa-edit"></i> Edit Objective</h1>
            <a href="list.php" class="btn-back">← Back to Objectives</a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?>
                <p>• <?= $e ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="form-card">

            <!-- Box 1 -->
            <div class="box">
                <div class="box-title">
                    <i class="fa-solid fa-layer-group"></i> Select Pillar
                </div>
                <div class="box-body">
                    <select name="pillar_id" required>
                        <?php foreach ($pillars as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $objective['pillar_id'] == $p['id'] ? 'selected' : '' ?>>
                            <?= "Pillar " . $p['pillar_number'] . " — " . htmlspecialchars($p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Box 2 -->
            <div class="box">
                <div class="box-title">
                    <i class="fa-solid fa-bullseye"></i> Objective Text
                </div>
                <div class="box-body">
                    <textarea style="width: 97%;" name="objective_text" required><?= htmlspecialchars($objective['objective_text']) ?></textarea>
                </div>
            </div>

            <!-- Box 3 - Code (read-only) -->
            <div class="code-box">
                <i class="fa-solid fa-code"></i>
                <span>Current code: <b><?= $objective['objective_code'] ?></b></span>
            </div>

            <div class="form-actions">
                <button class="btn-submit" type="submit">Save Changes</button>
                <a href="list.php" class="btn-cancel">Cancel</a>
            </div>

        </form>
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
