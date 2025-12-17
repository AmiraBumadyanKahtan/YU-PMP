<?php
// modules/pillars/view.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$pillar = getPillarById($id);

if (!$pillar) die("Pillar not found");

// تشغيل التحديث التلقائي للحالة
// (موجود لديك، لا يحتاج تعديل)
if (function_exists('updatePillarStatusAutomatic')) {
    updatePillarStatusAutomatic($id);
    $pillar = getPillarById($id); 
}

// --- تعريف الصلاحيات ---
$isDraft      = ($pillar['status_id'] == 12 || $pillar['status_id'] == 6); 
$isApproved   = ($pillar['status_id'] == 11);
$isLead       = ($pillar['lead_user_id'] == $_SESSION['user_id']);
$isStrategy   = ($_SESSION['role_key'] == 'strategy_office');
$isSuperAdmin = ($_SESSION['role_key'] == 'super_admin'); 

$canEditBasic = ($isDraft && ($isLead || $isStrategy || $isSuperAdmin));
$canEditContent = ($canEditBasic || $isSuperAdmin); 

// --- معالجة النماذج (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // إضافة هدف (تم تعديله ليقبل النص فقط) ✅
    if (isset($_POST['add_objective']) && $canEditContent) {
        addStrategicObjective($id, $_POST['obj_text']); // الكود يتولد تلقائياً
        header("Location: view.php?id=$id&tab=objectives&msg=obj_added");
        exit;
    }

    // إضافة عضو
    if (isset($_POST['add_member']) && $canEditContent) {
        addPillarMember($id, $_POST['user_id'], $_POST['role_id']);
        header("Location: view.php?id=$id&tab=team&msg=member_added");
        exit;
    }

    // رفع مستند
    if (isset($_POST['upload_doc']) && ($canEditContent || $isLead)) { 
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

    // إرسال للموافقة
    if (isset($_POST['submit_approval']) && $canEditBasic) {
        $teamMembers = getPillarTeam($id);
        $objs = getPillarObjectives($id);
        
        if (count($teamMembers) == 0) {
            $error = "Cannot submit: You must add at least one team member.";
        } elseif (count($objs) == 0) {
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

// --- معالجة الحذف (GET) ---
if (isset($_GET['del_obj']) && $canEditContent) {
    deleteStrategicObjective($_GET['del_obj']);
    header("Location: view.php?id=$id&tab=objectives&msg=obj_deleted");
    exit;
}
if (isset($_GET['del_member']) && $canEditContent) {
    removePillarMember($_GET['del_member']);
    header("Location: view.php?id=$id&tab=team&msg=member_deleted");
    exit;
}
if (isset($_GET['del_doc']) && ($canEditContent || $isLead)) {
    deleteDocument($_GET['del_doc']);
    header("Location: view.php?id=$id&tab=documents&msg=doc_deleted");
    exit;
}

// جلب البيانات
$objectives  = getPillarObjectives($id);
$tracker     = getPillarWorkflowTracker($id);
$team        = getPillarTeam($id);
$initiatives = getPillarInitiatives($id); 
$documents   = getPillarDocuments($id);   
$db = Database::getInstance()->pdo();
$allUsers    = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 ORDER BY full_name_en")->fetchAll();
$pillarRoles = getPillarRoles();

// التاب النشط
$activeTab = $_GET['tab'] ?? 'overview';

// Helper for file size
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* General Styles */
        .pillar-header-card { background: #fff; padding: 25px; border-radius: 12px; margin-bottom: 25px; border-top: 6px solid <?= $pillar['color'] ?>; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .ph-title { margin: 0; font-size: 1.8rem; color: #2c3e50; }
        .ph-meta { color: #7f8c8d; margin-top: 8px; font-size: 0.95rem; }
        
        /* Workflow Bar */
        .workflow-bar { display: flex; justify-content: space-between; margin: 30px 0; position: relative; }
        .workflow-bar::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 3px; background: #eee; z-index: 0; }
        .wf-step { position: relative; z-index: 1; text-align: center; width: 100%; }
        .wf-dot { width: 30px; height: 30px; background: #ddd; border-radius: 50%; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: bold; border: 3px solid #fff; }
        .wf-label { font-size: 0.85rem; color: #777; }
        .st-approved .wf-dot { background: #2ecc71; }
        .st-pending .wf-dot { background: #f39c12; animation: pulse 2s infinite; }
        .st-rejected .wf-dot { background: #e74c3c; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(243, 156, 18, 0); } 100% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0); } }

        /* Tabs */
        .tabs { display: flex; gap: 10px; border-bottom: 2px solid #eee; margin-bottom: 20px; overflow-x: auto; }
        .tab-btn { padding: 12px 20px; background: none; border: none; cursor: pointer; font-size: 0.95rem; color: #777; border-bottom: 3px solid transparent; transition: 0.3s; font-weight: 600; white-space: nowrap; }
        .tab-btn:hover { color: #3498db; background: #f9f9f9; }
        .tab-btn.active { color: #3498db; border-bottom-color: #3498db; }
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Tables & Lists */
        .modern-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.03); }
        .modern-table th { background: #f8f9fa; padding: 15px; text-align: left; color: #555; border-bottom: 1px solid #eee; }
        .modern-table td { padding: 15px; border-bottom: 1px solid #eee; color: #444; }
        .modern-table tr:last-child td { border-bottom: none; }
        
        .obj-code-badge { background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.85rem; margin-right: 10px; }
        
        .member-card { background: #fff; border: 1px solid #eee; padding: 15px; border-radius: 8px; display: flex; align-items: center; gap: 15px; transition: 0.2s; }
        .member-card:hover { border-color: #3498db; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .member-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; }
        
        .role-badge { font-size: 0.75rem; background: #e3f2fd; color: #1565c0; padding: 2px 8px; border-radius: 12px; font-weight: 600; display: inline-block; margin-top: 3px; }
        .role-chair { background: #fff3e0; color: #e65100; }

        /* Initiatives Card */
        .init-card { background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; transition: 0.2s; border-left: 4px solid #ddd; }
        .init-card:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .init-title { font-weight: bold; font-size: 1.1rem; color: #333; margin-bottom: 5px; }
        .init-meta { font-size: 0.85rem; color: #777; }
        .init-status { font-size: 0.75rem; padding: 3px 8px; border-radius: 10px; color: #fff; font-weight: bold; }
        .btn-more { padding: 6px 12px; border: 1px solid #3498db; color: #3498db; border-radius: 4px; text-decoration: none; font-size: 0.85rem; transition: 0.2s; }
        .btn-more:hover { background: #3498db; color: #fff; }

        /* Documents */
        .doc-item { display: flex; align-items: center; padding: 15px; background: #fff; border-bottom: 1px solid #eee; gap: 15px; }
        .doc-icon { font-size: 1.5rem; color: #555; }
        .doc-icon.pdf { color: #e74c3c; } .doc-icon.word { color: #3498db; } .doc-icon.excel { color: #27ae60; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
        .modal-content { background-color: #fff; margin: 8% auto; padding: 30px; border-radius: 12px; width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    </style>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="pillar-header-card">
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <div>
                <span style="font-size:0.8rem; background:<?= $pillar['color'] ?>; color:#fff; padding:4px 10px; border-radius:20px; font-weight:bold;">
                    Pillar #<?= $pillar['pillar_number'] ?>
                </span>
                <h1 class="ph-title" style="margin-top:10px;">
                    <?= htmlspecialchars($pillar['name']) ?>
                </h1>
                <div class="ph-meta">
                    <i class="fa-solid fa-user-tie"></i> <strong>Lead:</strong> <?= htmlspecialchars($pillar['lead_name']) ?> &nbsp;|&nbsp; 
                    <i class="fa-regular fa-calendar"></i> <?= $pillar['start_date'] ?> to <?= $pillar['end_date'] ?>
                </div>
            </div>

            <div style="text-align:right;">
                <div style="margin-bottom:15px;">
                    <span style="padding:6px 12px; border-radius:6px; font-weight:bold; font-size:0.9rem; background-color: <?= $pillar['status_color'] ?? '#ccc' ?>; color:#fff;">
                        <?= $pillar['status_name'] ?>
                    </span>
                </div>
                
                <?php if($canEditBasic): ?>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="submit_approval" class="btn-primary" onclick="return confirm('Submit pillar for final approval?')">
                            <i class="fa-solid fa-paper-plane"></i> Submit for Approval
                        </button>
                    </form>
                    <a href="edit.php?id=<?= $id ?>" class="btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
                <?php endif; ?>
                
                <?php if($isSuperAdmin && $isApproved): ?>
                   <div style="margin-top:5px; font-size:0.7rem; color:#e67e22;"><i class="fa-solid fa-shield-halved"></i> Super Admin Override</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if(isset($error)): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #fca5a5;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($tracker)): ?>
    <div class="card" style="margin-bottom:25px;">
        <h4 style="margin:0 0 15px 0; color:#555;">Approval Process</h4>
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
                    <div class="wf-label">
                        <strong><?= $step['stage_name'] ?></strong><br>
                        <span style="color:#888; font-size:0.8rem;"><?= $step['reviewer_name'] ?? 'Pending' ?></span>
                    </div>
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
        <div class="card" style="background:#fff; padding:30px; border-radius:8px;">
            <h3 style="margin-top:0; color:#333;">Description</h3>
            <p style="line-height:1.8; color:#555; font-size:1.05rem;">
                <?= nl2br(htmlspecialchars($pillar['description'])) ?>
            </p>
            
            <div style="margin-top:30px; padding-top:20px; border-top:1px solid #eee;">
                <h4 style="margin-bottom:10px;">Overall Progress</h4>
                <div style="background:#eee; height:12px; border-radius:6px; overflow:hidden;">
                    <div style="height:100%; width:<?= $pillar['progress_percentage'] ?>%; background:<?= $pillar['color'] ?>;"></div>
                </div>
                <div style="text-align:right; font-size:0.9rem; margin-top:5px; color:#777; font-weight:bold;"><?= $pillar['progress_percentage'] ?>%</div>
            </div>
        </div>
    </div>

    <div id="objectives" class="tab-content <?= $activeTab=='objectives'?'active':'' ?>">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Strategic Objectives List</h3>
                <?php if($canEditContent): ?>
                    <button onclick="document.getElementById('addObjModal').style.display='block'" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Add Objective
                    </button>
                <?php endif; ?>
            </div>

            <?php if(empty($objectives)): ?>
                <div style="text-align:center; padding:40px; color:#999; border:2px dashed #eee; border-radius:8px;">
                    <i class="fa-solid fa-bullseye" style="font-size:2rem; margin-bottom:10px; opacity:0.5;"></i>
                    <p>No objectives defined yet.</p>
                </div>
            <?php else: ?>
                <table class="modern-table">
                    <thead><tr><th width="100">Code</th><th>Objective</th><th width="80"></th></tr></thead>
                    <tbody>
                        <?php foreach($objectives as $obj): ?>
                        <tr>
                            <td><span class="obj-code-badge"><?= $obj['objective_code'] ?></span></td>
                            <td><?= htmlspecialchars($obj['objective_text']) ?></td>
                            <td style="text-align:right;">
                                <?php if($canEditContent): ?>
                                    <a href="?id=<?= $id ?>&tab=objectives&del_obj=<?= $obj['id'] ?>" onclick="return confirm('Delete?')" style="color:#e74c3c;">
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
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Pillar Team</h3>
                <?php if($canEditContent): ?>
                    <button onclick="document.getElementById('addMemberModal').style.display='block'" class="btn-primary">
                        <i class="fa-solid fa-user-plus"></i> Add Member
                    </button>
                <?php endif; ?>
            </div>

            <?php if(empty($team)): ?>
                <div style="text-align:center; padding:40px; color:#999; border:2px dashed #eee; border-radius:8px;">
                    <i class="fa-solid fa-users" style="font-size:3rem; margin-bottom:15px; color:#ddd;"></i>
                    <p>No team members added yet.</p>
                </div>
            <?php else: ?>
                <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">
                    <?php foreach($team as $m): ?>
                        <?php $avatar = $m['avatar'] ? BASE_URL.'assets/uploads/avatars/'.$m['avatar'] : BASE_URL.'assets/images/default-avatar.png'; ?>
                        <div class="member-card">
                            <img src="<?= $avatar ?>" class="member-avatar">
                            <div style="flex:1;">
                                <div style="font-weight:bold; color:#333;"><?= htmlspecialchars($m['full_name_en']) ?></div>
                                <div class="role-badge <?= ($m['role_name']=='Chair') ? 'role-chair' : '' ?>">
                                    <?= htmlspecialchars($m['role_name'] ?? 'Member') ?>
                                </div>
                            </div>
                            <?php if($canEditContent): ?>
                                <a href="?id=<?= $id ?>&tab=team&del_member=<?= $m['id'] ?>" onclick="return confirm('Remove?')" style="color:#ccc; transition:0.2s;">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="initiatives" class="tab-content <?= $activeTab=='initiatives'?'active':'' ?>">
        <div class="card">
            <h3 style="margin:0 0 20px 0;">Linked Initiatives</h3>
            
            <?php if(empty($initiatives)): ?>
                <div style="text-align:center; padding:40px; color:#999; border:2px dashed #eee; border-radius:8px;">
                    <i class="fa-solid fa-rocket" style="font-size:2rem; margin-bottom:10px; opacity:0.5;"></i>
                    <p>No initiatives linked to this pillar yet.</p>
                </div>
            <?php else: ?>
                <?php foreach($initiatives as $init): ?>
                    <div class="init-card" style="border-left-color: <?= $init['status_color'] ?? '#ccc' ?>;">
                        <div style="flex:1;">
                            <div class="init-title"><?= htmlspecialchars($init['name']) ?></div>
                            <div class="init-meta">
                                <span style="margin-right:15px;"><i class="fa-solid fa-user"></i> Owner: <?= htmlspecialchars($init['owner_name']) ?></span>
                                <span><i class="fa-regular fa-calendar"></i> <?= $init['start_date'] ?></span>
                            </div>
                        </div>
                        <div style="text-align:right; margin:0 20px;">
                            <span class="init-status" style="background-color: <?= $init['status_color'] ?? '#ccc' ?>;">
                                <?= $init['status_name'] ?>
                            </span>
                            <div style="font-weight:bold; margin-top:5px; color:#555;"><?= $init['progress_percentage'] ?>%</div>
                        </div>
                        <a href="../initiatives/view.php?id=<?= $init['id'] ?>" class="btn-more">More Details &rarr;</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div id="documents" class="tab-content <?= $activeTab=='documents'?'active':'' ?>">
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Pillar Documents</h3>
                <?php if($canEditContent || $isLead): ?>
                    <button onclick="document.getElementById('uploadModal').style.display='block'" class="btn-primary">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload
                    </button>
                <?php endif; ?>
            </div>

            <?php if(empty($documents)): ?>
                <div style="text-align:center; padding:40px; color:#999; border:2px dashed #eee; border-radius:8px;">
                    <i class="fa-solid fa-folder-open" style="font-size:2rem; margin-bottom:10px; opacity:0.5;"></i>
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
                    <div class="doc-item">
                        <i class="fa-solid <?= $iconClass ?> doc-icon"></i>
                        <div style="flex:1;">
                            <div style="font-weight:bold; color:#333;">
                                <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank" style="text-decoration:none; color:inherit;">
                                    <?= htmlspecialchars($doc['title']) ?>
                                </a>
                            </div>
                            <div style="font-size:0.8rem; color:#888;">
                                <?= formatSize($doc['file_size']) ?> • Uploaded by <?= htmlspecialchars($doc['uploader_name']) ?> on <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                            </div>
                        </div>
                        <?php if($canEditContent || ($isLead && $doc['uploaded_by'] == $_SESSION['user_id'])): ?>
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

<div id="addObjModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Add Strategic Objective</h3>
        <form method="POST">
            <input type="hidden" name="add_objective" value="1">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Objective Text</label>
                <textarea name="obj_text" rows="3" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
                <small style="color:#777;">Code will be auto-generated (e.g. OBJ-1.1)</small>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeModals()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>

<div id="addMemberModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Add Team Member</h3>
        <form method="POST">
            <input type="hidden" name="add_member" value="1">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Select User</label>
                <select name="user_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <option value="">-- Choose --</option>
                    <?php foreach($allUsers as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name_en']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Role</label>
                <select name="role_id" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
                    <?php foreach($pillarRoles as $role): ?>
                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeModals()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Add</button>
            </div>
        </form>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-top:0;">Upload Document</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_doc" value="1">
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Title</label>
                <input type="text" name="doc_title" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">Description (Optional)</label>
                <textarea name="doc_desc" rows="2" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;"></textarea>
            </div>
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;">File</label>
                <input type="file" name="file" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px;">
            </div>
            <div style="text-align:right;">
                <button type="button" onclick="closeModals()" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
        
        event.currentTarget.classList.add('active');
    }

    function closeModals() {
        document.querySelectorAll('.modal').forEach(el => el.style.display = 'none');
    }

    const currentTab = "<?= $activeTab ?>";
    const btn = document.querySelector(`button[onclick="openTab('${currentTab}')"]`);
    if(btn) btn.classList.add('active');

    <?php if(isset($_GET['msg'])): ?>
        let msg = "<?= $_GET['msg'] ?>";
        if(msg == 'created_draft') Swal.fire({icon: 'success', title: 'Draft Created', text: 'Please add objectives and members before submitting.'});
        if(msg == 'submitted') Swal.fire({icon: 'success', title: 'Submitted', text: 'Pillar sent for approval.'});
        if(msg == 'member_added') Swal.fire({icon: 'success', title: 'Member Added', timer: 1500, showConfirmButton: false});
        if(msg == 'obj_added') Swal.fire({icon: 'success', title: 'Objective Added', timer: 1500, showConfirmButton: false});
        if(msg == 'doc_uploaded') Swal.fire({icon: 'success', title: 'Uploaded', timer: 1500, showConfirmButton: false});
    <?php endif; ?>
</script>

</body>
</html>