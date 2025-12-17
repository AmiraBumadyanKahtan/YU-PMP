<?php
// modules/operational_projects/milestones.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (file_exists("../project_roles/functions.php")) { require_once "../project_roles/functions.php"; }

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// --- الصلاحيات ---
$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office']);

$canManage = ($isManager || $isSuperAdmin || userCanInProject($id, 'manage_project_tasks'));
$canEditAssigned = ($isManager || $isSuperAdmin || userCanInProject($id, 'edit_assigned_tasks'));

$isApproved = ($project['status_id'] == 5); 

$db = Database::getInstance()->pdo();

// ---------------------------------------------------------
// معالجة النماذج (POST Actions)
// ---------------------------------------------------------

// أ) حذف المهمة (للمدراء فقط)
if (isset($_GET['delete_task']) && $canManage) {
    $taskId = $_GET['delete_task'];
    $msId = $db->query("SELECT milestone_id FROM project_tasks WHERE id=$taskId")->fetchColumn();
    
    $db->prepare("UPDATE project_tasks SET is_deleted=1 WHERE id=?")->execute([$taskId]);
    
    if($msId && function_exists('recalculateMilestone')) {
        recalculateMilestone($msId);
    } else {
        recalculateProject($id); // إذا كانت عامة
    }
    header("Location: milestones.php?id=$id&msg=deleted");
    exit;
}

// ب) حفظ المهمة (إضافة / تعديل)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_task'])) {
    
    $taskId = $_POST['task_id'] ?? 0;
    
    // 1. إذا كان المدير (Full Update/Create)
    if ($canManage) {
        $progress = 0;
        $status = $_POST['status_id'] ?? 1;
        if ($status == 3) $progress = 100;
        elseif ($status == 2) $progress = 50;

        $milestoneId = !empty($_POST['milestone_id']) ? $_POST['milestone_id'] : null;

        $data = [
            'project_id' => $id,
            'milestone_id' => $milestoneId,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'assigned_to' => !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null,
            'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'due_date' => $_POST['due_date'],
            'priority_id' => $_POST['priority_id'] ?? 2,
            'weight' => $_POST['weight'] ?? 1,
            'cost_estimate' => $_POST['cost_estimate'] ?? 0,
            'status_id' => $status,
            'progress' => $progress,
            'cost_spent' => $_POST['cost_spent'] ?? 0
        ];
        
        $res = ['ok' => false];
        if (!empty($taskId)) {
            $res = updateTask($taskId, $data);
        } else {
            $res = createTask($data);
        }

        if ($res['ok']) { header("Location: milestones.php?id=$id&msg=task_saved"); exit; }
        else { $error = $res['error']; }
    }
    
    // 2. إذا كان موظف (Limited Update)
    elseif ($taskId && $canEditAssigned) {
        // نتحقق أن المهمة له
        $assignedTo = $db->query("SELECT assigned_to FROM project_tasks WHERE id=$taskId")->fetchColumn();
        
        if ($assignedTo == $_SESSION['user_id']) {
            $statusId = $_POST['status_id'];
            $costSpent = $_POST['cost_spent'];
            // النسبة تحسب تلقائياً حسب الحالة لتوحيد المنطق (أو يمكن أخذها من المدخلات إذا أردت)
            $progress = 0;
            if ($statusId == 3) $progress = 100;
            elseif ($statusId == 2) $progress = 50;

            $res = updateTaskProgressOnly($taskId, $statusId, $progress, $costSpent);
            
            if ($res['ok']) { header("Location: milestones.php?id=$id&msg=task_saved"); exit; }
            else { $error = $res['error']; }
        } else {
            $error = "Access Denied: This task is not assigned to you.";
        }
    } else {
        $error = "Access Denied.";
    }
}

// ج) إضافة مرحلة (للمدراء فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_milestone']) && $canManage) {
    $newStart = $_POST['start_date'];
    $newDue = $_POST['due_date'];

    $checkOverlap = $db->prepare("SELECT COUNT(*) FROM project_milestones WHERE project_id = ? AND is_deleted = 0 AND (start_date <= ? AND due_date >= ?)");
    $checkOverlap->execute([$id, $newDue, $newStart]);
    
    if ($checkOverlap->fetchColumn() > 0) {
        $error = "Date Conflict: Dates overlap with an existing milestone.";
    } else {
        $data = [
            'project_id' => $id, 'name' => $_POST['name'], 'description' => $_POST['description'],
            'start_date' => $newStart, 'due_date' => $newDue, 'cost_amount' => $_POST['cost_amount'] ?? 0
        ];
        $result = createMilestone($data);
        if ($result['ok']) { header("Location: milestones.php?id=$id&msg=milestone_added"); exit; }
        else { $error = $result['error']; }
    }
}

// جلب البيانات
$milestones = getProjectMilestones($id);
$teamMembers = getProjectTeam($id); 

$generalTasks = $db->prepare("
    SELECT t.*, u.full_name_en as assignee_name, s.name as status_name 
    FROM project_tasks t
    LEFT JOIN users u ON u.id = t.assigned_to
    LEFT JOIN task_statuses s ON s.id = t.status_id
    WHERE t.project_id = ? AND t.milestone_id IS NULL AND t.is_deleted = 0
    ORDER BY t.due_date ASC
");
$generalTasks->execute([$id]);
$generalTasks = $generalTasks->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Milestones - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/milestones.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">

    <style>
        /* إضافة ستايل لإخفاء العناصر غير المسموح للموظف بتعديلها */
        .readonly-view { pointer-events: none; background-color: #f9f9f9; opacity: 0.7; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php if (file_exists("project_header_inc.php")) include "project_header_inc.php"; ?>

    <?php if (isset($error)): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #fca5a5;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($canManage): ?>
    <div style="margin-bottom: 25px; display:flex; justify-content:flex-end; gap:10px;">
        <button onclick="openTaskModal('', true)" class="btn-primary" style="background:#3498db;">
            <i class="fa-solid fa-list-check"></i> Add General Task
        </button>
        <button onclick="openModal('milestoneModal')" class="btn-primary">
            <i class="fa-solid fa-flag"></i> New Milestone
        </button>
    </div>
    <?php endif; ?>

    <div class="milestone-card ms-general">
        <div class="milestone-header" onclick="toggleBody('ms-general')">
            <div style="display:flex; align-items:center; gap:15px;">
                <i class="fa-solid fa-chevron-right" id="icon-ms-general" style="color:#aaa;"></i>
                <div>
                    <div style="font-size:1.1rem; font-weight:700; color:#2c3e50;">
                        <i class="fa-solid fa-layer-group" style="color:#3498db;"></i> General Project Tasks
                    </div>
                    <div style="font-size:0.85rem; color:#888; margin-top:4px;">Tasks not linked to a specific milestone</div>
                </div>
            </div>
            <div style="text-align:right; font-weight:bold; color:#3498db;">
                <?= count($generalTasks) ?> Tasks
            </div>
        </div>
        <div id="ms-general" class="milestone-body" style="display: block;"> 
            <table class="task-table">
                <thead><tr><th>Task</th><th>Assignee</th><th>Weight</th><th>Progress</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($generalTasks)): ?>
                        <tr><td colspan="5" align="center" style="padding:20px; color:#999;">No general tasks found.</td></tr>
                    <?php else: ?>
                        <?php foreach($generalTasks as $t): ?>
                        <tr>
                            <td><b><?= htmlspecialchars($t['title']) ?></b><br><small style="color:#999;">Due: <?= $t['due_date'] ?></small></td>
                            <td><?= htmlspecialchars($t['assignee_name'] ?? '-') ?></td>
                            <td><?= $t['weight'] ?></td>
                            <td><span class="status-pill st-<?= $t['status_id'] ?>"><?= $t['status_name'] ?> (<?= $t['progress'] ?>%)</span></td>
                            <td>
                                <?php 
                                    // تحديد نوع الصلاحية (مدير أم موظف)
                                    $isFullEdit = $canManage;
                                    $isLimitedEdit = (!$canManage && $canEditAssigned && $t['assigned_to'] == $_SESSION['user_id']);
                                ?>

                                <?php if($isFullEdit || $isLimitedEdit): ?>
                                    <i class="fa-solid fa-pen btn-action" title="Edit" 
                                       onclick="editTask(this, <?= $isFullEdit ? 'true' : 'false' ?>)"
                                       data-id="<?= $t['id'] ?>"
                                       data-ms-id=""
                                       data-title="<?= htmlspecialchars($t['title']) ?>"
                                       data-desc="<?= htmlspecialchars($t['description']) ?>"
                                       data-assigned="<?= $t['assigned_to'] ?>"
                                       data-start="<?= $t['start_date'] ?>"
                                       data-due="<?= $t['due_date'] ?>"
                                       data-cost="<?= $t['cost_estimate'] ?>"
                                       data-spent="<?= $t['cost_spent'] ?>"
                                       data-weight="<?= $t['weight'] ?>"
                                       data-priority="<?= $t['priority_id'] ?>"
                                       data-status="<?= $t['status_id'] ?>">
                                    </i>
                                <?php endif; ?>

                                <?php if($canManage): ?>
                                    <a href="?id=<?= $id ?>&delete_task=<?= $t['id'] ?>" onclick="return confirm('Delete task?')" class="btn-action" style="color:#e74c3c;">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (empty($milestones) && empty($generalTasks)): ?>
        <div style="text-align:center; padding:60px; background:#fff; border-radius:12px; border:2px dashed #eee;">
            <i class="fa-solid fa-map-location-dot" style="font-size:3rem; color:#e0e0e0; margin-bottom:15px;"></i>
            <p style="color:#888;">No milestones or tasks defined yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($milestones as $m): ?>
            <?php 
                $msSpent = $m['cost_spent']; $msBudget = $m['cost_amount'];
                $isMsOver = ($msBudget > 0 && $msSpent > $msBudget);
                $isComplete = ($m['progress'] == 100);
            ?>
        <div class="milestone-card <?= $isComplete ? 'ms-completed' : 'ms-active' ?>">
            <div class="milestone-header" onclick="toggleBody('ms-<?= $m['id'] ?>')">
                <div style="display:flex; align-items:center; gap:15px;">
                    <i class="fa-solid fa-chevron-right" id="icon-ms-<?= $m['id'] ?>" style="color:#aaa; transition:0.2s;"></i>
                    <div>
                        <div style="font-size:1.1rem; font-weight:700; color:#333;">
                            <?= htmlspecialchars($m['name']) ?>
                            <?php if($isMsOver): ?><span style="color:red; font-size:0.7rem; margin-left:5px;">(Over Budget)</span><?php endif; ?>
                        </div>
                        <div style="font-size:0.85rem; color:#888; margin-top:4px;">
                            <i class="fa-regular fa-calendar"></i> <?= date('M d', strtotime($m['start_date'])) ?> - <?= date('M d', strtotime($m['due_date'])) ?>
                            <?php if($msBudget > 0): ?> <span style="margin-left:10px;">| Budget: <?= number_format($msBudget) ?></span> <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div style="text-align:right;">
                    <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $m['progress'] ?>%;"></div></div>
                    <div style="font-size:0.8rem; color:#777; margin-top:3px;"><?= $m['progress'] ?>% Done</div>
                </div>
            </div>
            
            <div id="ms-<?= $m['id'] ?>" class="milestone-body">
                <table class="task-table">
                    <thead><tr><th>Task</th><th>Assignee</th><th>Weight</th><th>Cost</th><th>Progress</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($m['tasks'])): ?>
                            <tr><td colspan="6" align="center" style="padding:20px; color:#999;">No tasks in this milestone.</td></tr>
                        <?php else: ?>
                            <?php foreach($m['tasks'] as $t): ?>
                            <tr>
                                <td><b><?= htmlspecialchars($t['title']) ?></b><br><small style="color:#999;">Due: <?= $t['due_date'] ?></small></td>
                                <td><?= htmlspecialchars($t['assignee_name'] ?? '-') ?></td>
                                <td><?= $t['weight'] ?></td>
                                <td><?= ($t['cost_estimate'] > 0) ? number_format($t['cost_estimate']) : '<span style="color:#ccc">Optional</span>' ?></td>
                                <td>
                                    <div class="progress-bar-bg" style="width:50px; height:4px;"><div class="progress-bar-fill" style="width: <?= $t['progress'] ?>%;"></div></div>
                                    <span style="font-size:0.75rem;"><?= $t['progress'] ?>%</span>
                                </td>
                                <td>
                                    <span class="status-pill st-<?= $t['status_id'] ?>"><?= $t['status_name'] ?></span>
                                    
                                    <?php 
                                        $isFullEdit = $canManage;
                                        $isLimitedEdit = (!$canManage && $canEditAssigned && $t['assigned_to'] == $_SESSION['user_id']);
                                    ?>

                                    <?php if($isFullEdit || $isLimitedEdit): ?>
                                        <i class="fa-solid fa-pen btn-action" title="Edit" 
                                           onclick="editTask(this, <?= $isFullEdit ? 'true' : 'false' ?>)"
                                           data-id="<?= $t['id'] ?>"
                                           data-ms-id="<?= $t['milestone_id'] ?>"
                                           data-title="<?= htmlspecialchars($t['title']) ?>"
                                           data-desc="<?= htmlspecialchars($t['description']) ?>"
                                           data-assigned="<?= $t['assigned_to'] ?>"
                                           data-start="<?= $t['start_date'] ?>"
                                           data-due="<?= $t['due_date'] ?>"
                                           data-cost="<?= $t['cost_estimate'] ?>"
                                           data-spent="<?= $t['cost_spent'] ?>"
                                           data-weight="<?= $t['weight'] ?>"
                                           data-priority="<?= $t['priority_id'] ?>"
                                           data-status="<?= $t['status_id'] ?>">
                                        </i>
                                    <?php endif; ?>

                                    <?php if($canManage): ?>
                                        <a href="?id=<?= $id ?>&delete_task=<?= $t['id'] ?>" onclick="return confirm('Delete?')" class="btn-action" style="color:#e74c3c;">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if($canManage): ?>
                    <div style="padding:15px; text-align:right;">
                        <button onclick="event.stopPropagation(); openTaskModal('<?= $m['id'] ?>', true)" class="btn-primary" style="padding:5px 15px; font-size:0.85rem; background:#fff4e0; color:#ff8c00;">+ Add Task</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</div>

<div id="milestoneModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>New Milestone</h3><span class="close-btn" onclick="closeModal('milestoneModal')">&times;</span></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="add_milestone" value="1">
                <div class="form-group"><label class="form-label">Name <span style="color:red">*</span></label><input type="text" name="name" required class="form-input"></div>
                <div class="form-grid">
                    <div><label class="form-label">Start Date <span style="color:red">*</span></label><input type="date" name="start_date" required class="form-input"></div>
                    <div><label class="form-label">Due Date <span style="color:red">*</span></label><input type="date" name="due_date" required class="form-input"></div>
                </div>
                <div class="form-group"><label class="form-label">Estimated Cost (SAR) <small style="color:#999;">(Optional)</small></label><input type="number" name="cost_amount" step="0.01" class="form-input" placeholder="0.00"></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('milestoneModal')" class="btn-secondary" style="margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-primary">Create Milestone</button>
            </div>
        </form>
    </div>
</div>

<div id="taskModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3 id="taskModalTitle">Task Details</h3><span class="close-btn" onclick="closeModal('taskModal')">&times;</span></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="save_task" value="1">
                <input type="hidden" name="task_id" id="t_id">
                
                <div id="manager-fields">
                    <div class="form-group">
                        <label class="form-label">Milestone</label>
                        <select name="milestone_id" id="t_ms_id" class="form-select">
                            <option value="">-- General Project Task (No Milestone) --</option>
                            <?php foreach($milestones as $ms): ?>
                                <option value="<?= $ms['id'] ?>"><?= htmlspecialchars($ms['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group"><label class="form-label">Task Title <span style="color:red">*</span></label><input type="text" name="title" id="t_title" required class="form-input"></div>
                    <div class="form-grid">
                        <div>
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" id="t_assigned" class="form-select">
                                <option value="">-- Unassigned --</option>
                                <?php foreach($teamMembers as $tm): ?><option value="<?= $tm['user_id'] ?>"><?= htmlspecialchars($tm['full_name_en']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Priority</label>
                            <select name="priority_id" id="t_priority" class="form-select">
                                <option value="1">Low</option><option value="2" selected>Medium</option><option value="3">High</option><option value="4">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div><label class="form-label">Start Date</label><input type="date" name="start_date" id="t_start" class="form-input"></div>
                        <div><label class="form-label">Due Date <span style="color:red">*</span></label><input type="date" name="due_date" id="t_due" required class="form-input"></div>
                    </div>
                </div>

                <div style="background:#fff8e1; padding:15px; border-radius:8px; border:1px dashed #ffcc80; margin-bottom:15px; margin-top:15px;">
                    <h4 style="margin:0 0 10px 0; color:#d35400; font-size:0.9rem;">Status, Weight & Financials</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label">Current Status</label>
                            <select name="status_id" id="t_status" class="form-select" onchange="updateProgressDisplay(this.value)">
                                <option value="1">Pending (0%)</option><option value="2">In Progress (50%)</option><option value="3">Completed (100%)</option><option value="4">On Hold</option>
                            </select>
                        </div>
                        
                        <div id="manager-financials">
                            <div><label class="form-label">Weight (1-10)</label><input type="number" name="weight" id="t_weight" value="1" min="1" max="10" class="form-input" style="margin-bottom:15px;"></div>
                            <div><label class="form-label">Est. Cost</label><input type="number" name="cost_estimate" id="t_cost" value="0" step="0.01" class="form-input"></div>
                        </div>

                        <div><label class="form-label">Spent Cost</label><input type="number" name="cost_spent" id="t_spent" value="0" step="0.01" class="form-input"></div>
                        
                        <div><label class="form-label">Progress % (Auto)</label><input type="text" name="progress" id="t_progress" class="form-input" readonly style="background:#eee;"></div>
                    </div>
                </div>
                
                <div id="manager-desc">
                    <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="t_desc" class="form-textarea" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('taskModal')" class="btn-secondary" style="margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-primary">Save Task</button>
            </div>
        </form>
    </div>
</div>

<script src="js/milestones.js"></script>
<script>
    function toggleBody(id) {
        var el = document.getElementById(id);
        var isOpen = (el.style.display === 'block');
        el.style.display = isOpen ? 'none' : 'block';
        var icon = document.getElementById('icon-' + id);
        if(icon) icon.className = isOpen ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-down';
    }

    function openModal(id) { document.getElementById(id).style.display = "block"; }
    function closeModal(id) { document.getElementById(id).style.display = "none"; }
    
    function openTaskModal(msId, isFullEdit) {
        setupTaskModal(isFullEdit);
        document.getElementById('taskModalTitle').innerText = "Add New Task";
        document.getElementById('t_id').value = "";
        
        document.getElementById('t_ms_id').value = msId ? msId : ""; 
        
        document.getElementById('t_title').value = "";
        document.getElementById('t_desc').value = "";
        document.getElementById('t_assigned').value = "";
        document.getElementById('t_start').value = "";
        document.getElementById('t_due').value = "";
        document.getElementById('t_cost').value = "0";
        document.getElementById('t_spent').value = "0";
        document.getElementById('t_weight').value = "1";
        document.getElementById('t_status').value = "1";
        updateProgressDisplay("1");
        openModal('taskModal');
    }

    function editTask(btn, isFullEdit) {
        setupTaskModal(isFullEdit);
        document.getElementById('taskModalTitle').innerText = isFullEdit ? "Edit Task" : "Update Task Progress";
        
        document.getElementById('t_id').value = btn.getAttribute('data-id');
        
        var msId = btn.getAttribute('data-ms-id');
        document.getElementById('t_ms_id').value = msId ? msId : "";
        
        document.getElementById('t_title').value = btn.getAttribute('data-title');
        document.getElementById('t_desc').value = btn.getAttribute('data-desc');
        document.getElementById('t_assigned').value = btn.getAttribute('data-assigned');
        document.getElementById('t_start').value = btn.getAttribute('data-start');
        document.getElementById('t_due').value = btn.getAttribute('data-due');
        document.getElementById('t_cost').value = btn.getAttribute('data-cost');
        document.getElementById('t_spent').value = btn.getAttribute('data-spent');
        document.getElementById('t_priority').value = btn.getAttribute('data-priority');
        document.getElementById('t_weight').value = btn.getAttribute('data-weight');
        document.getElementById('t_status').value = btn.getAttribute('data-status');
        
        updateProgressDisplay(btn.getAttribute('data-status'));
        openModal('taskModal');
    }

    function setupTaskModal(isFullEdit) {
        // التحكم في ظهور الحقول بناءً على الصلاحية
        const managerFields = document.getElementById('manager-fields');
        const managerFinancials = document.getElementById('manager-financials');
        const managerDesc = document.getElementById('manager-desc');
        
        if (isFullEdit) {
            // المدير: يرى ويعدل كل شيء
            managerFields.style.display = 'block';
            managerFinancials.style.display = 'block';
            managerDesc.style.display = 'block';
            // إزالة القيد (pointer-events) في حال كان موجوداً
            managerFields.classList.remove('readonly-view');
        } else {
            // الموظف: يرى الحقول للقراءة فقط (Read-only) ليتمكن من معرفة المهمة، لكن لا يعدلها
            // نستخدم CSS class لتعطيل التفاعل وجعلها تبدو كـ Disabled
            managerFields.classList.add('readonly-view');
            managerFinancials.style.display = 'none'; // نخفي الوزن والتكلفة التقديرية تماماً
            managerDesc.classList.add('readonly-view'); // الوصف للقراءة فقط
        }
    }

    function updateProgressDisplay(statusId) {
        let progress = 0;
        if(statusId == 3) progress = 100;
        else if(statusId == 2) progress = 50;
        else progress = 0;
        document.getElementById('t_progress').value = progress;
    }

    <?php if(isset($_GET['msg'])): ?>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        const msg = "<?= $_GET['msg'] ?>";
        if(msg == 'milestone_added') Toast.fire({icon: 'success', title: 'Milestone Created'});
        if(msg == 'task_saved') Toast.fire({icon: 'success', title: 'Task Saved'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Task Deleted'});
    <?php endif; ?>
</script>

</body>
</html>