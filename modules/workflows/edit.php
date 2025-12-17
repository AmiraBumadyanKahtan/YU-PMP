<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "workflow_functions.php";

if (!Auth::can('manage_rbac')) die("Access Denied");

$id = $_GET['id'] ?? 0;
$workflow = getWorkflowFull($id);

if (!$workflow) die("Workflow not found");

// جلب الأدوار للقائمة المنسدلة
$db = Database::getInstance()->pdo();
$roles = $db->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['workflow_name'];
    $active = isset($_POST['is_active']) ? 1 : 0;
    
    // المراحل تأتي كمصفوفة
    $stages = $_POST['stages'] ?? []; 
    
    if (updateWorkflow($id, $name, $active, $stages)) {
        $success = "Workflow updated successfully!";
        $workflow = getWorkflowFull($id); // تحديث البيانات المعروضة
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <style>
        .stage-row { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; border-radius: 5px; display: flex; gap: 15px; align-items: center; }
        .stage-handle { cursor: move; color: #999; }
        .stage-inputs { flex: 1; display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 0.5fr 0.5fr; gap: 10px; }
        .btn-remove-stage { color: #e74c3c; cursor: pointer; border: none; background: none; font-size: 1.2rem; }
    </style>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">Configure Workflow: <?= htmlspecialchars($workflow['workflow_name']) ?></h1>
        <a href="list.php" class="btn-secondary">Back</a>
    </div>

    <?php if (isset($success)): ?>
        <div style="background:#dff0d8; color:#3c763d; padding:10px; margin-bottom:15px; border-radius:5px;"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <div style="background:#fff; padding:20px; border-radius:8px; margin-bottom:20px;">
            <h3>Basic Settings</h3>
            <div style="display:flex; gap:20px;">
                <div style="flex:1;">
                    <label>Workflow Name</label>
                    <input type="text" name="workflow_name" value="<?= htmlspecialchars($workflow['workflow_name']) ?>" required style="width:100%; padding:8px;">
                </div>
                <div style="flex:0 0 100px;">
                    <label>Status</label>
                    <div>
                        <label><input type="checkbox" name="is_active" <?= $workflow['is_active']?'checked':'' ?>> Active</label>
                    </div>
                </div>
            </div>
        </div>

        <div style="background:#fff; padding:20px; border-radius:8px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <h3>Approval Stages</h3>
                <button type="button" class="btn-primary" onclick="addStage()">+ Add Stage</button>
            </div>

            <div id="stages-container">
                <?php foreach ($workflow['stages'] as $index => $stage): ?>
                    <div class="stage-row">
                        <div class="stage-handle"><i class="fa-solid fa-grip-lines"></i></div>
                        <div class="stage-inputs">
                            <input type="text" name="stages[<?= $index ?>][stage_name]" value="<?= htmlspecialchars($stage['stage_name']) ?>" placeholder="Stage Name (e.g. Finance Review)" required>
                            
                            <select name="stages[<?= $index ?>][assignee_type]" onchange="toggleRoleSelect(this)">
                                <option value="system_role" <?= $stage['assignee_type']=='system_role'?'selected':'' ?>>Specific Role</option>
                                <option value="project_manager" <?= $stage['assignee_type']=='project_manager'?'selected':'' ?>>Project Manager</option>
                                <option value="department_manager" <?= $stage['assignee_type']=='department_manager'?'selected':'' ?>>Department Head</option>
                            </select>

                            <select name="stages[<?= $index ?>][role_id]" class="role-select" <?= $stage['assignee_type']!='system_role'?'style="display:none; background:#eee;" disabled':'' ?>>
                                <option value="">-- Select Role --</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $stage['stage_role_id']==$r['id']?'selected':'' ?>><?= $r['role_name'] ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label style="display:flex; align-items:center; font-size:0.8rem;">
                                <input type="checkbox" name="stages[<?= $index ?>][is_final]" <?= $stage['is_final']?'checked':'' ?>> Final Approval
                            </label>

                            <button type="button" class="btn-remove-stage" onclick="removeStage(this)"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn-primary" style="margin-top:20px; width:100%; padding:15px;">Save Workflow Configuration</button>
    </form>

</div>
</div>

<script>
    let stageCount = <?= count($workflow['stages']) ?>;
    
    // دالة إضافة مرحلة جديدة
    function addStage() {
        const container = document.getElementById('stages-container');
        const html = `
            <div class="stage-row">
                <div class="stage-handle"><i class="fa-solid fa-grip-lines"></i></div>
                <div class="stage-inputs">
                    <input type="text" name="stages[${stageCount}][stage_name]" placeholder="Stage Name" required style="padding:8px;">
                    
                    <select name="stages[${stageCount}][assignee_type]" onchange="toggleRoleSelect(this)" style="padding:8px;">
                        <option value="system_role">Specific Role</option>
                        <option value="project_manager">Project Manager</option>
                        <option value="department_manager">Department Head</option>
                    </select>

                    <select name="stages[${stageCount}][role_id]" class="role-select" style="padding:8px;">
                        <option value="">-- Select Role --</option>
                        <?php foreach($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label style="display:flex; align-items:center; font-size:0.8rem;">
                        <input type="checkbox" name="stages[${stageCount}][is_final]"> Final Approval
                    </label>

                    <button type="button" class="btn-remove-stage" onclick="removeStage(this)"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        stageCount++;
    }

    // دالة حذف مرحلة
    function removeStage(btn) {
        btn.closest('.stage-row').remove();
    }

    // إخفاء/إظهار قائمة الأدوار بناءً على النوع
    function toggleRoleSelect(select) {
        const row = select.closest('.stage-inputs');
        const roleSelect = row.querySelector('.role-select');
        
        if (select.value === 'system_role') {
            roleSelect.style.display = 'block';
            roleSelect.disabled = false;
            roleSelect.style.background = '#fff';
        } else {
            roleSelect.style.display = 'none';
            roleSelect.disabled = true;
        }
    }
</script>

</body>
</html>