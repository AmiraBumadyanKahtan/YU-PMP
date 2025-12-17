<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "initiative_functions.php";

if (!Auth::check()) {
    die("Access denied");
}

// فقط أدوار معينة يسمح لها إنشاء مبادرة
if (!Auth::role(['super_admin', 'strategy_office', 'strategy_staff'])) {
    die("You are not allowed to create initiatives.");
}

$errors = [];
$success = false;

// Fetch dropdown data
$pillars    = getAllPillars();
$owners     = getAllOwners();
$objectives = getAllStrategicObjectives();

// Default values
$form = [
    'name'                  => '',
    'description'           => '',
    'impact'                => '',
    'notes'                 => '',
    'pillar_id'             => '',
    'strategic_objective_id'=> '',
    'owner_user_id'         => Auth::id(), // افتراضيًا صاحب الدخول
    'budget_min'            => '',
    'budget_max'            => '',
    'approved_budget'       => '',
    'start_date'            => '',
    'due_date'              => '',
    'priority'              => 'medium',
    'update_frequency'      => 'weekly',
    'update_time'           => '09:00:00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // استلام القيم
    foreach ($form as $key => $_) {
        $form[$key] = trim($_POST[$key] ?? '');
    }

    // تحقق بسيط
    if ($form['pillar_id'] === '') {
        $errors[] = "Pillar is required.";
    }
    if ($form['name'] === '') {
        $errors[] = "Initiative name is required.";
    }

    if (empty($errors)) {
        // تجهيز بيانات الإدخال
        $data = $form;

        try {
            $newId = createInitiative($data);
            $success = true;

            // TODO: تسجيل activity_log لو حبيتي

            // بعد الإنشاء: تحويل لصفحة view (لاحقًا) أو لليست
            header("Location: view.php?id=" . $newId);
            exit;

        } catch (Exception $e) {
            $errors[] = "Error while saving initiative: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Initiative</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/initiatives.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-bullseye"></i> Create Initiative
            </h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form-layout">

            <!-- ROW 1: Pillar + Objective -->
            <div class="form-row">
                <div class="form-group">
                    <label>Pillar <span class="req">*</span></label>
                    <select name="pillar_id" required>
                        <option value="">-- Select Pillar --</option>
                        <?php foreach ($pillars as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= $form['pillar_id']==$p['id'] ? 'selected' : '' ?>>
                                [<?= $p['pillar_number'] ?>] <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Strategic Objective</label>
                    <select name="strategic_objective_id">
                        <option value="">-- Select Objective (optional) --</option>
                        <?php foreach ($objectives as $obj): ?>
                            <option value="<?= $obj['id'] ?>"
                                <?= $form['strategic_objective_id']==$obj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($obj['objective_code']) ?> - 
                                <?= htmlspecialchars($obj['objective_text']) ?> 
                                (Pillar <?= $obj['pillar_number'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ROW 2: Name + Owner -->
            <div class="form-row">
                <div class="form-group">
                    <label>Initiative Name <span class="req">*</span></label>
                    <input type="text" name="name" required
                           value="<?= htmlspecialchars($form['name']) ?>">
                </div>

                <div class="form-group">
                    <label>Owner</label>
                    <select name="owner_user_id">
                        <option value="">-- Select Owner --</option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= $o['id'] ?>"
                                <?= $form['owner_user_id']==$o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['full_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- ROW 3: Description + Impact -->
            <div class="form-row">
                <div class="form-group full-width">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= htmlspecialchars($form['description']) ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group full-width">
                    <label>Expected Impact</label>
                    <textarea name="impact" rows="3"><?= htmlspecialchars($form['impact']) ?></textarea>
                </div>
            </div>

            <!-- ROW 4: Budget -->
            <div class="form-row">
                <div class="form-group">
                    <label>Budget Min</label>
                    <input type="number" step="0.01" name="budget_min"
                           value="<?= htmlspecialchars($form['budget_min']) ?>">
                </div>
                <div class="form-group">
                    <label>Budget Max</label>
                    <input type="number" step="0.01" name="budget_max"
                           value="<?= htmlspecialchars($form['budget_max']) ?>">
                </div>
                <div class="form-group">
                    <label>Approved Budget (optional)</label>
                    <input type="number" step="0.01" name="approved_budget"
                           value="<?= htmlspecialchars($form['approved_budget']) ?>">
                </div>
            </div>

            <!-- ROW 5: Dates -->
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date"
                           value="<?= htmlspecialchars($form['start_date']) ?>">
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date"
                           value="<?= htmlspecialchars($form['due_date']) ?>">
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority">
                        <option value="low"      <?= $form['priority']==='low' ? 'selected' : '' ?>>Low</option>
                        <option value="medium"   <?= $form['priority']==='medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="high"     <?= $form['priority']==='high' ? 'selected' : '' ?>>High</option>
                        <option value="critical" <?= $form['priority']==='critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
            </div>

            <!-- ROW 6: Update frequency -->
            <div class="form-row">
                <div class="form-group">
                    <label>Update Frequency</label>
                    <select name="update_frequency">
                        <option value="daily"      <?= $form['update_frequency']==='daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="every_2_days" <?= $form['update_frequency']==='every_2_days' ? 'selected' : '' ?>>Every 2 days</option>
                        <option value="weekly"     <?= $form['update_frequency']==='weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly"    <?= $form['update_frequency']==='monthly' ? 'selected' : '' ?>>Monthly</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Update Time</label>
                    <input type="time" name="update_time"
                           value="<?= htmlspecialchars($form['update_time']) ?>">
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2"><?= htmlspecialchars($form['notes']) ?></textarea>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-save"></i> Save as Draft
                </button>

                <a href="list.php" class="btn-reset">
                    Cancel
                </a>
            </div>

        </form>

    </div>
</div>

</body>
</html>
