<?php
// modules/initiatives/tabs/milestones.php

// 1. التحقق من الصلاحيات
// السماح للمالك، السوبر أدمن، أو من يملك صلاحية إدارة المعالم، بشرط أن المشروع غير مغلق
$canManageMilestones = ($isOwner || $isSuper || Auth::can('manage_initiative_milestones')) && !$isDraft; 
// ملاحظة: عادة يسمح بالإضافة في الـ Draft، ولكن حسب طلبك السابق "تواريخ ضمن وقت المبادرة"، سنفترض السماح دائماً ما لم يكن locked
// للتسهيل: سنسمح بالإضافة دائماً للمالك والأدمن
$canManageMilestones = ($isOwner || $isSuper || Auth::can('manage_initiative_milestones'));


// 2. معالجة الإضافة (Add Milestone)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_milestone']) && $canManageMilestones) {
    $mName = $_POST['name'];
    $mDesc = $_POST['description'];
    $mStart = $_POST['start_date'];
    $mDue = $_POST['due_date'];
    $mCost = $_POST['cost_amount'] ?: 0;
    
    // التحقق من النطاق الزمني
    if ($mStart < $init['start_date'] || $mDue > $init['due_date']) {
        echo "<script>Swal.fire('Date Error', 'Milestone dates must be within Initiative dates ({$init['start_date']} to {$init['due_date']})', 'error');</script>";
    } else {
        $stmt = $db->prepare("
            INSERT INTO initiative_milestones (initiative_id, name, description, start_date, due_date, cost_amount, status_id, progress, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, 0, NOW())
        ");
        $stmt->execute([$id, $mName, $mDesc, $mStart, $mDue, $mCost]);
        
        echo "<script>window.location.href='view.php?id=$id&tab=milestones&msg=milestone_added';</script>";
    }
}

// 3. معالجة الحذف
if (isset($_GET['delete_milestone']) && $canManageMilestones) {
    $mId = $_GET['delete_milestone'];
    $db->prepare("UPDATE initiative_milestones SET is_deleted=1 WHERE id=?")->execute([$mId]);
    echo "<script>window.location.href='view.php?id=$id&tab=milestones&msg=milestone_deleted';</script>";
}

// 4. دالة تحديث الحالة (للعرض)
function refreshMilestoneStatus($mId, $db) {
    // منطق تحديث الحالة (كما هو في الكود السابق)
    $tasks = $db->prepare("SELECT status_id, progress FROM initiative_tasks WHERE milestone_id = ? AND is_deleted = 0");
    $tasks->execute([$mId]);
    $taskList = $tasks->fetchAll(PDO::FETCH_ASSOC);
    
    $totalTasks = count($taskList);
    $avgProgress = 0;
    $isAllCompleted = ($totalTasks > 0);
    $hasInProgress = false;

    if ($totalTasks > 0) {
        $sumProg = 0;
        foreach ($taskList as $t) {
            $sumProg += $t['progress'];
            if ($t['status_id'] != 3) $isAllCompleted = false; 
            if ($t['status_id'] == 2) $hasInProgress = true; 
        }
        $avgProgress = round($sumProg / $totalTasks);
    } else {
        $isAllCompleted = false;
    }

    $m = $db->query("SELECT * FROM initiative_milestones WHERE id=$mId")->fetch(PDO::FETCH_ASSOC);
    $newStatus = $m['status_id'];
    $today = date('Y-m-d');

    if ($isAllCompleted) $newStatus = 3; 
    elseif ($m['due_date'] < $today && $avgProgress < 100) $newStatus = 4; 
    elseif ($hasInProgress || $avgProgress > 0) $newStatus = 2; 
    elseif ($m['start_date'] <= $today) $newStatus = 2; 
    else $newStatus = 1; 
    
    if ($newStatus != $m['status_id'] || $avgProgress != $m['progress']) {
        $db->prepare("UPDATE initiative_milestones SET status_id = ?, progress = ? WHERE id = ?")
           ->execute([$newStatus, $avgProgress, $mId]);
    }
}

// 5. جلب المعالم
$allMsIds = $db->query("SELECT id FROM initiative_milestones WHERE initiative_id=$id AND is_deleted=0")->fetchAll(PDO::FETCH_COLUMN);
foreach($allMsIds as $mid) refreshMilestoneStatus($mid, $db);

$milestones = $db->prepare("
    SELECT m.*, s.name as status_name, 
           (SELECT COUNT(*) FROM initiative_tasks t WHERE t.milestone_id = m.id AND t.is_deleted=0) as task_count
    FROM initiative_milestones m
    JOIN milestone_statuses s ON s.id = m.status_id
    WHERE m.initiative_id = ? AND m.is_deleted = 0
    ORDER BY m.start_date ASC
");
$milestones->execute([$id]);
$milestoneList = $milestones->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* --- تصميم التايم لاين --- */
    .timeline-container { position: relative; padding: 20px 0 20px 20px; }
    .timeline-line { 
        position: absolute; left: 35px; top: 10px; bottom: 10px; width: 3px; 
        background: #f0f0f0; z-index: 0; border-radius: 2px;
    }
    
    .ms-card { 
        position: relative; margin-left: 50px; background: #fff; border-radius: 16px; 
        padding: 25px; margin-bottom: 25px; border: 1px solid #f0f2f5; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.01); transition: all 0.3s ease;
    }
    .ms-card:hover { transform: translateY(-3px); border-color: #ff8c00; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
    
    .ms-dot { 
        position: absolute; left: -59px; top: 25px; width: 18px; height: 18px; 
        border-radius: 50%; background: #fff; border: 4px solid #ddd; z-index: 1;
        transition: 0.3s;
    }
    .ms-dot.completed { border-color: #2ecc71; background: #2ecc71; box-shadow: 0 0 0 4px rgba(46, 204, 113, 0.2); }
    .ms-dot.delayed { border-color: #e74c3c; background: #e74c3c; box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.2); }
    .ms-dot.inprogress { border-color: #3498db; background: #3498db; box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.2); }

    .ms-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
    .ms-title { font-size: 1.2rem; font-weight: 800; color: #2d3436; margin: 0; }
    
    .ms-status-badge { 
        font-size: 0.7rem; padding: 4px 10px; border-radius: 12px; 
        text-transform: uppercase; font-weight: 800; margin-left: 10px;
    }
    .st-1 { background: #f0f2f5; color: #7f8c8d; } /* Upcoming */
    .st-2 { background: #e3f2fd; color: #3498db; } /* In Progress */
    .st-3 { background: #e8f5e9; color: #27ae60; } /* Completed */
    .st-4 { background: #ffebee; color: #e74c3c; } /* Delayed */

    .ms-dates { 
        background: #fdfdfd; border: 1px solid #f0f0f0; padding: 8px 12px; border-radius: 8px;
        font-size: 0.85rem; color: #555; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 15px;
    }
    .ms-dates i { color: #ff8c00; }

    .ms-desc { color: #636e72; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px; }

    /* Progress Bar */
    .ms-progress-wrapper { display: flex; align-items: center; gap: 15px; }
    .prog-bar-bg { flex: 1; height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden; }
    .prog-bar-fill { height: 100%; background: linear-gradient(90deg, #3498db, #2980b9); border-radius: 4px; transition: width 0.6s ease; }
    .prog-text { font-size: 0.9rem; font-weight: 700; color: #2d3436; min-width: 40px; text-align: right; }
    
    /* Stats */
    .ms-stats { 
        display: flex; gap: 20px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; 
        font-size: 0.85rem; color: #95a5a6; font-weight: 600;
    }
    .ms-stats span { display: flex; align-items: center; gap: 6px; }

    /* --- تصميم المودال الحديث (نفس Team) --- */
    .modal-overlay {
        display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);
        justify-content: center; align-items: center; animation: fadeIn 0.3s;
    }
    .modal-box {
        background-color: #fff; width: 100%; max-width: 600px;
        border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        overflow: hidden; transform: translateY(20px); animation: slideUp 0.3s forwards;
    }
    .modal-header {
        background: #fff; padding: 25px 30px; border-bottom: 1px solid #f0f0f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-title { font-size: 1.3rem; font-weight: 800; color: #2d3436; margin: 0; display: flex; align-items: center; gap: 10px; }
    .modal-close { font-size: 1.5rem; color: #cbd5e0; cursor: pointer; transition: 0.2s; }
    .modal-close:hover { color: #e74c3c; transform: rotate(90deg); }

    .modal-body { padding: 30px; background: #fcfcfc; }

    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-row { margin-bottom: 20px; }
    .form-lbl { display: block; font-weight: 700; color: #4a5568; margin-bottom: 8px; font-size: 0.9rem; }
    .form-input {
        width: 100%; padding: 12px 15px; border-radius: 12px; border: 2px solid #edf2f7;
        background: #fff; font-size: 0.95rem; color: #2d3436; transition: 0.2s;
        box-sizing: border-box; font-family: inherit;
    }
    .form-input:focus { border-color: #ff8c00; outline: none; box-shadow: 0 0 0 4px rgba(255,140,0,0.1); }
    textarea.form-input { resize: vertical; min-height: 100px; }

    .modal-footer {
        padding: 20px 30px; background: #fff; border-top: 1px solid #f0f0f0;
        display: flex; justify-content: flex-end; gap: 12px;
    }
    .btn-cancel {
        background: #fff; border: 2px solid #edf2f7; color: #718096; padding: 10px 24px;
        border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;
    }
    .btn-cancel:hover { background: #edf2f7; color: #2d3436; }
    .btn-save {
        background: linear-gradient(135deg, #ff8c00, #e67e00); color: #fff; border: none;
        padding: 10px 30px; border-radius: 12px; font-weight: 700; cursor: pointer;
        box-shadow: 0 4px 12px rgba(255,140,0,0.3); transition: 0.2s;
    }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255,140,0,0.4); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Project Timeline</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Key milestones and their progress.</p>
        </div>
        <?php if($canManageMilestones): ?>
            <button onclick="openMsModal()" class="btn-primary" style="padding: 12px 25px; border-radius: 30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-flag"></i> Add Milestone
            </button>
        <?php endif; ?>
    </div>

    <div class="timeline-container">
        <div class="timeline-line"></div>
        
        <?php if(empty($milestoneList)): ?>
            <div style="margin-left:50px; padding: 40px; border: 2px dashed #eee; border-radius: 16px; text-align: center;">
                <i class="fa-solid fa-map-location-dot" style="font-size: 3rem; color: #eee; margin-bottom: 15px;"></i>
                <p style="color: #aaa; margin: 0;">No milestones defined yet.</p>
            </div>
        <?php else: ?>
            <?php foreach($milestoneList as $ms): 
                $dotClass = '';
                $statusColor = '#95a5a6';
                if($ms['status_id'] == 3) { $dotClass = 'completed'; $statusColor = '#2ecc71'; }
                elseif($ms['status_id'] == 4) { $dotClass = 'delayed'; $statusColor = '#e74c3c'; }
                elseif($ms['status_id'] == 2) { $dotClass = 'inprogress'; $statusColor = '#3498db'; }
            ?>
            <div class="ms-card">
                <div class="ms-dot <?= $dotClass ?>"></div>
                
                <div class="ms-header">
                    <div class="ms-title">
                        <?= htmlspecialchars($ms['name']) ?>
                        <span class="ms-status-badge st-<?= $ms['status_id'] ?>"><?= $ms['status_name'] ?></span>
                    </div>
                    <?php if($canManageMilestones): ?>
                        <a href="view.php?id=<?= $id ?>&tab=milestones&delete_milestone=<?= $ms['id'] ?>" onclick="return confirm('Delete milestone?')" style="color:#e74c3c; opacity:0.6; transition:0.2s;" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="ms-dates">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span><?= date('d M, Y', strtotime($ms['start_date'])) ?></span>
                    <i class="fa-solid fa-arrow-right" style="font-size:0.7rem; color:#ccc;"></i>
                    <span><?= date('d M, Y', strtotime($ms['due_date'])) ?></span>
                </div>

                <div class="ms-desc"><?= htmlspecialchars($ms['description']) ?></div>

                <div class="ms-progress-wrapper">
                    <div class="prog-bar-bg">
                        <div class="prog-bar-fill" style="width: <?= $ms['progress'] ?>%; background: <?= $statusColor ?>;"></div>
                    </div>
                    <div class="prog-text"><?= $ms['progress'] ?>%</div>
                </div>
                
                <div class="ms-stats">
                    <span title="Linked Tasks"><i class="fa-solid fa-list-check"></i> <?= $ms['task_count'] ?> Tasks</span>
                    <?php if($ms['cost_amount'] > 0): ?>
                        <span title="Estimated Cost"><i class="fa-solid fa-coins"></i> <?= number_format($ms['cost_amount']) ?> SAR</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if($canManageMilestones): ?>
<div id="addMsModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">
                <div style="width:40px; height:40px; background:#fff3e0; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#ff8c00;">
                    <i class="fa-solid fa-flag"></i>
                </div>
                New Milestone
            </h3>
            <div class="modal-close" onclick="closeMsModal()">&times;</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="add_milestone" value="1">
            <div class="modal-body">
                
                <div class="form-row">
                    <label class="form-lbl">Milestone Name <span style="color:#e74c3c">*</span></label>
                    <input type="text" name="name" class="form-input" required placeholder="e.g. Phase 1 Completion">
                </div>

                <div class="form-grid-2">
                    <div class="form-row">
                        <label class="form-lbl">Start Date <span style="color:#e74c3c">*</span></label>
                        <input type="date" name="start_date" class="form-input" required min="<?= $init['start_date'] ?>" max="<?= $init['due_date'] ?>">
                    </div>
                    <div class="form-row">
                        <label class="form-lbl">Due Date <span style="color:#e74c3c">*</span></label>
                        <input type="date" name="due_date" class="form-input" required min="<?= $init['start_date'] ?>" max="<?= $init['due_date'] ?>">
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-lbl">Estimated Budget (SAR)</label>
                    <input type="number" name="cost_amount" class="form-input" placeholder="0.00" step="0.01">
                </div>

                <div class="form-row" style="margin-bottom:0;">
                    <label class="form-lbl">Description</label>
                    <textarea name="description" class="form-input" placeholder="What will be achieved in this milestone?"></textarea>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeMsModal()">Cancel</button>
                <button type="submit" class="btn-save">Create Milestone</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMsModal() {
        document.getElementById('addMsModal').style.display = 'flex';
    }

    function closeMsModal() {
        document.getElementById('addMsModal').style.display = 'none';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('addMsModal');
        if (event.target == modal) {
            closeMsModal();
        }
    }
</script>
<?php endif; ?>