<?php
// modules/pillars/view.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$pillar = getPillarById($id);

if (!$pillar) die("Pillar not found");

// Automatic Status Update
if (function_exists('updatePillarStatusAutomatic')) {
    updatePillarStatusAutomatic($id);
    $pillar = getPillarById($id); 
}

$db = Database::getInstance()->pdo();

// --- Logic for Permissions & Locks ---
$statusId = $pillar['status_id'];

// Status Definitions
// 12=Draft, 6=Draft(Legacy), 14=Returned (Assuming) -> Editable
// 11=Approved, 3=In Progress, 4=On Track, 5=At Risk, 8=Delayed -> Active
// 7=Completed, 13=Rejected -> Closed

$isDraftOrReturned = ($statusId == 12 || $statusId == 6 || $statusId == 14); 
$isActiveState     = ($statusId == 11 || $statusId == 3 || $statusId == 4 || $statusId == 5 || $statusId == 8); 
$isCompleted       = ($statusId == 7);
$isRejected        = ($statusId == 13);
$isLocked          = ! $isDraftOrReturned; // Locked for Lead editing

// User Roles
$userId = $_SESSION['user_id'];
$isLead = ($pillar['lead_user_id'] == $userId);
$isSuperAdmin = ($_SESSION['role_key'] == 'super_admin');

// Check if user is Strategy Dept Manager
$isStrategyManager = false;
$stmtStrat = $db->prepare("SELECT COUNT(*) FROM departments WHERE manager_id = ? AND (name LIKE '%Strategy%' OR id = 11)"); 
$stmtStrat->execute([$userId]);
if ($stmtStrat->fetchColumn() > 0) {
    $isStrategyManager = true;
}

// --- Permission Matrix ---

// 1. Basic Edit (Name, Dates, Objectives)
// Allowed only in Draft/Returned for Lead, Strategy Mgr, Super Admin
$canEditBasic = ($isDraftOrReturned && ($isLead || $isStrategyManager || $isSuperAdmin));

// 2. Team Management (Add/Remove Members)
// - Draft/Returned: Lead, Strategy Mgr, Super Admin
// - Active (Approved..): Strategy Mgr, Super Admin ONLY
$canManageTeam = false;
if ($isDraftOrReturned) {
    $canManageTeam = ($isLead || $isStrategyManager || $isSuperAdmin);
} elseif ($isActiveState) {
    $canManageTeam = ($isStrategyManager || $isSuperAdmin);
}

// 3. Document Management (Upload/Delete)
$canManageDocs = false;
if ($isDraftOrReturned || $isActiveState) {
    $canManageDocs = ($isLead || $isStrategyManager || $isSuperAdmin);
}

// 4. Submit for Approval
$canSubmit = ($isDraftOrReturned && ($isLead || $isStrategyManager || $isSuperAdmin));


// --- POST Handling ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Add Objective
    if (isset($_POST['add_objective']) && $canEditBasic) {
        addStrategicObjective($id, $_POST['obj_text']); 
        header("Location: view.php?id=$id&tab=objectives&msg=obj_added");
        exit;
    }

    // 2. Add Member
    if (isset($_POST['add_member']) && $canManageTeam) {
        addPillarMember($id, $_POST['user_id'], $_POST['role_id']);
        header("Location: view.php?id=$id&tab=team&msg=member_added");
        exit;
    }

    // 3. Upload Document
    if (isset($_POST['upload_doc']) && $canManageDocs) { 
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $docData = [
                'pillar_id' => $id,
                'title' => $_POST['doc_title'],
                'description' => $_POST['doc_desc']
            ];
            uploadPillarDocument($docData, $_FILES['file']);
            header("Location: view.php?id=$id&tab=documents&msg=doc_uploaded");
            exit;
        }
    }

    // 4. Submit for Approval (Validation Logic)
    if (isset($_POST['submit_approval']) && $canSubmit) {
        $teamMembers = getPillarTeam($id);
        $objs = getPillarObjectives($id);
        $docs = getPillarDocuments($id);
        
        $hasTeam = count($teamMembers) > 1; // Lead + at least 1 member
        $hasDocs = count($docs) > 0;
        $hasObjs = count($objs) > 0;
        
        if (!$hasTeam) {
            $error = "Cannot submit: You must add at least one team member (other than the lead).";
        } elseif (!$hasDocs) {
            $error = "Cannot submit: Please upload at least one supporting document.";
        } elseif (!$hasObjs) {
            $error = "Cannot submit: Please add at least one strategic objective.";
        } else {
            $res = submitPillarForApproval($id, $_SESSION['user_id']);
            if ($res['ok']) {
                header("Location: view.php?id=$id&msg=submitted");
                exit;
            } else {
                $error = $res['error'];
            }
        }
    }
}

// --- GET Handling (Delete) ---
if (isset($_GET['del_obj']) && $canEditBasic) {
    deleteStrategicObjective($_GET['del_obj']);
    header("Location: view.php?id=$id&tab=objectives&msg=obj_deleted");
    exit;
}
if (isset($_GET['del_member']) && $canManageTeam) {
    removePillarMember($_GET['del_member']);
    header("Location: view.php?id=$id&tab=team&msg=member_deleted");
    exit;
}
if (isset($_GET['del_doc']) && $canManageDocs) {
    deleteDocument($_GET['del_doc']);
    header("Location: view.php?id=$id&tab=documents&msg=doc_deleted");
    exit;
}

// Fetch Data for View
$objectives  = getPillarObjectives($id);
$tracker     = getPillarWorkflowTracker($id);
$team        = getPillarTeam($id);
$initiatives = getPillarInitiatives($id); 
$documents   = getPillarDocuments($id);   
$allUsers    = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 ORDER BY full_name_en")->fetchAll();
$pillarRoles = getPillarRoles();

$activeTab = $_GET['tab'] ?? 'overview';

function formatSize($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pillar['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="stylesheet" href="css/view.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
         /* Header Card */
        .pillar-header-card { 
            background: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; 
            border-top: 6px solid <?= $pillar['color'] ?>; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
            position: relative; overflow: hidden;
        }
        .pillar-bg-icon { 
            position: absolute; right: -20px; top: -30px; font-size: 10rem; 
            opacity: 0.05; color: <?= $pillar['color'] ?>; transform: rotate(-15deg);
        }

        .ph-title { margin: 0; font-size: 2rem; font-weight: 800; color: #2c3e50; line-height: 1.2; }
        .ph-meta { color: #7f8c8d; margin-top: 10px; font-size: 0.9rem; display: flex; align-items: center; gap: 15px; }
        .ph-badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; color: #fff; 
            display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;
            background-color: <?= $pillar['color'] ?>;
        }
        .status-badge { 
            padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; 
            background-color: <?= $pillar['status_color'] ?? '#ccc' ?>; color: #fff;
        }
        </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="pillar-header-card">
        <i class="fa-solid <?= $pillar['icon'] ?> pillar-bg-icon"></i>
        <div style="display:flex; justify-content:space-between; align-items:flex-start; position:relative; z-index:1;">
            <div>
                <span class="ph-badge">Pillar #<?= $pillar['pillar_number'] ?></span>
                <h1 class="ph-title" style="margin-top:10px;">
                    <?= htmlspecialchars($pillar['name']) ?>
                </h1>
                <div class="ph-meta">
                    <span><i class="fa-solid fa-user-tie"></i> Lead: <strong><?= htmlspecialchars($pillar['lead_name']) ?></strong></span>
                    <span><i class="fa-regular fa-calendar"></i> <?= $pillar['start_date'] ?> — <?= $pillar['end_date'] ?></span>
                </div>
            </div>

            <div style="text-align:right;">
                <div style="margin-bottom:15px;">
                    <span class="status-badge"><?= $pillar['status_name'] ?></span>
                </div>
                
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <?php if($canSubmit): ?>
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="submit_approval" class="btn-primary" onclick="return confirm('Submit pillar for final approval?')">
                                <i class="fa-solid fa-paper-plane"></i> Submit
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if($canEditBasic): ?>
                        <a href="edit.php?id=<?= $id ?>" class="btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
                    <?php elseif($isLocked): ?>
                        <button class="btn-primary btn-disabled" disabled title="Locked during approval process"><i class="fa-solid fa-lock"></i> Locked</button>
                    <?php endif; ?>
                </div>

                <?php if($isSuperAdmin && $isLocked): ?>
                    <div style="margin-top:5px; font-size:0.75rem; color:#e67e22; font-weight:bold;">
                        <i class="fa-solid fa-shield-halved"></i> Super Admin View
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #fca5a5;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if($isLocked): ?>
        <div class="locked-msg">
            <i class="fa-solid fa-lock"></i>
            Editing is disabled because this pillar is currently <strong><?= htmlspecialchars($pillar['status_name']) ?></strong>.
        </div>
    <?php endif; ?>

    <?php if(!empty($tracker)): ?>
    <div class="workflow-card">
        <h4 style="margin:0 0 10px 0; color:#2c3e50;">Approval Process</h4>
        <div class="workflow-bar">
            <?php foreach($tracker as $step): ?>
                <div class="wf-step st-<?= $step['status_visual'] ?>">
                    <div class="wf-dot">
                        <?php 
                            if($step['status_visual']=='approved') echo '<i class="fa-solid fa-check"></i>'; 
                            elseif($step['status_visual']=='rejected') echo '<i class="fa-solid fa-xmark"></i>';
                            else echo $step['stage_id']; 
                        ?>
                    </div>
                    <div class="wf-label"><?= $step['stage_name'] ?></div>
                    <div class="wf-sub"><?= $step['reviewer_name'] ?? 'Pending' ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab-btn <?= $activeTab=='overview'?'active':'' ?>" onclick="openTab('overview')">
            <i class="fa-solid fa-circle-info"></i> Overview
        </button>
        <button class="tab-btn <?= $activeTab=='objectives'?'active':'' ?>" onclick="openTab('objectives')">
            <i class="fa-solid fa-bullseye"></i> Objectives
        </button>
        <button class="tab-btn <?= $activeTab=='team'?'active':'' ?>" onclick="openTab('team')">
            <i class="fa-solid fa-users"></i> Team
        </button>
        <button class="tab-btn <?= $activeTab=='initiatives'?'active':'' ?>" onclick="openTab('initiatives')">
            <i class="fa-solid fa-rocket"></i> Initiatives
        </button>
        <button class="tab-btn <?= $activeTab=='documents'?'active':'' ?>" onclick="openTab('documents')">
            <i class="fa-solid fa-folder-open"></i> Documents
        </button>
    </div>

    <div id="overview" class="tab-content <?= $activeTab=='overview'?'active':'' ?>">
        <div class="tab-card">
            <h3 style="margin-top:0; color:#2c3e50;">Description</h3>
            <p style="line-height:1.8; color:#555; font-size:1.05rem;">
                <?= nl2br(htmlspecialchars($pillar['description'])) ?>
            </p>
            
            <div style="margin-top:40px; padding-top:20px; border-top:1px solid #f0f0f0;">
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <h4 style="margin:0; color:#2c3e50;">Overall Progress</h4>
                    <span style="font-weight:800; color:<?= $pillar['color'] ?>; font-size:1.2rem;"><?= $pillar['progress_percentage'] ?>%</span>
                </div>
                <div style="background:#f0f2f5; height:12px; border-radius:10px; overflow:hidden;">
                    <div style="height:100%; width:<?= $pillar['progress_percentage'] ?>%; background:<?= $pillar['color'] ?>; transition:width 1s ease;"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="objectives" class="tab-content <?= $activeTab=='objectives'?'active':'' ?>">
        <div class="tab-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; color:#2c3e50;">Strategic Objectives</h3>
                <?php if($canEditBasic): ?>
                    <button onclick="document.getElementById('addObjModal').style.display='flex'" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Add Objective
                    </button>
                <?php endif; ?>
            </div>

            <?php if(empty($objectives)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-bullseye"></i>
                    <p>No objectives defined yet.</p>
                </div>
            <?php else: ?>
                <table class="modern-table">
                    <thead><tr><th width="120">Code</th><th>Objective Description</th><th width="50"></th></tr></thead>
                    <tbody>
                        <?php foreach($objectives as $obj): ?>
                        <tr>
                            <td><span class="obj-code-badge"><?= $obj['objective_code'] ?></span></td>
                            <td><?= htmlspecialchars($obj['objective_text']) ?></td>
                            <td style="text-align:right;">
                                <?php if($canEditBasic): ?>
                                    <a href="?id=<?= $id ?>&tab=objectives&del_obj=<?= $obj['id'] ?>" onclick="return confirm('Delete?')" style="color:#e74c3c; font-size:1.1rem;">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div id="team" class="tab-content <?= $activeTab=='team'?'active':'' ?>">
        <div class="tab-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; color:#2c3e50;">Pillar Team</h3>
                <?php if($canManageTeam): ?>
                    <button onclick="document.getElementById('addMemberModal').style.display='flex'" class="btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Add Member
                    </button>
                <?php endif; ?>
            </div>

            <?php if(empty($team)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-users"></i>
                    <p>No team members added yet.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
                    <?php foreach($team as $m): ?>
                        <?php $avatar = $m['avatar'] ? BASE_URL.'assets/uploads/avatars/'.$m['avatar'] : BASE_URL.'assets/uploads/avatars/default-profile.png'; ?>
                        <div class="member-card">
                            <img src="<?= $avatar ?>" class="member-avatar">
                            <div style="flex:1;">
                                <div style="font-weight:700; color:#2c3e50; font-size:0.95rem;"><?= htmlspecialchars($m['full_name_en']) ?></div>
                                <div class="role-badge <?= ($m['role_name']=='Chair') ? 'role-chair' : '' ?>">
                                    <?= htmlspecialchars($m['role_name'] ?? 'Member') ?>
                                </div>
                            </div>
                            <?php if($canManageTeam): ?>
                                <a href="?id=<?= $id ?>&tab=team&del_member=<?= $m['id'] ?>" onclick="return confirm('Remove?')" style="color:#b2bec3; transition:0.2s;">
                                    <i class="fa-solid fa-xmark" style="font-size:1.2rem;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="initiatives" class="tab-content <?= $activeTab=='initiatives'?'active':'' ?>">
        <div class="tab-card">
            <h3 style="margin:0 0 20px 0; color:#2c3e50;">Linked Initiatives</h3>
            <?php if(empty($initiatives)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-rocket"></i>
                    <p>No initiatives linked to this pillar yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($initiatives as $init): ?>
                    <div class="init-card" style="border-left-color: <?= $init['status_color'] ?? '#ccc' ?>;">
                        <div style="flex:1;">
                            <div class="init-title"><?= htmlspecialchars($init['name']) ?></div>
                            <div style="font-size:0.85rem; color:#7f8c8d;">
                                <span style="margin-right:15px;"><i class="fa-solid fa-user"></i> Owner: <?= htmlspecialchars($init['owner_name']) ?></span>
                                <span><i class="fa-regular fa-calendar"></i> <?= $init['start_date'] ?></span>
                            </div>
                        </div>
                        <div style="text-align:right; margin:0 25px;">
                            <span class="status-badge" style="background-color: <?= $init['status_color'] ?? '#ccc' ?>;">
                                <?= $init['status_name'] ?>
                            </span>
                            <div style="font-weight:900; margin-top:5px; color:#2c3e50; font-size:1.1rem;"><?= $init['progress_percentage'] ?>%</div>
                        </div>
                        <a href="../initiatives/view.php?id=<?= $init['id'] ?>" class="btn-more">Details &rarr;</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="documents" class="tab-content <?= $activeTab=='documents'?'active':'' ?>">
        <div class="tab-card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0; color:#2c3e50;">Pillar Documents</h3>
                <?php if($canManageDocs): ?>
                    <button onclick="document.getElementById('uploadModal').style.display='flex'" class="btn-primary">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    </button>
                <?php endif; ?>
            </div>

            <?php if(empty($documents)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>No documents uploaded.</p>
                </div>
            <?php else: ?>
                <?php foreach($documents as $doc): ?>
                    <?php 
                        $ext = strtolower($doc['file_type']);
                        $iconClass = 'fa-file';
                        if(in_array($ext, ['pdf'])) $iconClass = 'fa-file-pdf pdf';
                        elseif(in_array($ext, ['doc','docx'])) $iconClass = 'fa-file-word word';
                        elseif(in_array($ext, ['xls','xlsx'])) $iconClass = 'fa-file-excel excel';
                        elseif(in_array($ext, ['jpg','png','jpeg'])) $iconClass = 'fa-file-image';
                    ?>
                    <div class="doc-item" style="display:flex; align-items:center; padding:15px; border:1px solid #eee; border-radius:12px; margin-bottom:10px;">
                        <i class="fa-solid <?= $iconClass ?>" style="font-size:1.5rem; margin-right:15px; color:#3498db;"></i>
                        <div style="flex:1;">
                            <div style="font-weight:700; color:#2c3e50;">
                                <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank" style="text-decoration:none; color:inherit;">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                            </div>
                            <div style="font-size:0.8rem; color:#95a5a6; margin-top:2px;">
                                <?= formatSize($doc['file_size']) ?> • By <?= htmlspecialchars($doc['uploader_name']) ?> • <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                            </div>
                        </div>
                        <?php if($canManageDocs): ?>
                            <a href="?id=<?= $id ?>&tab=documents&del_doc=<?= $doc['id'] ?>" onclick="return confirm('Delete document?')" style="color:#e74c3c;">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<?php if($canEditBasic): ?>
<div id="addObjModal" class="modal">
    <div class="modal-content">
        <h3>Add Strategic Objective</h3>
        <form method="POST">
            <input type="hidden" name="add_objective" value="1">
            <label style="display:block; font-weight:bold; margin-bottom:8px;">Objective Text</label>
            <textarea name="obj_text" rows="4" class="form-control" required placeholder="Enter objective description..."></textarea>
            <div style="text-align:right; margin-top:15px;">
                <button type="button" onclick="closeModals()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Objective</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if($canManageTeam): ?>
<div id="addMemberModal" class="modal">
    <div class="modal-content">
        <h3>Add Team Member</h3>
        <form method="POST">
            <input type="hidden" name="add_member" value="1">
            <label style="display:block; font-weight:bold; margin-bottom:8px;">Select User</label>
            <select name="user_id" class="form-control" required>
                <option value="">-- Choose User --</option>
                <?php foreach($allUsers as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name_en']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label style="display:block; font-weight:bold; margin-bottom:8px;">Role</label>
            <select name="role_id" class="form-control" required>
                <?php foreach($pillarRoles as $role): ?>
                    <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <div style="text-align:right; margin-top:15px;">
                <button type="button" onclick="closeModals()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add Member</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if($canManageDocs): ?>
<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h3>Upload Document</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_doc" value="1">
            <label style="display:block; font-weight:bold; margin-bottom:8px;">Title</label>
            <input type="text" name="doc_title" class="form-control" required placeholder="Document Title">
            
            <label style="display:block; font-weight:bold; margin-bottom:8px;">Description (Optional)</label>
            <textarea name="doc_desc" rows="2" class="form-control"></textarea>
            
            <label style="display:block; font-weight:bold; margin-bottom:8px;">File</label>
            <input type="file" name="file" class="form-control" required>
            
            <div style="text-align:right; margin-top:15px;">
                <button type="button" onclick="closeModals()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
    function openTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        const btn = document.querySelector(`button[onclick="openTab('${tabName}')"]`);
        if(btn) btn.classList.add('active');
        
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    }

    function closeModals() {
        document.querySelectorAll('.modal').forEach(el => el.style.display = 'none');
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModals();
        }
    }

    // Activate current tab
    openTab("<?= $activeTab ?>");

    <?php if(isset($_GET['msg'])): ?>
        let msg = "<?= $_GET['msg'] ?>";
        let title = 'Success';
        if(msg == 'submitted') title = 'Pillar sent for approval';
        if(msg == 'member_added') title = 'Member Added';
        if(msg == 'obj_added') title = 'Objective Added';
        if(msg == 'doc_uploaded') title = 'Document Uploaded';
        
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        Toast.fire({icon: 'success', title: title});
    <?php endif; ?>
</script>

</body>
</html>