<?php
// modules/operational_projects/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

// 1. جلب المشروع والتحقق من وجوده
$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project Not Found");

// 2. التحقق من الصلاحية (هل المستخدم يملك صلاحية التعديل؟)
if (!userCanInProject($id, 'edit_project')) {
    die("Access Denied: You don't have permission to edit this project.");
}

// 3. تحديد حالة القفل الصارم (Strict Lock)
// التعديل مسموح فقط في حالتين: 1 (Draft) و 3 (Returned)
$allowedEditStatuses = [1, 3]; 
$isLocked = !in_array($project['status_id'], $allowedEditStatuses);

// متغير مساعد لتعطيل الحقول في HTML
$disabledStr = $isLocked ? 'disabled' : '';

// 4. معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // حماية أمنية: منع الحفظ إذا كان المشروع مقفلاً
    if ($isLocked) {
        die("Action Denied: This project is locked and cannot be modified in its current status.");
    }

    // التحقق من خيار الميزانية
    $budgetRequired = $_POST['budget_required'] ?? 'no';
    
    if ($budgetRequired == 'no') {
        $b_min = 0; $b_max = 0; $approved = 0; $b_item = null;
    } else {
        $b_min = $_POST['budget_min'];
        $b_max = $_POST['budget_max'];
        $approved = $_POST['approved_budget'];
        $b_item = $_POST['budget_item'];
    }

    $data = [
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'manager_id' => $_POST['manager_id'],
        'priority' => $_POST['priority'],
        'visibility' => $_POST['visibility'], 
        'update_frequency' => $_POST['update_frequency'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'budget_min' => $b_min,
        'budget_max' => $b_max,
        'approved_budget' => $approved,
        'budget_item' => $b_item,
        'department_id' => $_POST['department_id'], 
    ];

    if (updateProject($id, $data)) {
        header("Location: view.php?id=$id&msg=updated");
        exit;
    } else {
        $error = "Failed to update project.";
    }
}

$db = Database::getInstance()->pdo();

// جلب المستخدمين والأقسام (للعرض فقط حتى لو كان مقفلاً)
$users = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 ORDER BY full_name_en")->fetchAll();
$depts = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();

// تحديد حالة الميزانية الحالية
$hasBudget = ($project['budget_max'] > 0 || $project['approved_budget'] > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Project</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/edit.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        #budget_section_content { transition: all 0.3s ease; }
        /* تنسيق الحقول المعطلة لتبدو واضحة للقراءة */
        input:disabled, select:disabled, textarea:disabled {
            background-color: #f9fafb;
            color: #555;
            cursor: not-allowed;
            border-color: #e5e7eb;
        }
        .locked-banner {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
    </style>

    <script>
        function toggleBudget(val) {
            var content = document.getElementById('budget_section_content');
            if (val === 'yes') {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        }
        
        window.onload = function() {
            var budgetReq = document.getElementById('budget_required');
            if(budgetReq) {
                toggleBudget(budgetReq.value);
            }
        };
    </script>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-pen-to-square"></i> 
            <?= $isLocked ? 'View Project Details' : 'Edit Project' ?>: <?= htmlspecialchars($project['project_code']) ?>
        </h1>
        <a href="index.php?id=<?= $id ?>" class="btn-secondary">
            <i class="fa-solid fa-xmark"></i> <?= $isLocked ? 'Back' : 'Cancel' ?>
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
        <div class="locked-banner">
            <i class="fa-solid fa-lock fa-lg"></i> 
            <span>
                This project is currently <strong><?= htmlspecialchars($project['status_name'] ?? 'Locked') ?></strong>. 
                Editing is disabled to ensure data integrity.
            </span>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        
        <div class="form-section">
            <h3><i class="fa-solid fa-circle-info"></i> Project Information</h3>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Project Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" class="form-input" required <?= $disabledStr ?>>
                </div>

                <div class="form-group">
                    <label>Visibility</label>
                    <select name="visibility" class="form-select" <?= $disabledStr ?>>
                        <option value="private" <?= $project['visibility']=='private'?'selected':'' ?>>Private</option>
                        <option value="public" <?= $project['visibility']=='public'?'selected':'' ?>>Public</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-select" <?= $disabledStr ?>>
                        <?php foreach($depts as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= $project['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Project Manager</label>
                    <select name="manager_id" class="form-select" <?= $disabledStr ?>>
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $project['manager_id']==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['full_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Description</label>
                <textarea name="description" class="form-textarea" <?= $disabledStr ?>><?= htmlspecialchars($project['description']) ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fa-solid fa-wallet"></i> Planning & Budget</h3>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:inline-block; margin-right: 10px;">Budget Required?</label>
                <select id="budget_required" name="budget_required" class="form-select" style="width: auto; display: inline-block;" onchange="toggleBudget(this.value)" <?= $disabledStr ?>>
                    <option value="yes" <?= $hasBudget ? 'selected' : '' ?>>Yes</option>
                    <option value="no" <?= !$hasBudget ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div id="budget_section_content">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Budget Min</label>
                        <input type="number" id="budget_min" name="budget_min" value="<?= $project['budget_min'] ?>" step="0.01" class="form-input" <?= $disabledStr ?>>
                    </div>

                    <div class="form-group">
                        <label>Budget Max</label>
                        <input type="number" id="budget_max" name="budget_max" value="<?= $project['budget_max'] ?>" step="0.01" class="form-input" <?= $disabledStr ?>>
                    </div>
                    
                    <div class="form-group">
                        <label>Approved Budget</label>
                        <input type="number" id="approved_budget" name="approved_budget" value="<?= $project['approved_budget'] ?>" step="0.01" class="form-input" <?= $disabledStr ?>>
                    </div>

                    <div class="form-group">
                        <label>Budget Item</label>
                        <input type="text" id="budget_item" name="budget_item" value="<?= htmlspecialchars($project['budget_item'] ?? '') ?>" class="form-input" <?= $disabledStr ?>>
                    </div>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

            <div class="form-grid">
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-select" <?= $disabledStr ?>>
                        <option value="medium" <?= $project['priority']=='medium'?'selected':'' ?>>Medium</option>
                        <option value="high" <?= $project['priority']=='high'?'selected':'' ?>>High</option>
                        <option value="low" <?= $project['priority']=='low'?'selected':'' ?>>Low</option>
                        <option value="critical" <?= $project['priority']=='critical'?'selected':'' ?>>Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Update Frequency</label>
                    <select name="update_frequency" class="form-select" <?= $disabledStr ?>>
                        <option value="weekly" <?= $project['update_frequency']=='weekly'?'selected':'' ?>>Weekly</option>
                        <option value="every_2_days" <?= $project['update_frequency']=='every_2_days'?'selected':'' ?>>Every 2 Days</option>
                        <option value="monthly" <?= $project['update_frequency']=='monthly'?'selected':'' ?>>Monthly</option>
                        <option value="daily" <?= $project['update_frequency']=='daily'?'selected':'' ?>>Daily</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= $project['start_date'] ?>" class="form-input" <?= $disabledStr ?>>
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= $project['end_date'] ?>" class="form-input" <?= $disabledStr ?>>
                </div>
            </div>
        </div>

        <?php if (!$isLocked): ?>
        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>
        <?php endif; ?>

    </form>

</div>
</div>
</body>
</html>