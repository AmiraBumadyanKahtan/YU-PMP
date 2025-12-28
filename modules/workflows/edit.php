<?php
// modules/workflows/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "workflow_functions.php";

if (!Auth::can('manage_workflows')) die("Access Denied");

$id = $_GET['id'] ?? 0;
$workflow = getWorkflowFull($id);

if (!$workflow) die("Workflow not found");

// جلب الأدوار
$db = Database::getInstance()->pdo();
$roles = $db->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['workflow_name'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    $stages = $_POST['stages'] ?? []; 
    
    if (updateWorkflow($id, $name, $active, $stages)) {
        $success = "Workflow updated successfully!";
        $workflow = getWorkflowFull($id);
    } else {
        $error = "Failed to update workflow.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Workflow</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="css/edit.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i> Edit Workflow
        </h1>
        <a href="list.php" class="btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Basic Configuration</h3>
            </div>
            <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div>
                    <label class="form-label">Workflow Name</label>
                    <input type="text" name="workflow_name" class="form-control" value="<?= htmlspecialchars($workflow['workflow_name']) ?>" required>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <label class="checkbox-label" style="margin-top: 10px;">
                        <input type="checkbox" name="is_active" <?= $workflow['is_active']?'checked':'' ?>> 
                        <span>Active Workflow</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">Approval Stages</h3>
                <button type="button" class="btn-add" onclick="addStage()"><i class="fa-solid fa-plus"></i> Add Stage</button>
            </div>

            <div id="stages-container">
                <?php foreach ($workflow['stages'] as $index => $stage): ?>
                    <div class="stage-row">
                        <div class="stage-handle" title="Drag to reorder"><i class="fa-solid fa-grip-lines"></i></div>
                        <div class="stage-inputs">
                            <input type="text" name="stages[<?= $index ?>][stage_name]" class="form-control" value="<?= htmlspecialchars($stage['stage_name']) ?>" placeholder="Stage Name" required>
                            
                            <select name="stages[<?= $index ?>][assignee_type]" class="form-control" onchange="toggleRoleSelect(this)">
                                <option value="system_role" <?= $stage['assignee_type']=='system_role'?'selected':'' ?>>Specific Role</option>
                                <option value="project_manager" <?= $stage['assignee_type']=='project_manager'?'selected':'' ?>>Project Manager</option>
                                <option value="department_manager" <?= $stage['assignee_type']=='department_manager'?'selected':'' ?>>Department Head</option>
                            </select>

                            <select name="stages[<?= $index ?>][role_id]" class="form-control role-select" <?= $stage['assignee_type']!='system_role'?'style="display:none;" disabled':'' ?>>
                                <option value="">-- Select Role --</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $stage['stage_role_id']==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['role_name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label class="checkbox-label" title="Final Approval Stage">
                                <input type="checkbox" name="stages[<?= $index ?>][is_final]" <?= $stage['is_final']?'checked':'' ?>> 
                                <span>Final</span>
                            </label>

                            <button type="button" class="btn-remove-stage" onclick="removeStage(this)" title="Remove Stage"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="width:100%; padding:15px; font-size:1rem; justify-content:center;">
            <i class="fa-solid fa-save"></i> Save Workflow Configuration
        </button>
    </form>

</div>
</div>

<script>
    let stageCount = <?= count($workflow['stages']) ?>;
    
    function addStage() {
        const container = document.getElementById('stages-container');
        // تم تحديث الـ HTML ليتطابق مع الستايل الجديد
        const html = `
            <div class="stage-row">
                <div class="stage-handle" title="Drag to reorder"><i class="fa-solid fa-grip-lines"></i></div>
                <div class="stage-inputs">
                    <input type="text" name="stages[${stageCount}][stage_name]" class="form-control" placeholder="Stage Name" required>
                    
                    <select name="stages[${stageCount}][assignee_type]" class="form-control" onchange="toggleRoleSelect(this)">
                        <option value="system_role">Specific Role</option>
                        <option value="project_manager">Project Manager</option>
                        <option value="department_manager">Department Head</option>
                    </select>

                    <select name="stages[${stageCount}][role_id]" class="form-control role-select">
                        <option value="">-- Select Role --</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label class="checkbox-label" title="Final Approval Stage">
                        <input type="checkbox" name="stages[${stageCount}][is_final]"> 
                        <span>Final</span>
                    </label>

                    <button type="button" class="btn-remove-stage" onclick="removeStage(this)" title="Remove Stage"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        stageCount++;
    }

    function removeStage(btn) {
        btn.closest('.stage-row').remove();
    }

    function toggleRoleSelect(select) {
        const row = select.closest('.stage-inputs');
        const roleSelect = row.querySelector('.role-select');
        
        if (select.value === 'system_role') {
            roleSelect.style.display = 'block';
            roleSelect.disabled = false;
        } else {
            roleSelect.style.display = 'none';
            roleSelect.disabled = true;
        }
    }
</script>

</body>
</html>