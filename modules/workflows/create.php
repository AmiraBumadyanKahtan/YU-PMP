<?php
// modules/workflows/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "workflow_functions.php";

if (!Auth::can('manage_workflows')) die("Access Denied");

$entities = getEntityTypes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $entity = $_POST['entity_id'];
    $active = isset($_POST['is_active']) ? 1 : 0;

    $newId = createWorkflow($name, $entity, $active);
    if ($newId) {
        header("Location: edit.php?id=" . $newId);
        exit;
    } else {
        $error = "Failed to create workflow.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Workflow</title>
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
        <h1 class="page-title"><i class="fa-solid fa-code-branch"></i> Create Workflow</h1>
        <a href="list.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
    </div>
    
    <div class="form-card">
        <?php if (isset($error)): ?>
            <div class="alert-error">
                <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">Workflow Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Project Budget Approval" autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label">Applies To Entity <span style="color:red">*</span></label>
                <select name="entity_id" class="form-control" required>
                    <option value="">-- Select Entity --</option>
                    <?php foreach($entities as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['entity_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="checkbox-wrapper">
                    <input type="checkbox" name="is_active" checked> 
                    <span class="checkbox-label">Active Workflow</span>
                </label>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-primary">
                    Create & Add Stages <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </form>
    </div>

</div>
</div>
</body>
</html>