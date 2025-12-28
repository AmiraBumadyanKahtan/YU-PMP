<?php
// modules/initiatives/tabs/team.php

// 1. تحديد الصلاحيات (ربطها بصلاحية manage_initiative_team)
$canManageTeam = Auth::can('manage_initiative_team') || $isOwner || $isSuper;

// 2. معالجة الإضافة (Add Member)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_team_member']) && $canManageTeam) {
    $userId = $_POST['user_id'];
    $roleId = $_POST['role_id'];
    
    // التحقق من عدم التكرار
    $exists = $db->query("SELECT COUNT(*) FROM initiative_team WHERE initiative_id=$id AND user_id=$userId")->fetchColumn();
    
    if (!$exists) {
        $stmt = $db->prepare("INSERT INTO initiative_team (initiative_id, user_id, role_id, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
        $stmt->execute([$id, $userId, $roleId]);
        echo "<script>window.location.href='view.php?id=$id&tab=team&msg=member_added';</script>";
    } else {
        echo "<script>Swal.fire('Warning', 'This user is already in the team!', 'warning');</script>";
    }
}

// 3. معالجة الحذف (Remove Member)
if (isset($_GET['remove_member']) && $canManageTeam) {
    $memId = $_GET['remove_member'];
    
    $targetUserId = $db->query("SELECT user_id FROM initiative_team WHERE id=$memId")->fetchColumn();
    
    if ($targetUserId != $init['owner_user_id']) {
        $db->exec("DELETE FROM initiative_team WHERE id=$memId");
        echo "<script>window.location.href='view.php?id=$id&tab=team&msg=member_removed';</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Cannot remove the Initiative Owner.', 'error');</script>";
    }
}

// 4. جلب بيانات الفريق
$teamMembers = $db->query("
    SELECT it.*, u.full_name_en, u.avatar, u.job_title, d.name as dept_name, ir.name as role_name
    FROM initiative_team it
    JOIN users u ON u.id = it.user_id
    LEFT JOIN departments d ON d.id = u.department_id
    JOIN initiative_roles ir ON ir.id = it.role_id
    WHERE it.initiative_id = $id
    ORDER BY it.role_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($canManageTeam) {
    $departments = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();
    $roles = $db->query("SELECT id, name FROM initiative_roles ORDER BY id")->fetchAll();
}
?>

<style>
    /* --- تصميم الشبكة والكروت --- */
    .team-grid { 
        display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 25px; margin-top: 25px; 
    }
    
    .member-card { 
        background: #fff; border-radius: 20px; padding: 25px 20px; 
        display: flex; flex-direction: column; align-items: center; text-align: center;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); 
        position: relative; border: 1px solid #f0f2f5;
        box-shadow: 0 4px 6px rgba(0,0,0,0.01);
    }
    
    .member-card:hover { 
        transform: translateY(-8px); 
        box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
        border-color: #ff8c00; 
    }
    
    .mem-avatar { 
        width: 85px; height: 85px; border-radius: 50%; object-fit: cover; 
        border: 4px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.1); margin-bottom: 15px;
        transition: 0.3s;
    }
    .member-card:hover .mem-avatar { transform: scale(1.05); }
    
    .mem-info h4 { margin: 0 0 6px 0; font-size: 1.1rem; color: #2d3436; font-weight: 800; }
    .mem-job { font-size: 0.8rem; color: #636e72; margin-bottom: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .mem-dept { 
        font-size: 0.75rem; background: #fff3e0; padding: 4px 12px; border-radius: 20px; 
        color: #e67e22; font-weight: 700; display: inline-block; margin-bottom: 15px;
    }
    
    .role-badge { 
        position: absolute; top: 15px; right: 15px;
        font-size: 0.65rem; font-weight: 800; padding: 4px 10px; border-radius: 8px; 
        text-transform: uppercase; letter-spacing: 0.5px;
    }
    .role-1 { background: #ffebee; color: #c0392b; } /* Manager */
    .role-2 { background: #e3f2fd; color: #2980b9; } /* Member */
    .role-3 { background: #f3e5f5; color: #8e44ad; } /* Coordinator */
    .role-4 { background: #f5f5f5; color: #7f8c8d; } /* Viewer */

    .mem-actions { width: 100%; border-top: 1px solid #eee; padding-top: 15px; display: flex; justify-content: center; gap: 15px; }
    .action-icon { color: #b2bec3; font-size: 1rem; cursor: pointer; transition: 0.2s; }
    .action-icon:hover { color: #ff8c00; transform: scale(1.2); }
    .action-icon.del:hover { color: #e74c3c; }

    /* --- تصميم المودال الجديد (Premium) --- */
    /* ضمان إخفاء المودال عند التحميل */
    #addTeamModal { display: none; } 

    .modal-overlay {
        display: none; /* Hidden by default */
        position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0, 0, 0, 0.5); /* Black w/ opacity */
        backdrop-filter: blur(5px); /* Glass effect */
        justify-content: center; align-items: center;
        animation: fadeIn 0.3s;
    }
    
    .modal-box {
        background-color: #fff; width: 100%; max-width: 550px;
        border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        overflow: hidden; transform: translateY(20px); animation: slideUp 0.3s forwards;
    }

    .modal-header {
        background: #fff; padding: 25px 30px; border-bottom: 1px solid #f0f0f0;
        display: flex; justify-content: space-between; align-items: center;
    }
    .modal-title { font-size: 1.4rem; font-weight: 800; color: #2d3436; margin: 0; display: flex; align-items: center; gap: 10px; }
    .modal-close { font-size: 1.5rem; color: #cbd5e0; cursor: pointer; transition: 0.2s; }
    .modal-close:hover { color: #e74c3c; transform: rotate(90deg); }

    .modal-body { padding: 30px; background: #fdfdfd; }

    .form-row { margin-bottom: 20px; }
    .form-lbl { display: block; font-weight: 700; color: #4a5568; margin-bottom: 8px; font-size: 0.9rem; }
    .form-input {
        width: 100%; padding: 12px 15px; border-radius: 12px; border: 2px solid #edf2f7;
        background: #fff; font-size: 0.95rem; color: #2d3436; transition: 0.2s;
        box-sizing: border-box; font-family: inherit; appearance: none;
    }
    .form-input:focus { border-color: #ff8c00; outline: none; box-shadow: 0 0 0 4px rgba(255,140,0,0.1); }
    .form-input:disabled { background: #f7fafc; color: #a0aec0; cursor: not-allowed; }

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
            <h3 style="margin:0; color:#2c3e50; font-size:1.3rem;">Team Members & Roles</h3>
            <p style="margin:5px 0 0; color:#95a5a6; font-size:0.9rem;">Manage permissions and access.</p>
        </div>
        
        <?php if($canManageTeam): ?>
            <button type="button" onclick="openModal()" class="btn-primary" style="padding: 12px 25px; border-radius: 30px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-user-plus"></i> Add New Member
            </button>
        <?php endif; ?>
    </div>

    <?php if(empty($teamMembers)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-users-slash" style="font-size:3rem; color:#eee;"></i>
            <p style="color:#aaa; margin-top:10px;">No team members assigned yet.</p>
        </div>
    <?php else: ?>
        <div class="team-grid">
            <?php foreach($teamMembers as $mem): 
                $av = $mem['avatar'] ? '../../assets/uploads/avatars/'.$mem['avatar'] : '../../assets/uploads/avatars/default-profile.png';
                $isOwnerUser = ($mem['user_id'] == $init['owner_user_id']);
            ?>
            <div class="member-card">
                <span class="role-badge role-<?= $mem['role_id'] ?>"><?= htmlspecialchars($mem['role_name']) ?></span>
                <img src="<?= $av ?>" class="mem-avatar">
                
                <div class="mem-info">
                    <h4>
                        <?= htmlspecialchars($mem['full_name_en']) ?> 
                        <?php if($isOwnerUser) echo '<i class="fa-solid fa-crown" style="color:#f1c40f; font-size:0.9rem;" title="Owner"></i>'; ?>
                    </h4>
                    <div class="mem-job"><?= htmlspecialchars($mem['job_title'] ?? 'N/A') ?></div>
                    <div class="mem-dept"><?= htmlspecialchars($mem['dept_name'] ?? 'General') ?></div>
                </div>

                <?php if($canManageTeam && !$isOwnerUser): ?>
                    <div class="mem-actions">
                        <i class="fa-solid fa-sliders action-icon" onclick="Swal.fire('Info', 'Permissions management coming soon', 'info')" title="Permissions"></i>
                        <a href="view.php?id=<?= $id ?>&tab=team&remove_member=<?= $mem['id'] ?>" onclick="return confirm('Remove user?')" style="text-decoration:none;">
                            <i class="fa-solid fa-trash-can action-icon del" title="Remove"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if($canManageTeam): ?>
<div id="addTeamModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title">
                <div style="width:40px; height:40px; background:#fff3e0; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#ff8c00;">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                Add Team Member
            </h3>
            <div class="modal-close" onclick="closeModal()">&times;</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="add_team_member" value="1">
            <div class="modal-body">
                
                <div class="form-row">
                    <label class="form-lbl">1. Filter by Department</label>
                    <div style="position:relative;">
                        <select id="dept_select" class="form-input" onchange="fetchUsersByDept(this.value)">
                            <option value="">-- Show All Departments --</option>
                            <?php foreach($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down" style="position:absolute; right:15px; top:15px; color:#a0aec0; pointer-events:none;"></i>
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-lbl">2. Select User <span style="color:#e74c3c">*</span></label>
                    <div style="position:relative;">
                        <select name="user_id" id="user_select" class="form-input" required disabled>
                            <option value="">(Select Department First)</option>
                        </select>
                        <i class="fa-solid fa-chevron-down" style="position:absolute; right:15px; top:15px; color:#a0aec0; pointer-events:none;"></i>
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:0;">
                    <label class="form-lbl">3. Assign Role <span style="color:#e74c3c">*</span></label>
                    <div style="position:relative;">
                        <select name="role_id" class="form-input" required>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down" style="position:absolute; right:15px; top:15px; color:#a0aec0; pointer-events:none;"></i>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-save">Add Member</button>
            </div>
        </form>
    </div>
</div>

<script>
// Logic for Modal
function openModal() {
    document.getElementById('addTeamModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('addTeamModal').style.display = 'none';
}

// Logic for AJAX
function fetchUsersByDept(deptId) {
    const userSelect = document.getElementById('user_select');
    userSelect.innerHTML = '<option>Loading users...</option>';
    userSelect.disabled = true;

    // تأكد أن view.php يحتوي على معالج AJAX في الأعلى
    fetch(`view.php?id=<?= $id ?>&action=get_users&dept_id=${deptId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            userSelect.innerHTML = '<option value="">-- Select User --</option>';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(user => {
                    userSelect.innerHTML += `<option value="${user.id}">${user.full_name_en}</option>`;
                });
                userSelect.disabled = false;
            } else {
                userSelect.innerHTML = '<option value="">No users found</option>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            userSelect.innerHTML = '<option value="">Error loading users</option>';
        });
}

// Close on outside click
window.onclick = function(event) {
    const modal = document.getElementById('addTeamModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>
<?php endif; ?>