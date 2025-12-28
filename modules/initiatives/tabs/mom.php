<?php
// modules/initiatives/tabs/mom.php

// 1. الصلاحيات
$canManageMoM = ($isOwner || $isSuper || Auth::can('manage_initiative_documents')) && !$isLocked;

// 2. معالجة الإضافة (Add Meeting)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mom']) && $canManageMoM) {
    $topic = $_POST['topic'];
    $date = $_POST['meeting_date'];
    $time = $_POST['meeting_time'];
    $venue = $_POST['venue'];
    $agenda = $_POST['agenda'];
    
    // معالجة الحضور (Array -> JSON)
    $attendees = isset($_POST['attendees']) ? json_encode($_POST['attendees']) : '[]';
    
    // معالجة نقاط النقاش (Dynamic Rows -> JSON)
    $discussionPoints = [];
    if (isset($_POST['disc_point']) && is_array($_POST['disc_point'])) {
        for ($i = 0; $i < count($_POST['disc_point']); $i++) {
            if (!empty($_POST['disc_point'][$i])) {
                $discussionPoints[] = [
                    'point' => $_POST['disc_point'][$i],
                    'type'  => $_POST['disc_type'][$i] ?? 'info', // info, decision, action
                    'owner' => $_POST['disc_owner'][$i] ?? ''
                ];
            }
        }
    }
    $topicsJson = json_encode($discussionPoints);

    $stmt = $db->prepare("
        INSERT INTO meeting_minutes (
            parent_type, parent_id, topic, meeting_date, meeting_time, venue, 
            attendees, agenda, topics, prepared_by, created_at
        ) VALUES (
            'initiative', ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, NOW()
        )
    ");
    $stmt->execute([$id, $topic, $date, $time, $venue, $attendees, $agenda, $topicsJson, $_SESSION['user_id']]);
    
    echo "<script>window.location.href='view.php?id=$id&tab=mom&msg=mom_added';</script>";
}

// 3. حذف اجتماع
if (isset($_GET['delete_mom']) && $canManageMoM) {
    $mId = $_GET['delete_mom'];
    $db->prepare("DELETE FROM meeting_minutes WHERE id=?")->execute([$mId]);
    echo "<script>window.location.href='view.php?id=$id&tab=mom&msg=mom_deleted';</script>";
}

// 4. جلب البيانات
$moms = $db->prepare("
    SELECT m.*, u.full_name_en as preparer_name 
    FROM meeting_minutes m
    JOIN users u ON u.id = m.prepared_by
    WHERE m.parent_type = 'initiative' AND m.parent_id = ?
    ORDER BY m.meeting_date DESC, m.meeting_time DESC
");
$moms->execute([$id]);
$momList = $moms->fetchAll(PDO::FETCH_ASSOC);

// جلب أعضاء الفريق للمودال (للاختيار في الحضور)
if ($canManageMoM) {
    $teamMembers = $db->query("
        SELECT u.id, u.full_name_en, u.avatar 
        FROM initiative_team it 
        JOIN users u ON u.id = it.user_id 
        WHERE it.initiative_id = $id AND it.is_active = 1
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
    /* Timeline Layout */
    .mom-timeline { position: relative; padding: 20px 0; }
    .mom-timeline::before { 
        content: ''; position: absolute; left: 24px; top: 0; bottom: 0; width: 2px; 
        background: #e0e0e0; z-index: 0; 
    }

    .mom-card { 
        margin-left: 60px; background: #fff; border-radius: 16px; border: 1px solid #f0f2f5; 
        padding: 25px; margin-bottom: 30px; position: relative; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.02); transition: 0.3s;
    }
    .mom-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); border-color: #ff8c00; }

    /* Date Badge on Timeline */
    .mom-date-badge { 
        position: absolute; left: -60px; top: 25px; width: 50px; text-align: center; 
        background: #fff; border: 2px solid #ff8c00; border-radius: 12px; padding: 5px 0; z-index: 1;
    }
    .mdb-day { display: block; font-size: 1.2rem; font-weight: 800; color: #2d3436; line-height: 1; }
    .mdb-month { display: block; font-size: 0.7rem; text-transform: uppercase; color: #ff8c00; font-weight: 700; }

    .mom-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px dashed #eee; padding-bottom: 15px; margin-bottom: 15px; }
    .mom-title { margin: 0; font-size: 1.1rem; font-weight: 800; color: #2d3436; }
    .mom-meta { font-size: 0.85rem; color: #95a5a6; display: flex; gap: 15px; margin-top: 5px; }
    .mom-meta i { color: #b2bec3; margin-right: 5px; }

    /* Attendees Avatars */
    .attendee-list { display: flex; margin-left: 10px; }
    .at-avatar { 
        width: 30px; height: 30px; border-radius: 50%; border: 2px solid #fff; 
        margin-left: -10px; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .at-avatar:first-child { margin-left: 0; }

    /* Discussion Points Table */
    .disc-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9rem; }
    .disc-table th { text-align: left; color: #aaa; font-weight: 600; padding: 8px; border-bottom: 1px solid #eee; font-size: 0.8rem; }
    .disc-table td { padding: 10px 8px; border-bottom: 1px solid #f9f9f9; color: #555; vertical-align: top; }
    .disc-table tr:last-child td { border-bottom: none; }
    
    .type-badge { font-size: 0.7rem; padding: 3px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase; }
    .tb-info { background: #e3f2fd; color: #3498db; }
    .tb-decision { background: #e8f5e9; color: #27ae60; }
    .tb-action { background: #fff3e0; color: #e67e22; }

    /* Actions */
    .mom-actions { display: flex; gap: 10px; }
    .btn-icon-soft { 
        width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; 
        border-radius: 8px; background: #f8f9fa; color: #95a5a6; cursor: pointer; transition: 0.2s;
    }
    .btn-icon-soft:hover { background: #e3f2fd; color: #3498db; }
    .btn-icon-soft.del:hover { background: #ffebee; color: #e74c3c; }

    /* Modal Extras */
    .attendee-select-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; max-height: 150px; overflow-y: auto; padding: 10px; border: 1px solid #eee; border-radius: 8px; background: #fafafa; }
    .att-checkbox { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; cursor: pointer; }
    
    .dynamic-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
    .btn-add-row { background: #e3f2fd; color: #3498db; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.8rem; }
    .btn-remove-row { color: #e74c3c; cursor: pointer; font-size: 1.1rem; }
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Minutes of Meetings (MoM)</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Document decisions and track meeting outcomes.</p>
        </div>
        <?php if($canManageMoM): ?>
            <button onclick="openMomModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-gavel"></i> Add MoM
            </button>
        <?php endif; ?>
    </div>

    <div class="mom-timeline">
        <?php if(empty($momList)): ?>
            <div style="margin-left:60px; padding:40px; text-align:center; border:2px dashed #eee; border-radius:12px; color:#ccc;">
                <i class="fa-regular fa-calendar-xmark" style="font-size:3rem; margin-bottom:10px;"></i>
                <p>No meetings recorded yet.</p>
            </div>
        <?php else: ?>
            <?php foreach($momList as $mom): 
                $dateObj = new DateTime($mom['meeting_date']);
                $attendeeIds = json_decode($mom['attendees'], true) ?: [];
                $topics = json_decode($mom['topics'], true) ?: [];
            ?>
            <div class="mom-card">
                <div class="mom-date-badge">
                    <span class="mdb-day"><?= $dateObj->format('d') ?></span>
                    <span class="mdb-month"><?= $dateObj->format('M') ?></span>
                </div>

                <div class="mom-header">
                    <div>
                        <div class="mom-title"><?= htmlspecialchars($mom['topic']) ?></div>
                        <div class="mom-meta">
                            <span><i class="fa-regular fa-clock"></i> <?= date('h:i A', strtotime($mom['meeting_time'])) ?></span>
                            <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($mom['venue']) ?></span>
                            <span><i class="fa-solid fa-user-pen"></i> <?= htmlspecialchars($mom['preparer_name']) ?></span>
                        </div>
                    </div>
                    <div class="mom-actions">
                        <button class="btn-icon-soft" title="Print/View PDF" onclick="window.print()">
                            <i class="fa-solid fa-print"></i>
                        </button>
                        <?php if($canManageMoM): ?>
                            <a href="view.php?id=<?= $id ?>&tab=mom&delete_mom=<?= $mom['id'] ?>" class="btn-icon-soft del" onclick="return confirm('Delete this record?')" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(!empty($attendeeIds)): ?>
                <div style="margin-bottom:15px; display:flex; align-items:center; gap:10px;">
                    <span style="font-size:0.8rem; font-weight:700; color:#aaa;">ATTENDEES:</span>
                    <div class="attendee-list">
                        <?php foreach($attendeeIds as $uid): 
                             // Fetch avatar simply (In a real app, optimize this to avoid N+1 query)
                             $u = $db->query("SELECT avatar, full_name_en FROM users WHERE id=$uid")->fetch();
                             $av = $u['avatar'] ? '../../assets/uploads/avatars/'.$u['avatar'] : '../../assets/uploads/avatars/default-profile.png';
                        ?>
                            <img src="<?= $av ?>" class="at-avatar" title="<?= htmlspecialchars($u['full_name_en']) ?>">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($mom['agenda'])): ?>
                    <div style="background:#f9f9f9; padding:15px; border-radius:8px; font-size:0.9rem; color:#555; margin-bottom:15px;">
                        <strong>Agenda:</strong> <?= nl2br(htmlspecialchars($mom['agenda'])) ?>
                    </div>
                <?php endif; ?>

                <?php if(!empty($topics)): ?>
                <table class="disc-table">
                    <thead>
                        <tr>
                            <th style="width:60%;">Discussion / Decision</th>
                            <th style="width:15%;">Type</th>
                            <th style="width:25%;">Action Owner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($topics as $t): 
                            $typeClass = 'tb-info';
                            if(($t['type']??'') == 'decision') $typeClass = 'tb-decision';
                            if(($t['type']??'') == 'action') $typeClass = 'tb-action';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($t['point'] ?? '') ?></td>
                            <td><span class="type-badge <?= $typeClass ?>"><?= htmlspecialchars($t['type'] ?? 'Info') ?></span></td>
                            <td><?= htmlspecialchars($t['owner'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php if($canManageMoM): ?>
<div id="addMomModal" class="modal-overlay">
    <div class="modal-box" style="width:700px;">
        <div class="modal-header" style="padding:20px 25px; border-bottom:1px solid #eee; display:flex; justify-content:space-between;">
            <h3 style="margin:0; color:#2c3e50;">Record Meeting Minutes</h3>
            <span onclick="closeMomModal()" style="font-size:1.5rem; cursor:pointer;">&times;</span>
        </div>
        
        <form method="POST" style="padding:25px; max-height:80vh; overflow-y:auto;">
            <input type="hidden" name="add_mom" value="1">
            
            <div class="form-row">
                <label class="form-lbl">Meeting Topic <span style="color:red">*</span></label>
                <input type="text" name="topic" class="form-input" required placeholder="e.g. Kickoff Meeting">
            </div>

            <div class="form-grid-2">
                <div>
                    <label class="form-lbl">Date <span style="color:red">*</span></label>
                    <input type="date" name="meeting_date" class="form-input" required value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="form-lbl">Time</label>
                    <input type="time" name="meeting_time" class="form-input" value="<?= date('H:i') ?>">
                </div>
            </div>

            <div class="form-row" style="margin-top:15px;">
                <label class="form-lbl">Venue / Location</label>
                <input type="text" name="venue" class="form-input" placeholder="e.g. Conference Room A or Zoom Link">
            </div>

            <div class="form-row">
                <label class="form-lbl">Attendees (Team Members)</label>
                <div class="attendee-select-grid">
                    <?php foreach($teamMembers as $tm): 
                         $av = $tm['avatar'] ? '../../assets/uploads/avatars/'.$tm['avatar'] : '../../assets/uploads/avatars/default-profile.png';
                    ?>
                        <label class="att-checkbox">
                            <input type="checkbox" name="attendees[]" value="<?= $tm['id'] ?>">
                            <img src="<?= $av ?>" style="width:24px; height:24px; border-radius:50%;">
                            <?= htmlspecialchars(explode(' ', $tm['full_name_en'])[0]) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-row">
                <label class="form-lbl">Agenda</label>
                <textarea name="agenda" class="form-input" style="height:60px;" placeholder="Main objectives..."></textarea>
            </div>

            <div style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
                <label class="form-lbl" style="margin-bottom:10px;">Discussion Points & Decisions</label>
                <div id="topics_container">
                    <div class="dynamic-row">
                        <input type="text" name="disc_point[]" class="form-input" placeholder="Point / Decision" style="flex:3;">
                        <select name="disc_type[]" class="form-input" style="flex:1;">
                            <option value="info">Info</option>
                            <option value="decision">Decision</option>
                            <option value="action">Action Item</option>
                        </select>
                        <input type="text" name="disc_owner[]" class="form-input" placeholder="Owner" style="flex:1;">
                        <i class="fa-solid fa-trash btn-remove-row" onclick="removeRow(this)"></i>
                    </div>
                </div>
                <button type="button" class="btn-add-row" onclick="addTopicRow()">
                    <i class="fa-solid fa-plus"></i> Add Another Point
                </button>
            </div>

            <div class="modal-footer" style="margin-top:25px; padding-top:20px; border-top:1px solid #eee; text-align:right;">
                <button type="button" class="btn-cancel" onclick="closeMomModal()" style="margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-save">Save Minutes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openMomModal() { document.getElementById('addMomModal').style.display = 'flex'; }
    function closeMomModal() { document.getElementById('addMomModal').style.display = 'none'; }

    function addTopicRow() {
        const container = document.getElementById('topics_container');
        const div = document.createElement('div');
        div.className = 'dynamic-row';
        div.innerHTML = `
            <input type="text" name="disc_point[]" class="form-input" placeholder="Point / Decision" style="flex:3;">
            <select name="disc_type[]" class="form-input" style="flex:1;">
                <option value="info">Info</option>
                <option value="decision">Decision</option>
                <option value="action">Action Item</option>
            </select>
            <input type="text" name="disc_owner[]" class="form-input" placeholder="Owner" style="flex:1;">
            <i class="fa-solid fa-trash btn-remove-row" onclick="removeRow(this)"></i>
        `;
        container.appendChild(div);
    }

    function removeRow(btn) {
        btn.parentElement.remove();
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addMomModal')) closeMomModal();
    }
</script>
<?php endif; ?>