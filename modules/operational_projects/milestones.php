<?php
// modules/operational_projects/milestones.php
require_once "php/milestones_BE.php";
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
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php if (file_exists("project_header_inc.php")) include "project_header_inc.php"; ?>

    <?php if (!$isLockedStatus): ?>
        <div class="alert-box alert-locked">
            <i class="fa-solid fa-lock"></i> 
            <div>Project is currently <strong>Locked</strong>. New requests are disabled.</div>
        </div>
    <?php endif; ?>

    <?php if (!$hasMilestones && $isLockedStatus): ?>
        <div class="alert-box alert-mandatory">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><strong>Action Required:</strong> Creating at least one milestone is mandatory.</div>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert-box alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error ?></div>
    <?php endif; ?>

    <div style="margin-bottom: 30px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 style="margin:0; font-size:1.5rem; color:#2d3436; font-weight:800;">Timeline Roadmap</h2>
            <p style="margin:5px 0 0; color:#a0aec0; font-size:0.9rem;">
                <i class="fa-regular fa-calendar-check"></i> Project Duration: 
                <?= date('M d, Y', strtotime($project['start_date'])) ?> - <?= date('M d, Y', strtotime($project['end_date'])) ?>
            </p>
        </div>
        <div style="display:flex; gap:12px;">
            <?php if ($canManageTasks): ?>
                <button onclick="openTaskModal('', true)" class="btn-secondary">
                    <i class="fa-solid fa-list-check"></i> General Task
                </button>
            <?php endif; ?>
            
            <?php if ($canManageMilestones): ?>
                <button onclick="openModal('milestoneModal')" class="btn-primary">
                    <i class="fa-solid fa-plus"></i> New Milestone
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="timeline-container">
        
        <?php if (!empty($generalTasks)): ?>
        <div class="timeline-item ms-general-item">
            <div class="timeline-dot"></div>
            <div class="milestone-card ms-general">
                <div class="milestone-header" onclick="toggleBody('ms-general')">
                    <div style="display:flex; align-items:center; gap:15px;">
                        <div style="width:36px; height:36px; background:#e3f2fd; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#3498db;">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <div class="ms-title" style="font-size:1rem;">General Project Tasks</div>
                            <div class="ms-meta">Uncategorized tasks</div>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <span style="font-weight:700; color:#3498db; font-size:0.9rem;"><?= count($generalTasks) ?> Tasks</span>
                        <i class="fa-solid fa-chevron-right" id="icon-ms-general" style="color:#bdc3c7; font-size:0.9rem;"></i>
                    </div>
                </div>
                <div id="ms-general" class="milestone-body"> 
                    <table class="task-table">
                        <thead><tr><th width="40%">Task</th><th>Assignee</th><th>Weight</th><th>Progress</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($generalTasks as $t): ?>
                            <tr>
                                <td>
                                    <div class="task-title"><?= htmlspecialchars($t['title']) ?></div>
                                    <div class="task-due"><i class="fa-regular fa-clock"></i> <?= $t['due_date'] ?></div>
                                </td>
                                <td><?= htmlspecialchars($t['assignee_name'] ?? '-') ?></td>
                                <td><?= $t['weight'] ?></td>
                                <td><span class="status-pill st-<?= $t['status_id'] ?>"><?= $t['status_name'] ?> (<?= $t['progress'] ?>%)</span></td>
                                <td>
                                    <?php 
                                        $isFullEdit = $canManageTasks;
                                        $isLimitedEdit = (!$canManageTasks && $canEditOwnTasks && $t['assigned_to'] == $_SESSION['user_id']);
                                    ?>
                                    <?php if($isFullEdit || $isLimitedEdit): ?>
                                        <i class="fa-solid fa-pen btn-action" title="Edit" onclick="editTask(this, <?= $isFullEdit ? 'true' : 'false' ?>)" 
                                           data-id="<?= $t['id'] ?>" data-ms-id="" data-title="<?= htmlspecialchars($t['title']) ?>" 
                                           data-desc="<?= htmlspecialchars($t['description']) ?>" data-assigned="<?= $t['assigned_to'] ?>" 
                                           data-start="<?= $t['start_date'] ?>" data-due="<?= $t['due_date'] ?>" 
                                           data-cost="<?= $t['cost_estimate'] ?>" data-spent="<?= $t['cost_spent'] ?>" 
                                           data-weight="<?= $t['weight'] ?>" data-priority="<?= $t['priority_id'] ?>" 
                                           data-status="<?= $t['status_id'] ?>"></i>
                                    <?php endif; ?>
                                    <?php if($canManageTasks): ?>
                                        <a href="?id=<?= $id ?>&delete_task=<?= $t['id'] ?>" onclick="return confirm('Delete task?')" class="btn-action delete"><i class="fa-solid fa-trash-can"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($milestones) && empty($generalTasks)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-map-location-dot empty-icon"></i>
                <h3 style="margin:0 0 10px 0; color:#4a5568;">Start Your Roadmap</h3>
                <p class="empty-text">Create a milestone to begin tracking your project timeline.</p>
            </div>
        <?php else: ?>
            <?php foreach ($milestones as $m): ?>
                <?php 
                    $msSpent = $m['cost_spent']; $msBudget = $m['cost_amount'];
                    $isMsOver = ($msBudget > 0 && $msSpent > $msBudget);
                    $isComplete = ($m['progress'] == 100);
                ?>
            <div class="timeline-item <?= $isComplete ? 'ms-completed' : 'ms-active' ?>">
                <div class="timeline-dot"></div> <div class="milestone-card">
                    <div class="milestone-header" onclick="toggleBody('ms-<?= $m['id'] ?>')">
                        <div style="flex-grow:1;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                <div class="ms-title">
                                    <?= htmlspecialchars($m['name']) ?>
                                    <?php if($isMsOver): ?><span style="background:#fee2e2; color:#c0392b; font-size:0.7rem; padding:2px 6px; border-radius:4px;">Over Budget</span><?php endif; ?>
                                </div>
                                <div class="ms-meta"><i class="fa-regular fa-calendar"></i> <?= date('M d', strtotime($m['start_date'])) ?> - <?= date('M d', strtotime($m['due_date'])) ?></div>
                            </div>
                            <div style="display:flex; align-items:center; justify-content:space-between;">
                                <div class="ms-meta">
                                    <?php if($msBudget > 0): ?> <span><i class="fa-solid fa-coins"></i> <?= number_format($msBudget) ?> SAR</span> <?php endif; ?>
                                </div>
                                <div style="display:flex; align-items:center; gap:15px;">
                                    <div class="ms-progress-wrapper">
                                        <div class="progress-bar-bg"><div class="progress-bar-fill" style="width: <?= $m['progress'] ?>%;"></div></div>
                                        <div class="ms-percent"><?= $m['progress'] ?>%</div>
                                    </div>
                                    <i class="fa-solid fa-chevron-right" id="icon-ms-<?= $m['id'] ?>" style="color:#bdc3c7; transition:0.3s;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="ms-<?= $m['id'] ?>" class="milestone-body">
                        <table class="task-table">
                            <thead><tr><th width="35%">Task</th><th>Assignee</th><th>Weight</th><th>Cost</th><th>Progress</th><th>Action</th></tr></thead>
                            <tbody>
                                <?php if (empty($m['tasks'])): ?>
                                    <tr><td colspan="6" align="center" style="padding:30px; color:#cbd5e0; font-style:italic;">No tasks added to this milestone yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach($m['tasks'] as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="task-title"><?= htmlspecialchars($t['title']) ?></div>
                                            <div class="task-due"><i class="fa-regular fa-clock"></i> <?= $t['due_date'] ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($t['assignee_name'] ?? '-') ?></td>
                                        <td><?= $t['weight'] ?></td>
                                        <td><?= ($t['cost_estimate'] > 0) ? number_format($t['cost_estimate']) : '-' ?></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <div class="progress-bar-bg" style="width:50px; height:4px; margin:0;"><div class="progress-bar-fill" style="width: <?= $t['progress'] ?>%; background:#3498db;"></div></div>
                                                <span style="font-size:0.75rem; color:#7f8c8d;"><?= $t['progress'] ?>%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center;">
                                                <span class="status-pill st-<?= $t['status_id'] ?>" style="margin-right:8px;"><?= $t['status_name'] ?></span>
                                                <?php 
                                                    $isFullEdit = $canManageTasks;
                                                    $isLimitedEdit = (!$canManageTasks && $canEditOwnTasks && $t['assigned_to'] == $_SESSION['user_id']);
                                                ?>
                                                <?php if($isFullEdit || $isLimitedEdit): ?>
                                                    <i class="fa-solid fa-pen btn-action" title="Edit" onclick="editTask(this, <?= $isFullEdit ? 'true' : 'false' ?>)" 
                                                       data-id="<?= $t['id'] ?>" data-ms-id="<?= $t['milestone_id'] ?>" data-title="<?= htmlspecialchars($t['title']) ?>" 
                                                       data-desc="<?= htmlspecialchars($t['description']) ?>" data-assigned="<?= $t['assigned_to'] ?>" 
                                                       data-start="<?= $t['start_date'] ?>" data-due="<?= $t['due_date'] ?>" 
                                                       data-cost="<?= $t['cost_estimate'] ?>" data-spent="<?= $t['cost_spent'] ?>" 
                                                       data-weight="<?= $t['weight'] ?>" data-priority="<?= $t['priority_id'] ?>" 
                                                       data-status="<?= $t['status_id'] ?>"></i>
                                                <?php endif; ?>
                                                <?php if($canManageTasks): ?>
                                                    <a href="?id=<?= $id ?>&delete_task=<?= $t['id'] ?>" onclick="return confirm('Delete?')" class="btn-action delete"><i class="fa-solid fa-trash-can"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <?php if($canManageTasks): ?>
                            <button onclick="event.stopPropagation(); openTaskModal('<?= $m['id'] ?>', true)" class="btn-add-task-row">
                                <i class="fa-solid fa-plus"></i> Add New Task
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div> </div>
</div>

<div id="milestoneModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>New Milestone</h3><span class="close-btn" onclick="closeModal('milestoneModal')">&times;</span></div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="add_milestone" value="1">
                <div class="form-group"><label class="form-label">Name <span style="color:#e74c3c">*</span></label><input type="text" name="name" required class="form-input" placeholder="e.g. Design Phase"></div>
                <div class="form-grid">
                    <div>
                        <label class="form-label">Start Date <span style="color:#e74c3c">*</span></label>
                        <input type="date" name="start_date" required class="form-input" min="<?= $project['start_date'] ?>" max="<?= $project['end_date'] ?>">
                    </div>
                    <div>
                        <label class="form-label">Due Date <span style="color:#e74c3c">*</span></label>
                        <input type="date" name="due_date" required class="form-input" min="<?= $project['start_date'] ?>" max="<?= $project['end_date'] ?>">
                    </div>
                </div>
                <div class="form-group"><label class="form-label">Estimated Budget (SAR)</label><input type="number" name="cost_amount" step="0.01" class="form-input" placeholder="0.00"></div>
                <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-textarea" placeholder="Brief details..."></textarea></div>
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

                    <div class="form-group"><label class="form-label">Task Title <span style="color:#e74c3c">*</span></label><input type="text" name="title" id="t_title" required class="form-input"></div>
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
                        <div><label class="form-label">Due Date <span style="color:#e74c3c">*</span></label><input type="date" name="due_date" id="t_due" required class="form-input"></div>
                    </div>
                </div>

                <div style="background:#fffcf7; padding:20px; border-radius:12px; border:1px solid #ffe0b2; margin:20px 0;">
                    <h4 style="margin:0 0 15px 0; color:#e67e22; font-size:0.95rem; font-weight:700;"><i class="fa-solid fa-chart-line"></i> Progress & Financials</h4>
                    <div class="form-grid">
                        <div>
                            <label class="form-label">Current Status</label>
                            <select name="status_id" id="t_status" class="form-select" onchange="updateProgressDisplay(this.value)">
                                <option value="1">Pending (0%)</option><option value="2">In Progress (50%)</option><option value="3">Completed (100%)</option><option value="4">On Hold</option>
                            </select>
                        </div>
                        
                        <div id="manager-financials">
                            <div style="margin-bottom:15px;"><label class="form-label">Weight (1-10)</label><input type="number" name="weight" id="t_weight" value="1" min="1" max="10" class="form-input"></div>
                            <div><label class="form-label">Est. Cost</label><input type="number" name="cost_estimate" id="t_cost" value="0" step="0.01" class="form-input"></div>
                        </div>

                        <div><label class="form-label">Spent Cost</label><input type="number" name="cost_spent" id="t_spent" value="0" step="0.01" class="form-input"></div>
                        <div><label class="form-label">Progress % (Auto)</label><input type="text" name="progress" id="t_progress" class="form-input" readonly style="background:#f1f2f6;"></div>
                    </div>
                </div>
                
                <div id="manager-desc">
                    <div class="form-group"><label class="form-label">Description</label><textarea name="description" id="t_desc" class="form-textarea" placeholder="Detailed instructions..."></textarea></div>
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
        var isOpen = (el.classList.contains('open'));
        if (isOpen) {
            el.classList.remove('open');
            document.getElementById('icon-' + id).style.transform = "rotate(0deg)";
        } else {
            el.classList.add('open');
            document.getElementById('icon-' + id).style.transform = "rotate(90deg)";
        }
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
        document.getElementById('taskModalTitle').innerText = isFullEdit ? "Edit Task Details" : "Update Task Progress";
        
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
        const managerFields = document.getElementById('manager-fields');
        const managerFinancials = document.getElementById('manager-financials');
        const managerDesc = document.getElementById('manager-desc');
        
        if (isFullEdit) {
            managerFields.style.display = 'block';
            managerFinancials.style.display = 'block';
            managerDesc.style.display = 'block';
            managerFields.classList.remove('readonly-view');
        } else {
            managerFields.classList.add('readonly-view');
            managerFinancials.style.display = 'none'; 
            managerDesc.classList.add('readonly-view');
        }
    }

    function updateProgressDisplay(statusId) {
        let progress = 0;
        if(statusId == 3) progress = 100;
        else if(statusId == 2) progress = 50;
        else progress = 0;
        document.getElementById('t_progress').value = progress;
    }

    // Close Modal on Outside Click
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
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
        if(msg == 'task_saved') Toast.fire({icon: 'success', title: 'Task Saved Successfully'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Item Deleted'});
    <?php endif; ?>
</script>

</body>
</html>