<?php
// modules/operational_projects/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::can('create_project')) die("Access Denied");

$db = Database::getInstance()->pdo();
$nextCode = generateProjectCode();

// --- منطق الصلاحيات وتحديد النطاق (Scope) ---
$roleKey = $_SESSION['role_key'];
$myDeptId = $_SESSION['department_id'];

// الأدوار التي ترى كل الأقسام
$superRoles = ['super_admin', 'ceo', 'strategy_office'];
$isSuperUser = in_array($roleKey, $superRoles);

// 1. جلب الأقسام المتاحة
if ($isSuperUser) {
    $depts = $db->query("SELECT * FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();
} else {
    $depts = $db->query("SELECT * FROM departments WHERE id = $myDeptId AND is_deleted=0")->fetchAll();
}

// 2. جلب المدراء (مبدئياً للقسم الحالي أو الأول)
$initialDeptId = $isSuperUser ? ($depts[0]['id'] ?? 0) : $myDeptId;
$users = $db->prepare("SELECT id, full_name_en FROM users WHERE department_id = ? AND is_active = 1 ORDER BY full_name_en");
$users->execute([$initialDeptId]);
$deptUsers = $users->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Project</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/create.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* إضافة بسيطة لإخفاء قسم الميزانية */
        #budget_section_content { transition: all 0.3s ease; }
        .hidden-section { display: none; }
    </style>

    <script>
        // دالة التحقق من الميزانية
        function validateBudget() {
            var budgetRequired = document.getElementById('budget_required').value;
            
            if (budgetRequired === 'no') return true; // لا داعي للتحقق

            var min = parseFloat(document.getElementById('budget_min').value) || 0;
            var max = parseFloat(document.getElementById('budget_max').value) || 0;
            var approved = parseFloat(document.getElementById('approved_budget').value) || 0;

            if (max > 0 && approved > max) {
                Swal.fire({
                    icon: 'error',
                    title: 'Budget Error',
                    text: 'Approved Budget (' + approved + ') cannot be greater than Max Budget (' + max + ').'
                });
                document.getElementById('approved_budget').value = max;
                return false;
            }
            return true;
        }

        // دالة تبديل ظهور قسم الميزانية
        function toggleBudget(val) {
            var content = document.getElementById('budget_section_content');
            if (val === 'yes') {
                content.style.display = 'block';
                // إعادة تفعيل الحقول المطلوبة (اختياري حسب المنطق)
            } else {
                content.style.display = 'none';
                // تصفير القيم عند الإخفاء
                document.getElementById('budget_min').value = '';
                document.getElementById('budget_max').value = '';
                document.getElementById('approved_budget').value = '';
            }
        }

        // AJAX لجلب الموظفين عند تغيير القسم
        // AJAX لجلب الموظفين عند تغيير القسم
function fetchDeptUsers(deptId) {
    var managerSelect = document.querySelector('select[name="manager_id"]');
    managerSelect.innerHTML = '<option value="">Loading...</option>';

    // استخدم BASE_URL التي يطبعها PHP لضمان المسار الصحيح
    // تأكد أنك داخل وسم <script> في ملف PHP
    var apiUrl = '<?php echo BASE_URL; ?>api/get_users_by_dept.php?dept_id=' + deptId;

    fetch(apiUrl)
        .then(response => {
            // التحقق من أن الاستجابة هي JSON وليست HTML خطأ
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            managerSelect.innerHTML = '<option value="">-- Select Manager --</option>';
            
            // التحقق من وجود خطأ في البيانات
            if (data.error) {
                console.error(data.error);
                return;
            }

            if (data.length === 0) {
                 managerSelect.innerHTML = '<option value="">No users found in this department</option>';
            }

            data.forEach(user => {
                var option = document.createElement('option');
                option.value = user.id;
                option.text = user.full_name_en;
                managerSelect.add(option);
            });
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            managerSelect.innerHTML = '<option value="">Error loading users</option>';
        });
}
    </script>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-folder-plus"></i> Create Operational Project
        </h1>
        <a href="index.php" class="btn-secondary">
            <i class="fa-solid fa-xmark"></i> Cancel
        </a>
    </div>

    <form action="save.php" method="POST" class="form-card" onsubmit="return validateBudget()">
        
        <div class="form-section">
            <h3><i class="fa-solid fa-circle-info"></i> Project Definition</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Project Code</label>
                    <input type="text" name="project_code" value="<?= $nextCode ?>" class="form-input input-readonly" readonly>
                </div>
                
                <div class="form-group">
                    <label>Project Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. Annual IT Infrastructure Upgrade">
                </div>

                <div class="form-group">
                    <label>Visibility</label>
                    <select name="visibility" class="form-select">
                        <option value="private">Private (Department Only)</option>
                        <option value="public">Public (Organization Wide)</option>
                    </select>
                </div>
                
                <div class="form-group"></div> 
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-textarea" placeholder="Briefly describe the project goals and scope..."></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fa-solid fa-users"></i> Ownership & Management</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label>Department <span class="req">*</span></label>
                    <select name="department_id" class="form-select <?= !$isSuperUser ? 'input-readonly' : '' ?>" 
                            <?= !$isSuperUser ? 'style="pointer-events:none;"' : 'onchange="fetchDeptUsers(this.value)"' ?>>
                        <?php foreach($depts as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= (!$isSuperUser && $d['id'] == $myDeptId) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if(!$isSuperUser): ?>
                        <input type="hidden" name="department_id" value="<?= $myDeptId ?>">
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Project Manager <span class="req">*</span></label>
                    <select name="manager_id" class="form-select" required>
                        <option value="">-- Select Manager --</option>
                        <?php foreach($deptUsers as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3><i class="fa-solid fa-wallet"></i> Budget & Planning</h3>
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="display:inline-block; margin-right: 10px;">Does this project require a budget?</label>
                <select id="budget_required" name="budget_required" class="form-select" style="width: auto; display: inline-block;" onchange="toggleBudget(this.value)">
                    <option value="yes" selected>Yes, Budget Required</option>
                    <option value="no">No, No Budget Needed</option>
                </select>
            </div>

            <div id="budget_section_content">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Estimated Budget (Min)</label>
                        <input type="number" id="budget_min" name="budget_min" class="form-input" step="0.01" placeholder="0.00" onchange="validateBudget()">
                    </div>
                    <div class="form-group">
                        <label>Estimated Budget (Max)</label>
                        <input type="number" id="budget_max" name="budget_max" class="form-input" step="0.01" placeholder="0.00" onchange="validateBudget()">
                    </div>

                    <div class="form-group">
                        <label>Approved Budget</label>
                        <input type="number" id="approved_budget" name="approved_budget" class="form-input" step="0.01" placeholder="0.00" onchange="validateBudget()">
                        <small style="color:#888;">Must not exceed Max Budget</small>
                    </div>

                    <div class="form-group">
                        <label>Budget Item / Clause (البند المالي)</label>
                        <input type="text" name="budget_item" class="form-input" placeholder="e.g. Operating Expenses - Chapter 2">
                    </div>
                </div>
            </div>

            <hr style="border:0; border-top:1px solid #eee; margin: 20px 0;">

            <div class="form-grid">
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" class="form-select">
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="low">Low</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Update Frequency</label>
                    <select name="update_frequency" class="form-select">
                        <option value="weekly" selected>Weekly</option>
                        <option value="every_2_days">Every 2 Days</option>
                        <option value="monthly">Monthly</option>
                        <option value="daily">Daily</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Planned Start Date</label>
                    <input type="date" name="start_date" class="form-input">
                </div>
                <div class="form-group">
                    <label>Planned End Date</label>
                    <input type="date" name="end_date" class="form-input">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-check"></i> Create Project
            </button>
        </div>

    </form>

</div>
</div>
</body>
</html>