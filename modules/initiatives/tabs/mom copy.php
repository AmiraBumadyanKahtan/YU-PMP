<?php
// modules/initiatives/tabs/mom.php

// 1. الصلاحيات
$canManageMoM = ($isOwner || $isSuper || Auth::can('manage_initiative_documents')) && !$isLocked;

// 2. معالجة الإضافة (Add Meeting)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_mom']) && $canManageMoM) {
    try {
        $topic = $_POST['topic'];
        $date = $_POST['meeting_date'];
        $time = $_POST['meeting_time'];
        $venue = $_POST['venue'];
        $agenda = $_POST['agenda'];
        $adjTime = $_POST['adjournment_time'];
        $nextMeet = $_POST['next_meeting_datetime'];
        
        // معالجة المصفوفات (JSON)
        $attendees = isset($_POST['attendees']) ? json_encode($_POST['attendees']) : '[]';
        $absentees = isset($_POST['absentees']) ? json_encode($_POST['absentees']) : '[]';
        
        // معالجة نقاط النقاش (Dynamic Topics)
        $topicList = [];
        if (isset($_POST['topic_title']) && is_array($_POST['topic_title'])) {
            for ($i = 0; $i < count($_POST['topic_title']); $i++) {
                if (!empty($_POST['topic_title'][$i])) {
                    $topicList[] = [
                        'title' => $_POST['topic_title'][$i],
                        'type' => $_POST['topic_type'][$i] ?? 'info',
                        'action_by' => $_POST['topic_action_by'][$i] ?? '',
                        'deadline' => $_POST['topic_deadline'][$i] ?? ''
                    ];
                }
            }
        }
        $topicsJson = json_encode($topicList);

        $stmt = $db->prepare("
            INSERT INTO meeting_minutes (
                parent_type, parent_id, topic, meeting_date, meeting_time, venue,
                attendees, absentees, agenda, topics, 
                next_meeting_datetime, adjournment_time, prepared_by, created_at
            ) VALUES (
                'initiative', ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $id, $topic, $date, $time, $venue, 
            $attendees, $absentees, $agenda, $topicsJson,
            $nextMeet, $adjTime, $_SESSION['user_id']
        ]);

        echo "<script>window.location.href='view.php?id=$id&tab=mom&msg=mom_added';</script>";

    } catch (Exception $e) {
        echo "<script>Swal.fire('Error', 'Database error: " . addslashes($e->getMessage()) . "', 'error');</script>";
    }
}

// 3. معالجة الاعتماد (Approve Meeting)
if (isset($_GET['approve_meeting']) && ($isOwner || $isSuper)) {
    $mId = $_GET['approve_meeting'];
    $db->prepare("UPDATE meeting_minutes SET approved_by = ? WHERE id = ?")->execute([$_SESSION['user_id'], $mId]);
    echo "<script>window.location.href='view.php?id=$id&tab=mom&msg=meeting_approved';</script>";
}

// 4. معالجة الحذف
if (isset($_GET['delete_mom']) && $canManageMoM) {
    $mId = $_GET['delete_mom'];
    $db->prepare("DELETE FROM meeting_minutes WHERE id=?")->execute([$mId]);
    echo "<script>window.location.href='view.php?id=$id&tab=mom&msg=mom_deleted';</script>";
}

// 5. جلب البيانات
$meetings = $db->prepare("
    SELECT mm.*, u.full_name_en as preparer_name, u.avatar as preparer_avatar,
           approver.full_name_en as approver_name
    FROM meeting_minutes mm
    LEFT JOIN users u ON u.id = mm.prepared_by
    LEFT JOIN users approver ON approver.id = mm.approved_by
    WHERE mm.parent_type = 'initiative' AND mm.parent_id = ?
    ORDER BY mm.meeting_date DESC, mm.meeting_time DESC
");
$meetings->execute([$id]);
$meetingList = $meetings->fetchAll(PDO::FETCH_ASSOC);

// جلب أعضاء الفريق للاختيار
$teamMembers = $db->query("
    SELECT u.id, u.full_name_en, u.avatar 
    FROM initiative_team it
    JOIN users u ON u.id = it.user_id
    WHERE it.initiative_id = $id AND it.is_active = 1
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Card Styles */
    .meet-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; margin-top: 25px; }
    
    .meet-card { 
        background: #fff; border: 1px solid #f0f2f5; border-radius: 16px; 
        transition: all 0.3s ease; position: relative; overflow: hidden;
        display: flex; flex-direction: column;
        box-shadow: 0 4px 6px rgba(0,0,0,0.01);
    }
    .meet-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-color: #ff8c00; }

    /* Date Box */
    .meet-date-box { 
        background: #f8f9fa; padding: 15px; text-align: center; border-bottom: 1px solid #eee;
        display: flex; justify-content: space-between; align-items: center;
    }
    .md-day { font-size: 1.5rem; font-weight: 800; color: #2d3436; line-height: 1; }
    .md-month { font-size: 0.8rem; text-transform: uppercase; color: #ff8c00; font-weight: 700; }
    .md-time { font-size: 0.8rem; color: #95a5a6; background: #fff; padding: 4px 10px; border-radius: 20px; border: 1px solid #eee; }

    .meet-body { padding: 20px; flex: 1; }
    .meet-title { font-size: 1.1rem; font-weight: 800; color: #2d3436; margin-bottom: 10px; }
    .meet-venue { font-size: 0.85rem; color: #636e72; margin-bottom: 15px; display: flex; align-items: center; gap: 5px; }
    
    /* Attendees Stack */
    .attendee-stack { display: flex; margin-bottom: 15px; }
    .att-avatar { 
        width: 30px; height: 30px; border-radius: 50%; object-fit: cover; 
        border: 2px solid #fff; margin-left: -10px; transition: 0.2s;
    }
    .att-avatar:first-child { margin-left: 0; }
    .att-avatar:hover { transform: translateY(-3px); z-index: 2; }
    .att-count { 
        width: 30px; height: 30px; border-radius: 50%; background: #eee; 
        color: #777; font-size: 0.75rem; display: flex; align-items: center; 
        justify-content: center; margin-left: -10px; border: 2px solid #fff; font-weight: 700; 
    }

    .meet-footer { 
        padding: 15px 20px; border-top: 1px dashed #eee; background: #fcfcfc;
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .status-badge { font-size: 0.7rem; padding: 3px 10px; border-radius: 10px; font-weight: 700; text-transform: uppercase; }
    .st-draft { background: #fff3e0; color: #e67e22; }
    .st-approved { background: #e8f5e9; color: #27ae60; }

    .btn-view-meet { 
        color: #3498db; text-decoration: none; font-size: 0.85rem; font-weight: 700; 
        display: flex; align-items: center; gap: 5px; cursor: pointer;
    }
    .btn-view-meet:hover { text-decoration: underline; }
    .btn-icon-del { color: #ccc; cursor: pointer; transition: 0.2s; }
    .btn-icon-del:hover { color: #e74c3c; }

    /* Modal Extras */
    .topic-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
    .btn-add-topic { background: #e3f2fd; color: #3498db; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600; margin-bottom: 10px; }
    .user-select-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-height: 150px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 8px; }
    .user-chk { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #555; }
    
    /* Reuse Modal Styles */
    .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px); justify-content: center; align-items: center; }
    .modal-box { background: #fff; width: 700px; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.2); overflow: hidden; transform: translateY(20px); animation: slideUp 0.3s forwards; max-height: 90vh; display: flex; flex-direction: column; }
    .modal-body { padding: 30px; overflow-y: auto; }
</style>

<div class="tab-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
            <h3 style="margin:0; color:#2c3e50;">Meeting Minutes</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Document discussions, decisions, and attendance.</p>
        </div>
        <?php if($canManageMoM): ?>
            <button onclick="openMeetModal()" class="btn-primary" style="padding:12px 25px; border-radius:30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-pen-to-square"></i> New MOM
            </button>
        <?php endif; ?>
    </div>

    <?php if(empty($meetingList)): ?>
        <div style="text-align:center; padding:50px; border:2px dashed #eee; border-radius:16px; color:#ccc;">
            <i class="fa-solid fa-handshake" style="font-size:3rem; margin-bottom:15px; opacity:0.5;"></i>
            <p style="font-size:1.1rem;">No meetings recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="meet-grid">
            <?php foreach($meetingList as $m): 
                $day = date('d', strtotime($m['meeting_date']));
                $month = date('M', strtotime($m['meeting_date']));
                $time = date('h:i A', strtotime($m['meeting_time']));
                
                $attIds = json_decode($m['attendees'], true) ?: [];
                $attCount = count($attIds);
            ?>
            <div class="meet-card">
                <div class="meet-date-box">
                    <div style="text-align:left;">
                        <div class="md-day"><?= $day ?></div>
                        <div class="md-month"><?= $month ?></div>
                    </div>
                    <div class="md-time"><i class="fa-regular fa-clock"></i> <?= $time ?></div>
                </div>
                
                <div class="meet-body">
                    <div class="meet-title"><?= htmlspecialchars($m['topic']) ?></div>
                    <div class="meet-venue"><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($m['venue']) ?></div>
                    
                    <div class="attendee-stack">
                        <?php 
                        $shown = 0;
                        foreach($teamMembers as $tm) {
                            if(in_array($tm['id'], $attIds)) {
                                if($shown < 5) {
                                    $av = $tm['avatar'] ? '../../assets/uploads/avatars/'.$tm['avatar'] : '../../assets/uploads/avatars/default-profile.png';
                                    echo '<img src="'.$av.'" class="att-avatar" title="'.htmlspecialchars($tm['full_name_en']).'">';
                                    $shown++;
                                }
                            }
                        }
                        if($attCount > 5) {
                            echo '<div class="att-count">+'.($attCount - 5).'</div>';
                        }
                        if($attCount == 0) echo '<span style="font-size:0.8rem; color:#ccc;">No attendees recorded</span>';
                        ?>
                    </div>

                    <?php if($m['next_meeting_datetime']): ?>
                         <div style="font-size:0.8rem; color:#e67e22; font-weight:600; margin-top: 10px;">
                            <i class="fa-solid fa-forward"></i> Next: <?= date('d M, h:i A', strtotime($m['next_meeting_datetime'])) ?>
                         </div>
                    <?php endif; ?>
                </div>

                <div class="meet-footer">
                    <?php if($m['approved_by']): ?>
                        <span class="status-badge st-approved"><i class="fa-solid fa-check"></i> Approved</span>
                    <?php else: ?>
                        <span class="status-badge st-draft"><i class="fa-solid fa-pen-ruler"></i> Draft</span>
                    <?php endif; ?>
                    
                    <div style="display:flex; gap:10px;">
                        <span class="btn-view-meet" onclick='viewMeeting(<?= json_encode($m) ?>)'>View Details</span>
                        <?php if($canManageMoM): ?>
                        <a href="view.php?id=<?= $id ?>&tab=mom&delete_mom=<?= $m['id'] ?>" class="btn-icon-del" onclick="return confirm('Delete record?')">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if($canManageMoM): ?>
<div id="addMeetModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" style="margin:0; color:#2c3e50;">Record Meeting Minutes</h3>
            <span onclick="closeMeetModal()" style="font-size:1.5rem; cursor:pointer; color:#ccc;">&times;</span>
        </div>
        
        <form method="POST" class="modal-body">
            <input type="hidden" name="add_mom" value="1">
            
            <div class="form-row">
                <label class="form-lbl">Meeting Topic <span style="color:red">*</span></label>
                <input type="text" name="topic" class="form-input" required placeholder="e.g. Weekly Progress Review">
            </div>

            <div class="grid-2">
                <div class="form-row">
                    <label class="form-lbl">Date <span style="color:red">*</span></label>
                    <input type="date" name="meeting_date" class="form-input" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-row">
                    <label class="form-lbl">Time <span style="color:red">*</span></label>
                    <input type="time" name="meeting_time" class="form-input" required value="<?= date('H:i') ?>">
                </div>
            </div>

            <div class="form-row">
                <label class="form-lbl">Venue / Link</label>
                <input type="text" name="venue" class="form-input" placeholder="e.g. Conference Room A or Zoom Link">
            </div>

            <div class="grid-2" style="margin-top:20px;">
                <div>
                    <label class="form-lbl">Attendees</label>
                    <div class="user-select-grid">
                        <?php foreach($teamMembers as $tm): ?>
                            <label class="user-chk">
                                <input type="checkbox" name="attendees[]" value="<?= $tm['id'] ?>"> 
                                <?= htmlspecialchars($tm['full_name_en']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="form-lbl">Absentees</label>
                    <div class="user-select-grid">
                        <?php foreach($teamMembers as $tm): ?>
                            <label class="user-chk">
                                <input type="checkbox" name="absentees[]" value="<?= $tm['id'] ?>"> 
                                <?= htmlspecialchars($tm['full_name_en']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:20px;">
                <label class="form-lbl">Meeting Agenda</label>
                <textarea name="agenda" class="form-input" style="height:60px;" placeholder="Main points to discuss..."></textarea>
            </div>

            <div class="form-row">
                <label class="form-lbl">Discussion Points & Decisions</label>
                <div id="topics_container"></div>
                <button type="button" class="btn-add-topic" onclick="addTopicRow()">+ Add Point</button>
            </div>

            <div class="grid-2" style="margin-top:20px; padding-top:20px; border-top:1px dashed #eee;">
                <div>
                    <label class="form-lbl">Adjournment Time</label>
                    <input type="time" name="adjournment_time" class="form-input">
                </div>
                <div>
                    <label class="form-lbl">Next Meeting (Optional)</label>
                    <input type="datetime-local" name="next_meeting_datetime" class="form-input">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeMeetModal()">Cancel</button>
                <button type="submit" class="btn-submit" style="background:#ff8c00; border:none; color:white;">Save Minutes</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="viewMeetModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" style="margin:0;" id="vm_topic">Topic</h3>
            <span onclick="document.getElementById('viewMeetModal').style.display='none'" style="cursor:pointer; font-size:1.5rem;">&times;</span>
        </div>
        <div class="modal-body">
            <div style="display:flex; justify-content:space-between; margin-bottom:20px; background:#f9f9f9; padding:15px; border-radius:12px;">
                <div><strong>Date:</strong> <span id="vm_date"></span></div>
                <div><strong>Venue:</strong> <span id="vm_venue"></span></div>
                <div><strong>Prep By:</strong> <span id="vm_prep"></span></div>
            </div>

            <h4 style="margin-bottom:10px; color:#3498db;">Agenda</h4>
            <p id="vm_agenda" style="color:#555; line-height:1.6; margin-bottom:20px; white-space: pre-wrap;"></p>

            <h4 style="margin-bottom:10px; color:#e67e22;">Discussion & Decisions</h4>
            <table class="disc-table" style="margin-bottom:20px;">
                <thead>
                    <tr style="background:#f0f0f0;"><th>Point / Decision</th><th>Type</th><th>Action By</th><th>Deadline</th></tr>
                </thead>
                <tbody id="vm_topics_body"></tbody>
            </table>

            <div style="display:flex; gap:30px;">
                <div>
                    <h5 style="margin-bottom:5px;">Attendees</h5>
                    <ul id="vm_att_list" style="padding-left:20px; color:#555; font-size:0.9rem;"></ul>
                </div>
                <div>
                    <h5 style="margin-bottom:5px; color:#e74c3c;">Absentees</h5>
                    <ul id="vm_abs_list" style="padding-left:20px; color:#555; font-size:0.9rem;"></ul>
                </div>
            </div>
            
            <div id="vm_approval_section" style="margin-top:30px; text-align:right; border-top:1px solid #eee; padding-top:15px;"></div>
        </div>
    </div>
</div>

<script>
    function openMeetModal() { document.getElementById('addMeetModal').style.display = 'flex'; }
    function closeMeetModal() { document.getElementById('addMeetModal').style.display = 'none'; }
    
    // Dynamic Topics Script
    function addTopicRow() {
        const container = document.getElementById('topics_container');
        const row = document.createElement('div');
        row.className = 'topic-row';
        row.innerHTML = `
            <input type="text" name="topic_title[]" class="form-input" style="flex:2;" placeholder="Discussion point..." required>
            <select name="topic_type[]" class="form-input" style="flex:1;">
                <option value="info">Info</option>
                <option value="decision">Decision</option>
                <option value="action">Action</option>
            </select>
            <input type="text" name="topic_action_by[]" class="form-input" style="flex:1;" placeholder="Owner">
            <input type="date" name="topic_deadline[]" class="form-input" style="flex:1;">
            <span onclick="this.parentElement.remove()" style="color:red; cursor:pointer; font-weight:bold;">&times;</span>
        `;
        container.appendChild(row);
    }
    // Add one row by default
    document.addEventListener('DOMContentLoaded', () => { if(document.getElementById('topics_container')) addTopicRow(); });

    // View Details Logic
    const allUsers = <?= json_encode($teamMembers) ?>; // Pass PHP array to JS
    
    function viewMeeting(m) {
        document.getElementById('viewMeetModal').style.display = 'flex';
        document.getElementById('vm_topic').innerText = m.topic;
        document.getElementById('vm_date').innerText = m.meeting_date + ' ' + m.meeting_time;
        document.getElementById('vm_venue').innerText = m.venue;
        document.getElementById('vm_prep').innerText = m.preparer_name;
        document.getElementById('vm_agenda').innerText = m.agenda || 'No agenda recorded.';
        
        // Topics Table
        const tbody = document.getElementById('vm_topics_body');
        tbody.innerHTML = '';
        let topics = [];
        try { topics = JSON.parse(m.topics); } catch(e) {}
        
        if(topics.length > 0) {
            topics.forEach(t => {
                let badgeClass = 'tb-info';
                if(t.type == 'decision') badgeClass = 'tb-decision';
                if(t.type == 'action') badgeClass = 'tb-action';

                tbody.innerHTML += `<tr>
                    <td style="text-align:left;">${t.title}</td>
                    <td><span class="type-badge ${badgeClass}">${t.type}</span></td>
                    <td>${t.action_by}</td>
                    <td>${t.deadline}</td>
                </tr>`;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4">No details recorded.</td></tr>';
        }

        // Attendance Lists
        const attList = document.getElementById('vm_att_list');
        const absList = document.getElementById('vm_abs_list');
        attList.innerHTML = ''; absList.innerHTML = '';
        
        let attIds = JSON.parse(m.attendees || '[]');
        let absIds = JSON.parse(m.absentees || '[]');
        
        allUsers.forEach(u => {
            if(attIds.includes(u.id.toString()) || attIds.includes(u.id)) {
                attList.innerHTML += `<li>${u.full_name_en}</li>`;
            }
            if(absIds.includes(u.id.toString()) || absIds.includes(u.id)) {
                absList.innerHTML += `<li>${u.full_name_en}</li>`;
            }
        });

        // Approval Button Logic
        const appSec = document.getElementById('vm_approval_section');
        appSec.innerHTML = '';
        if(m.approved_by) {
            appSec.innerHTML = `<span style="color:#27ae60; font-weight:bold;"><i class="fa-solid fa-check-double"></i> Approved by ${m.approver_name}</span>`;
        } else {
            <?php if($isOwner || $isSuper): ?>
                appSec.innerHTML = `<a href="view.php?id=<?= $id ?>&tab=mom&approve_meeting=${m.id}" class="btn-submit" style="text-decoration:none; display:inline-block; padding:8px 20px;">Approve Minutes</a>`;
            <?php else: ?>
                appSec.innerHTML = `<span style="color:#f39c12;">Pending Approval</span>`;
            <?php endif; ?>
        }
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('addMeetModal')) closeMeetModal();
        if (event.target == document.getElementById('viewMeetModal')) document.getElementById('viewMeetModal').style.display='none';
    }
</script>