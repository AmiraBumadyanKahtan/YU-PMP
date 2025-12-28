<?php
// modules/operational_projects/team.php
require_once "php/team_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/team.css">
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

    <?php include "project_header_inc.php"; ?>

    <?php if (!$isLockedStatus): ?>
        <div class="locked-banner">
            <i class="fa-solid fa-lock"></i>
            <div>
                Project is currently 
                <strong>
                    <?php 
                        if ($project['status_id'] == 4) echo 'Rejected';
                        elseif ($project['status_id'] == 1) echo 'Draft';
                        elseif ($project['status_id'] == 8) echo 'Completed';
                        elseif ($project['status_id'] == 7) echo 'On Hold';
                        else echo 'Locked';
                    ?>
                </strong>. 
                Team management is disabled.
            </div>
        </div>
    <?php endif; ?>

    <div class="page-header-flex">
        <div class="page-title">
            <h3>Project Team</h3>
            <p>Manage members, roles, and permissions.</p>
        </div>
    </div>

    <?php if ($canManageTeam): ?>
    <div class="add-box">
        <h4 class="add-title"><i class="fa-solid fa-user-plus" style="color:#ff8c00;"></i> Add New Member</h4>
        <form method="POST" class="add-form">
            <select name="user_id" class="form-select" required>
                <option value="">-- Select User --</option>
                <?php foreach($availableUsers as $u): ?> <option value="<?= $u['id'] ?>"><?= $u['full_name_en'] ?></option> <?php endforeach; ?>
            </select>
            <select name="role_id" class="form-select" required>
                <option value="">-- Select Role --</option>
                <?php foreach($projectRoles as $r): ?> <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option> <?php endforeach; ?>
            </select>
            <button type="submit" name="add_member" class="btn-primary-pill" style="padding: 10px 30px;">Add</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="team-grid">
        <div class="member-card card-pm">
            <img src="<?= BASE_URL ?>/assets/uploads/avatars/default-profile.png" class="member-avatar">
            <div class="member-info">
                <h4><?= htmlspecialchars($project['manager_name']) ?></h4>
                <p>Project Manager</p>
                <span class="role-badge role-pm-badge">Owner</span>
            </div>
        </div>

        <?php foreach($teamMembers as $m): ?>
            <div class="member-card">
                <img src="<?= $m['avatar'] ? BASE_URL.'/assets/uploads/avatars/'.$m['avatar'] : BASE_URL.'/assets/uploads/avatars/default-profile.png' ?>" class="member-avatar">
                <div class="member-info">
                    <h4><?= htmlspecialchars($m['full_name_en']) ?></h4>
                    <p><?= htmlspecialchars($m['email']) ?></p>
                    <span class="role-badge role-member-badge"><?= htmlspecialchars($m['role_name']) ?></span>
                </div>
                
                <?php if ($canManageTeam): ?>
                    <div class="action-btns">
                        <button type="button" class="btn-icon" onclick="openEditModal(<?= $m['user_id'] ?>, '<?= $m['full_name_en'] ?>', <?= $m['role_id'] ?>)" title="Edit Role">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <a href="?id=<?= $id ?>&remove_uid=<?= $m['user_id'] ?>" onclick="return confirm('Remove this user?')" class="btn-icon btn-del" title="Remove Member">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Role</h3>
            <span class="close" onclick="closeEditModal()">&times;</span>
        </div>
        
        <form method="POST">
            <div class="modal-body">
                <p id="editUserName" style="color: #64748b; margin-bottom: 20px; font-weight: 500;"></p>
                <input type="hidden" name="user_id" id="editUserId">
                <input type="hidden" name="update_member" value="1">
                
                <label style="display:block; margin-bottom:8px; font-weight:700; color:#334155;">Select New Role</label>
                <select name="role_id" id="editRoleId" class="form-select" style="width:100%;">
                    <?php foreach($projectRoles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-save">Update Role</button>
            </div>
        </form>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
<script>
    let msg = '<?= $_GET['msg'] ?>';
    let text = msg === 'added' ? 'Member added successfully' : (msg === 'removed' ? 'Member removed' : (msg === 'cannot_remove_owner' ? 'Cannot remove the Project Manager!' : 'Role updated successfully'));
    let icon = msg === 'cannot_remove_owner' ? 'error' : 'success';
    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, icon: icon, title: text });
</script>
<?php endif; ?>

<script>
    function openEditModal(userId, userName, currentRoleId) {
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUserName').innerText = "Member: " + userName;
        document.getElementById('editRoleId').value = currentRoleId;
        document.getElementById('editModal').style.display = "block";
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = "none";
    }
    window.onclick = function(event) {
        if (event.target == document.getElementById('editModal')) {
            closeEditModal();
        }
    }
</script>

</body>
</html>