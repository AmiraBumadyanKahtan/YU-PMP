<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "workflow_functions.php";

if (!Auth::can('manage_rbac')) die("Access Denied");

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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">
    <h1 class="page-title">Create New Workflow</h1>
    
    <div style="background:#fff; padding:30px; border-radius:8px; max-width:500px;">
        <form method="POST">
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Workflow Name</label>
                <input type="text" name="name" required style="width:100%; padding:10px;">
            </div>
            
            <div style="margin-bottom:15px;">
                <label style="display:block; margin-bottom:5px;">Applies To</label>
                <select name="entity_id" required style="width:100%; padding:10px;">
                    <?php foreach($entities as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= $e['entity_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:15px;">
                <label>
                    <input type="checkbox" name="is_active" checked> Active
                </label>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;">Create & Add Stages</button>
        </form>
    </div>
</div>
</div>
</body>
</html>