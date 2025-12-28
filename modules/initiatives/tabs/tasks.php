<?php
// modules/initiatives/tabs/tasks.php

// 1. التحقق من الصلاحيات
$canManageTasks = ($isOwner || $isSuper || Auth::can('manage_initiative_tasks')) && !$isLocked;
$canEditOwnTasks = Auth::can('edit_assigned_tasks') && !$isLocked;

// 2. معالجة الإضافة (Add Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task']) && $canManageTasks) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $milestoneId = !empty($_POST['milestone_id']) ? $_POST['milestone_id'] : null;
    $assignedTo = $_POST['assigned_to'];
    $startDate = $_POST['start_date'];
    $dueDate = $_POST['due_date'];
    $weight = $_POST['weight'] ?: 1;
    $priority = $_POST['priority_id'];
    
    if ($startDate < $init['start_date'] || $dueDate > $init['due_date']) {
        echo "<script>Swal.fire('Date Error', 'Task dates must be within Initiative timeline.', 'error');</script>";
    } else {
        $stmt = $db->prepare("
            INSERT INTO initiative_tasks (initiative_id, milestone_id, title, description, assigned_to, start_date, due_date, status_id, priority_id, weight, progress, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 0, NOW())
        ");
        $stmt->execute([$id, $milestoneId, $title, $desc, $assignedTo, $startDate, $dueDate, $priority, $weight]);
        
        // تحديث النسبة العامة (شامل المشاريع والمهام)
        if (function_exists('updateInitiativeProgress')) {
            updateInitiativeProgress($id);
        }

        echo "<script>window.location.href='view.php?id=$id&tab=tasks&msg=task_added';</script>";
    }
}

// 3. معالجة التحديث (Update Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task'])) {
    $taskId = $_POST['task_id'];
    $newStatus = $_POST['status_id'];
    $newProgress = $_POST['progress'];
    
    $taskOwner = $db->query("SELECT assigned_to FROM initiative_tasks WHERE id=$taskId")->fetchColumn();
    $isMyTask = ($taskOwner == $_SESSION['user_id']);
    
    if ($canManageTasks || ($canEditOwnTasks && $isMyTask)) {
        $db->prepare("UPDATE initiative_tasks SET status_id = ?, progress = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$newStatus, $newProgress, $taskId]);
        
        // تحديث النسبة العامة والحالة
        if (function_exists('updateInitiativeProgress')) {
            updateInitiativeProgress($id);
        }

        // تحديث الميل ستون المرتبط
        $mId = $db->query("SELECT milestone_id FROM initiative_tasks WHERE id=$taskId")->fetchColumn();
        if ($mId) {
             // منطق بسيط لتحديث الميل ستون
             $msTasks = $db->query("SELECT status_id FROM initiative_tasks WHERE milestone_id=$mId AND is_deleted=0")->fetchAll(PDO::FETCH_COLUMN);
             $isComplete = true;
             foreach($msTasks as $st) { if($st != 3) $isComplete = false; } // 3 = Completed
             
             if ($isComplete) {
                 $db->prepare("UPDATE initiative_milestones SET status_id = 3, progress = 100 WHERE id = ?")->execute([$mId]);
             }
        }

        echo "<script>window.location.href='view.php?id=$id&tab=tasks&msg=task_updated';</script>";
    }
}

// 4. حذف مهمة
if (isset($_GET['delete_task']) && $canManageTasks) {
    $tId = $_GET['delete_task'];
    $db->prepare("UPDATE initiative_tasks SET is_deleted=1 WHERE id=?")->execute([$tId]);
    
    if (function_exists('updateInitiativeProgress')) {
        updateInitiativeProgress($id);
    }
    
    echo "<script>window.location.href='view.php?id=$id&tab=tasks&msg=task_deleted';</script>";
}

// 5. جلب البيانات
$tasksList = $db->query("
    SELECT t.*, m.name as milestone_name, u.full_name_en as assignee_name, u.avatar as assignee_avatar, 
           ts.name as status_name, tp.label as priority_label
    FROM initiative_tasks t
    LEFT JOIN initiative_milestones m ON m.id = t.milestone_id
    LEFT JOIN users u ON u.id = t.assigned_to
    LEFT JOIN task_statuses ts ON ts.id = t.status_id
    LEFT JOIN task_priorities tp ON tp.id = t.priority_id
    WHERE t.initiative_id = $id AND t.is_deleted = 0
    ORDER BY t.due_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($canManageTasks) {
    $milestones = $db->query("SELECT id, name FROM initiative_milestones WHERE initiative_id=$id AND is_deleted=0")->fetchAll();
    $team = $db->query("SELECT u.id, u.full_name_en FROM initiative_team it JOIN users u ON u.id = it.user_id WHERE it.initiative_id=$id AND it.is_active=1")->fetchAll();
    $priorities = $db->query("SELECT * FROM task_priorities")->fetchAll();
}
$taskStatuses = $db->query("SELECT * FROM task_statuses")->fetchAll();
?>

<style>
    /* --- تصميم القائمة والكروت (محدث) --- */
    .task-container { display: flex; flex-direction: column; gap: 15px; margin-top: 25px; }
    
    .task-row { 
        background: #fff; border: 1px solid #f0f2f5; border-radius: 16px; padding: 20px; 
        display: flex; align-items: center; justify-content: space-between; 
        transition: all 0.2s ease; position: relative; overflow: hidden;
        box-shadow: 0 2px 5px rgba(0,0,0,0.02);
    }
    .task-row:hover { transform: translateY(-2px); border-color: #ff8c00; box-shadow: 0 8px 20px rgba(0,0,0,0.06); }
    
    /* Priority Bar */
    .task-row::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; }
    .prio-1::before { background: #2ecc71; } /* Low */
    .prio-2::before { background: #3498db; } /* Medium */
    .prio-3::before { background: #e67e22; } /* High */
    .prio-4::before { background: #e74c3c; } /* Critical */

    .t-info { flex: 2; padding-left: 10px; }
    .t-title { font-weight: 800; color: #2d3436; font-size: 1.05rem; margin-bottom: 8px; }
    .t-meta { font-size: 0.8rem; color: #95a5a6; display: flex; gap: 15px; align-items: center; }
    .ms-badge { background: #f8f9fa; padding: 4px 10px; border-radius: 6px; font-weight: 700; color: #7f8c8d; border: 1px solid #eee; display: flex; align-items: center; gap: 5px; }

    .t-user { flex: 1; display: flex; align-items: center; gap: 10px; }
    .u-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .u-name { font-size: 0.85rem; font-weight: 600; color: #555; }

    .t-progress { width: 140px; margin-right: 25px; text-align: center; }
    .tp-header { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 5px; color: #777; font-weight: 600; }
    .tp-bar-bg { height: 8px; background: #eee; border-radius: 4px; overflow: hidden; }
    .tp-fill { height: 100%; background: linear-gradient(90deg, #2ecc71, #27ae60); border-radius: 4px; transition: width 0.5s; }

    .t-status { width: 110px; text-align: center; }
    .st-pill { padding: 6px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: #eee; color: #777; display: inline-block; width: 100%; box-sizing: border-box; }
    .ts-1 { background: #f0f2f5; color: #7f8c8d; } 
    .ts-2 { background: #e3f2fd; color: #3498db; } 
    .ts-3 { background: #e8f5e9; color: #27ae60; } 
    .ts-5 { background: #ffebee; color: #e74c3c; } 

    .t-actions { display: flex; gap: 10px; margin-left: 15px; }
    .act-btn { width: 35px; height: 35px; border-radius: 10px; background: #f9f9f9; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; color: #aaa; cursor: pointer; transition: 0.2s; }
    .act-btn:hover { border-color: #ff8c00; color: #ff8c00; background: #fff; transform: scale(1.05); }
    .act-btn.del:hover { border-color: #e74c3c; color: #e74c3c; background: #fff; }
    
    /* --- مودال بتصميم زجاجي --- */
    .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); justify-content: center; align-items: center; animation: fadeIn 0.3s; }
    .modal-box { background: #fff; width: 650px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); overflow: hidden; transform: translateY(20px); animation: slideUp 0.3s forwards; }
    
    /* حقول المودال */
    .f-row { margin-bottom: 20px; }
    .f-lbl { display: block; font-weight: 700; color: #2c3e50; margin-bottom: 8px; font-size: 0.9rem; }
    .f-inp { width: 100%; padding: 12px 15px; border-radius: 12px; border: 2px solid #f0f2f5; background: #fff; font-family: inherit; transition: 0.2s; box-sizing: border-box; }
    .f-inp:focus { border-color: #ff8c00; outline: none; box-shadow: 0 0 0 4px rgba(255,140,0,0.1); }
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h3 style="margin:0; color:#2c3e50; font-size:1.3rem;">Execution Tasks</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Manage daily tasks and track progress.</p>
        </div>
        <?php if($canManageTasks): ?>
            <button onclick="openTaskModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-plus"></i> New Task
            </button>
        <?php endif; ?>
    </div>

    <div class="task-container">
        <?php if(empty($tasksList)): ?>
            <div style="text-align:center; padding:60px; border:2px dashed #eee; border-radius:16px; color:#ccc;">
                <i class="fa-solid fa-list-check" style="font-size:3.5rem; margin-bottom:15px; opacity:0.5;"></i>
                <p style="font-size:1.1rem;">No tasks created yet.</p>
            </div>
        <?php else: ?>
            <?php foreach($tasksList as $t): 
                $isMyTask = ($t['assigned_to'] == $_SESSION['user_id']);
                $canUpdate = $canManageTasks || ($canEditOwnTasks && $isMyTask);
                $av = $t['assignee_avatar'] ? '../../assets/uploads/avatars/'.$t['assignee_avatar'] : '../../assets/uploads/avatars/default-profile.png';
            ?>
            <div class="task-row prio-<?= $t['priority_id'] ?>">
                <div class="t-info">
                    <div class="t-title"><?= htmlspecialchars($t['title']) ?></div>
                    <div class="t-meta">
                        <?php if($t['milestone_name']): ?>
                            <span class="ms-badge"><i class="fa-solid fa-flag" style="color:#ff8c00;"></i> <?= htmlspecialchars($t['milestone_name']) ?></span>
                        <?php else: ?>
                            <span class="ms-badge">General</span>
                        <?php endif; ?>
                        <span><i class="fa-regular fa-calendar" style="color:#3498db;"></i> <?= date('d M', strtotime($t['due_date'])) ?></span>
                        <span title="Weight (Impact on Initiative Progress)">Weight: <strong><?= $t['weight'] ?></strong></span>
                    </div>
                </div>

                <div class="t-user">
                    <img src="<?= $av ?>" class="u-avatar" title="<?= htmlspecialchars($t['assignee_name']) ?>">
                    <div class="u-name"><?= htmlspecialchars($t['assignee_name']) ?></div>
                </div>

                <div class="t-progress">
                    <div class="tp-header">
                        <span>Progress</span>
                        <span><?= $t['progress'] ?>%</span>
                    </div>
                    <div class="tp-bar-bg"><div class="tp-fill" style="width:<?= $t['progress'] ?>%"></div></div>
                </div>

                <div class="t-status">
                    <span class="st-pill ts-<?= $t['status_id'] ?>"><?= $t['status_name'] ?></span>
                </div>

                <div class="t-actions">
                    <?php if($canUpdate): ?>
                        <div class="act-btn" onclick='openEditTask(<?= json_encode($t) ?>)' title="Update Progress">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($canManageTasks): ?>
                        <a href="view.php?id=<?= $id ?>&tab=tasks&delete_task=<?= $t['id'] ?>" class="act-btn del" onclick="return confirm('Delete task?')" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if($canManageTasks): ?>
<div id="addTaskModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header" style="padding:25px 30px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; color:#2c3e50; font-size:1.3rem;">Add New Task</h3>
            <span onclick="closeTaskModal()" style="font-size:1.5rem; cursor:pointer; color:#ccc;">&times;</span>
        </div>
        
        <form method="POST" style="padding:30px;">
            <input type="hidden" name="add_task" value="1">
            
            <div class="f-row">
                <label class="f-lbl">Task Title <span style="color:red">*</span></label>
                <input type="text" name="title" class="f-inp" required placeholder="Enter task title...">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="f-row">
                    <label class="f-lbl">Link to Milestone</label>
                    <select name="milestone_id" class="f-inp">
                        <option value="">-- General (No Milestone) --</option>
                        <?php foreach($milestones as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="f-row">
                    <label class="f-lbl">Assign To <span style="color:red">*</span></label>
                    <select name="assigned_to" class="f-inp" required>
                        <?php foreach($team as $tm): ?>
                            <option value="<?= $tm['id'] ?>"><?= htmlspecialchars($tm['full_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="f-row">
                    <label class="f-lbl">Start Date</label>
                    <input type="date" name="start_date" class="f-inp" required min="<?= $init['start_date'] ?>" max="<?= $init['due_date'] ?>">
                </div>
                <div class="f-row">
                    <label class="f-lbl">Due Date</label>
                    <input type="date" name="due_date" class="f-inp" required min="<?= $init['start_date'] ?>" max="<?= $init['due_date'] ?>">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="f-row">
                    <label class="f-lbl">Priority</label>
                    <select name="priority_id" class="f-inp">
                        <?php foreach($priorities as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= $p['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="f-row">
                    <label class="f-lbl">Weight (1-10)</label>
                    <input type="number" name="weight" class="f-inp" min="1" max="10" value="1" title="Higher weight affects overall progress more">
                </div>
            </div>

            <div class="f-row" style="margin-bottom:0;">
                <label class="f-lbl">Description</label>
                <textarea name="description" class="f-inp" style="height:100px; resize:vertical;"></textarea>
            </div>

            <div style="margin-top:30px; text-align:right; border-top:1px solid #eee; padding-top:20px;">
                <button type="button" class="btn-cancel" style="margin-right:10px; background:#fff; border:1px solid #ddd; padding:10px 25px; border-radius:10px; cursor:pointer;" onclick="closeTaskModal()">Cancel</button>
                <button type="submit" class="btn-submit" style="background:#ff8c00; color:#fff; border:none; padding:10px 30px; border-radius:10px; cursor:pointer; font-weight:bold;">Create Task</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="editTaskModal" class="modal-overlay">
    <div class="modal-box" style="width:450px;">
        <div class="modal-header" style="padding:25px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
            <h3 style="margin:0; color:#2c3e50;">Update Task Status</h3>
            <span onclick="document.getElementById('editTaskModal').style.display='none'" style="cursor:pointer; font-size:1.5rem;">&times;</span>
        </div>
        <form method="POST" style="padding:25px;">
            <input type="hidden" name="update_task" value="1">
            <input type="hidden" name="task_id" id="edit_task_id">
            
            <div class="f-row">
                <label class="f-lbl">Status</label>
                <select name="status_id" id="edit_status_id" class="f-inp">
                    <?php foreach($taskStatuses as $ts): ?>
                        <option value="<?= $ts['id'] ?>"><?= $ts['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f-row">
                <label class="f-lbl">Progress %</label>
                <div style="display:flex; align-items:center; gap:15px;">
                    <input type="range" name="progress" id="edit_progress" class="f-inp" style="flex:1; padding:0;" min="0" max="100">
                    <span id="prog_val" style="font-weight:800; color:#ff8c00; font-size:1.2rem; min-width:50px; text-align:right;">0%</span>
                </div>
            </div>

            <div style="margin-top:30px; text-align:right;">
                <button type="button" class="btn-cancel" style="margin-right:10px; background:#fff; border:1px solid #ddd; padding:10px 25px; border-radius:10px; cursor:pointer;" onclick="document.getElementById('editTaskModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn-submit" style="background:#3498db; color:#fff; border:none; padding:10px 30px; border-radius:10px; cursor:pointer; font-weight:bold;">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTaskModal() { document.getElementById('addTaskModal').style.display = 'flex'; }
    function closeTaskModal() { document.getElementById('addTaskModal').style.display = 'none'; }
    
    function openEditTask(task) {
        document.getElementById('editTaskModal').style.display = 'flex';
        document.getElementById('edit_task_id').value = task.id;
        document.getElementById('edit_status_id').value = task.status_id;
        
        const progInput = document.getElementById('edit_progress');
        const progVal = document.getElementById('prog_val');
        const statusSelect = document.getElementById('edit_status_id');
        
        progInput.value = task.progress;
        progVal.innerText = task.progress + '%';

        // المنطق الذكي لتحديث الحالة عند تحريك الـ Slider
        progInput.oninput = function() {
            let val = parseInt(this.value);
            progVal.innerText = val + '%';
            
            let currentStatus = parseInt(statusSelect.value);
            // 4=On Hold, 5=Cancelled (تأكد من الـ IDs في قاعدتك)
            if (currentStatus !== 4 && currentStatus !== 5) {
                if (val === 0) statusSelect.value = 1; // Pending
                else if (val === 100) statusSelect.value = 3; // Completed
                else statusSelect.value = 2; // In Progress
            }
        };
        
        // المنطق العكسي: عند تغيير الحالة يدوياً، نحدث النسبة
        statusSelect.onchange = function() {
            let val = parseInt(this.value);
            if (val === 3) { // Completed
                progInput.value = 100;
                progVal.innerText = '100%';
            } else if (val === 1) { // Pending
                progInput.value = 0;
                progVal.innerText = '0%';
            }
        };
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addTaskModal')) closeTaskModal();
        if (event.target == document.getElementById('editTaskModal')) document.getElementById('editTaskModal').style.display='none';
    }
</script>