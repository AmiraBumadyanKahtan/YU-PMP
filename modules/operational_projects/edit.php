<?php
// modules/operational_projects/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::can('edit_project')) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project Not Found");

// التحقق من حالة القفل (للمشاريع المعتمدة أو قيد الانتظار)
$isLocked = in_array($project['status_id'], [2, 5]);

// معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من خيار الميزانية
    $budgetRequired = $_POST['budget_required'] ?? 'no';
    
    // إذا اختار "لا"، نصفر القيم إلا إذا كانت مقفلة
    if ($budgetRequired == 'no') {
        $b_min = 0;
        $b_max = 0;
        $approved = 0; // أو NULL حسب تصميم قاعدة البيانات
        $b_item = null;
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
        
        // القيم المقفلة (لا تتغير إذا كان المشروع مقفلاً)
        'budget_min' => $isLocked ? $project['budget_min'] : $b_min,
        'budget_max' => $isLocked ? $project['budget_max'] : $b_max,
        'approved_budget' => $isLocked ? $project['approved_budget'] : $approved,
        'budget_item' => $isLocked ? $project['budget_item'] : $b_item,
        'department_id' => $isLocked ? $project['department_id'] : $_POST['department_id'], 
    ];

    if (updateProject($id, $data)) {
        header("Location: view.php?id=$id&msg=updated");
        exit;
    } else {
        $error = "Failed to update project. Please check budget constraints.";
    }
}

$db = Database::getInstance()->pdo();

// جلب المستخدمين (حسب القسم إذا لم يكن سوبر أدمن - منطق مشابه لـ create لكن أبسط هنا للتعديل)
// للتسهيل في التعديل سنجلب الجميع أو حسب منطقك السابق
$users = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 ORDER BY full_name_en")->fetchAll();
$depts = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();

// تحديد حالة الميزانية الحالية للعرض
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
    </style>

    <script>
        function toggleBudget(val) {
            var content = document.getElementById('budget_section_content');
            if (val === 'yes') {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
                // تصفير الحقول بصرياً (لن يتم إرسالها أو سيتم تجاهلها في الخلفية)
                document.getElementById('budget_min').value = 0;
                document.getElementById('budget_max').value = 0;
                document.getElementById('approved_budget').value = 0;
                document.getElementById('budget_item').value = '';
            }
        }
        
        // تشغيل الدالة عند التحميل لضبط الحالة
        window.onload = function() {
            toggleBudget(document.getElementById('budget_required').value);
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
            <i class="fa-solid fa-pen-to-square"></i> Edit Project: <?= htmlspecialchars($project['project_code']) ?>
        </h1>
        <a href="view.php?id=<?= $id ?>" class="btn-secondary">
            <i class="fa-solid fa-xmark"></i> Cancel
        </a>
    </div>

    <?php if (isset($error)): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px;">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
        <div class="notice-box">
            <i class="fa-solid fa-lock"></i> 
            <span>This project is <strong>Pending/Approved</strong>. Critical fields (Department, Budget) are locked.</span>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        
        <div class="form-section">
            <h3><i class="fa-solid fa-circle-info"></i> Project Information</h3>
            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Project Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($project['name']) ?>" class="form-input" required>
                </div>

                <div class="form-group">
                    <label>Visibility</label>
                    <select name="visibility" class="form-select">
                        <option value="private" <?= $project['visibility']=='private'?'selected':'' ?>>Private</option>
                        <option value="public" <?= $project['visibility']=='public'?'selected':'' ?>>Public</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Department</label>
                    <?php if ($isLocked): ?>
                        <div class="locked-wrapper">
                            <input type="text" value="<?= htmlspecialchars($project['department_name']) ?>" class="form-input locked-field" readonly>
                            <input type="hidden" name="department_id" value="<?= $project['department_id'] ?>">
                            <span class="lock-addon"><i class="fa-solid fa-lock"></i></span>
                        </div>
                    <?php else: ?>
                        <select name="department_id" class="form-select">
                            <?php foreach($depts as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $project['department_id'] == $d['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Project Manager</label>
                    <select name="manager_id" class="form-select">
                        <?php foreach($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $project['manager_id']==$u['id']?'selected':'' ?>><?= htmlspecialchars($u['full_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label>Description</label>
                <textarea name="description" class="form-textarea"><?= htmlspecialchars($project['description']) ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fa-solid fa-wallet"></i> Planning & Budget</h3>
            
            <?php if (!$isLocked): ?>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label style="display:inline-block; margin-right: 10px;">Budget Required?</label>
                    <select id="budget_required" name="budget_required" class="form-select" style="width: auto; display: inline-block;" onchange="toggleBudget(this.value)">
                        <option value="yes" <?= $hasBudget ? 'selected' : '' ?>>Yes</option>
                        <option value="no" <?= !$hasBudget ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
            <?php else: ?>
                <input type="hidden" id="budget_required" value="<?= $hasBudget ? 'yes' : 'no' ?>">
            <?php endif; ?>

            <div id="budget_section_content">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Budget Min</label>
                        <?php if ($isLocked): ?>
                            <div class="locked-wrapper">
                                <input type="number" value="<?= $project['budget_min'] ?>" class="form-input locked-field" readonly>
                                <span class="lock-addon"><i class="fa-solid fa-lock"></i></span>
                            </div>
                        <?php else: ?>
                            <input type="number" id="budget_min" name="budget_min" value="<?= $project['budget_min'] ?>" step="0.01" class="form-input">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Budget Max</label>
                        <?php if ($isLocked): ?>
                            <div class="locked-wrapper">
                                <input type="number" value="<?= $project['budget_max'] ?>" class="form-input locked-field" readonly>
                                <span class="lock-addon"><i class="fa-solid fa-lock"></i></span>
                            </div>
                        <?php else: ?>
                            <input type="number" id="budget_max" name="budget_max" value="<?= $project['budget_max'] ?>" step="0.01" class="form-input">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Approved Budget</label>
                        <?php if ($isLocked): ?>
                            <div class="locked-wrapper">
                                <input type="number" value="<?= $project['approved_budget'] ?>" class="form-input locked-field" readonly>
                                <span class="lock-addon"><i class="fa-solid fa-lock"></i></span>
                            </div>
                        <?php else: ?>
                            <input type="number" id="approved_budget" name="approved_budget" value="<?= $project['approved_budget'] ?>" step="0.01" class="form-input">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Budget Item</label>
                        <?php if ($isLocked): ?>
                            <div class="locked-wrapper">
                                <input type="text" value="<?= htmlspecialchars($project['budget_item'] ?? '') ?>" class="form-input locked-field" readonly>
                                <span class="lock-addon"><i class="fa-solid fa-lock"></i></span>
                            </div>
                        <?php else: ?>
                            <input type="text" id="budget_item" name="budget_item" value="<?= htmlspecialchars($project['budget_item'] ?? '') ?>" class="form-input">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

            <div class="form-grid">
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-select">
                        <option value="medium" <?= $project['priority']=='medium'?'selected':'' ?>>Medium</option>
                        <option value="high" <?= $project['priority']=='high'?'selected':'' ?>>High</option>
                        <option value="low" <?= $project['priority']=='low'?'selected':'' ?>>Low</option>
                        <option value="critical" <?= $project['priority']=='critical'?'selected':'' ?>>Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Update Frequency</label>
                    <select name="update_frequency" class="form-select">
                        <option value="weekly" <?= $project['update_frequency']=='weekly'?'selected':'' ?>>Weekly</option>
                        <option value="every_2_days" <?= $project['update_frequency']=='every_2_days'?'selected':'' ?>>Every 2 Days</option>
                        <option value="monthly" <?= $project['update_frequency']=='monthly'?'selected':'' ?>>Monthly</option>
                        <option value="daily" <?= $project['update_frequency']=='daily'?'selected':'' ?>>Daily</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= $project['start_date'] ?>" class="form-input">
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= $project['end_date'] ?>" class="form-input">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
        </div>

    </form>

</div>
</div>
</body>
</html>