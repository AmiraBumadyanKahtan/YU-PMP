<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "actions.php";

// Roles allowed: super_admin + strategy_office + strategy_employee
$allowedRoles = ["super_admin", "strategy_office", "strategy_employee"];
if (!Auth::check() || !in_array($_SESSION['role_key'], $allowedRoles)) {
    die("Access denied");
}

$db = Database::getInstance()->pdo();

// Fetch pillars
$pillars = $db->query("SELECT id, pillar_number, name FROM pillars ORDER BY pillar_number ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pillar_id       = $_POST['pillar_id'] ?? null;
    $objective_text  = trim($_POST['objective_text']);

    if (!$pillar_id) {
        $errors[] = "Please select a pillar.";
    }
    if ($objective_text === "") {
        $errors[] = "Objective text is required.";
    }

    if (empty($errors)) {
        // Auto-generate objective code: OBJ-{pillar_number}.{index}
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM strategic_objectives WHERE pillar_id = ?
        ");
        $stmt->execute([$pillar_id]);
        $count = $stmt->fetchColumn();
        $index = $count + 1;

        // Get pillar number
        $pStmt = $db->prepare("SELECT pillar_number FROM pillars WHERE id = ?");
        $pStmt->execute([$pillar_id]);
        $pillarNumber = $pStmt->fetchColumn();

        $objective_code = "OBJ-" . $pillarNumber . "." . $index;

        try {
            $stmt = $db->prepare("
                INSERT INTO strategic_objectives (pillar_id, objective_code, objective_text)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$pillar_id, $objective_code, $objective_text]);

            // log
            logObjectiveAction(
                $newId,
                "objective_created",
                null,
                [
                    "objective_text" => $objective_text,
                    "pillar_id" => $pillar_id,
                    "objective_code" => $objective_code
                ]
            );

            $_SESSION['toast_success'] = "Strategic Objective added successfully.";
            header("Location: list.php");
            exit;

        } catch (PDOException $e) {
            $_SESSION['toast_error'] = "Error while saving the objective.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Strategic Objective</title>

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
            <h1 class="page-title" ><i class="fa-solid fa-bullseye"></i> Add Strategic Objective</h1>
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
                        <option value="">— Choose Pillar —</option>
                        <?php foreach ($pillars as $p): ?>
                        <option value="<?= $p['id'] ?>">
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
                    <textarea style="width: 97%;" name="objective_text" placeholder="Describe the strategic objective..." required></textarea>
                </div>
            </div>

            <!-- Code Preview (auto) -->
            <div class="auto-code-box">
                <i class="fa-solid fa-code"></i>
                <span>Objective Code will be generated automatically.</span>
            </div>

            <div class="form-actions">
                <button class="btn-submit" type="submit">Create Objective</button>
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
