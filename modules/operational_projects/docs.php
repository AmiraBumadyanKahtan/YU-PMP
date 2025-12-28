<?php
// modules/operational_projects/docs.php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";
require_once "php/doc_BE.php";

if (!Auth::check()) {
    header("Location: ../../error/403.php");
    exit;
};

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// ============================================================
// 1. منطق حالة المشروع
// ============================================================
$lockedStatuses = [1,2, 4, 8, 7]; 
$isLockedStatus = in_array($project['status_id'], $lockedStatuses);

// ============================================================
// 2. تحديد الصلاحيات
// ============================================================
$isManager = ($project['manager_id'] == $_SESSION['user_id']);
$isSuperAdmin = in_array($_SESSION['role_key'], ['super_admin', 'ceo', 'strategy_office', 'pmo_manager']);

if (!userCanInProject($id, 'proj_view_dashboard')) {
    header("Location: ../../error/403.php");
    exit;;
}

$canUpload = ($isManager || $isSuperAdmin || userCanInProject($id, 'pdoc_manage')) && !$isLockedStatus;
$canDelete = ($isManager || $isSuperAdmin || userCanInProject($id, 'pdoc_delete')) && !$isLockedStatus;

// ============================================================
// 3. معالجة الطلبات
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isLockedStatus) {
    die("Action Denied: Project is locked.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc']) && $canUpload) {
    $parentType = $_POST['parent_type'];
    $parentId = ($parentType == 'project') ? $id : $_POST['parent_id'];

    $data = [
        'project_id' => $id,
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'parent_type' => $parentType, 
        'parent_id' => $parentId 
    ];

    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $res = uploadProjectDocument($data, $_FILES['file']);
        if($res['ok']) { header("Location: docs.php?id=$id&msg=uploaded"); exit; } 
        else { $error = $res['error']; }
    } else {
        $error = "Please select a file.";
    }
}

if (isset($_GET['delete_doc']) && $canDelete) {
    deleteDocument($_GET['delete_doc']);
    header("Location: docs.php?id=$id&msg=deleted");
    exit;
}

// ============================================================
// 4. جلب البيانات
// ============================================================
$docs = getProjectDocuments($id); 

if ($isManager || $isSuperAdmin) {
    $tasks = getAllProjectTasks($id);
} else {
    $tasks = getUserProjectTasks($id, $_SESSION['user_id']); 
}

$milestones = getProjectMilestones($id);
$risks = getProjectRisks($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documents - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- General Layout --- */
        body { background-color: #f8f9fa; font-family: 'Varela Round', sans-serif; color: #2d3436; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* --- Header Section --- */
        .page-header-flex { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .page-title h3 { margin: 0; color: #2d3436; font-weight: 800; font-size: 1.75rem; letter-spacing: -0.5px; }
        .page-title p { margin: 6px 0 0; color: #636e72; font-size: 0.95rem; }

        /* --- Buttons --- */
        .btn-grad {
            font-family: 'Varela Round', sans-serif;
            background: linear-gradient(135deg, #ff8c00 0%, #e67e00 100%);
            color: white; border: none; padding: 12px 25px; border-radius: 50px;
            font-weight: 700; cursor: pointer; transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.25);
            font-size: 0.95rem; text-decoration: none;
        }
        .btn-grad:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 140, 0, 0.35); color: #fff; }

        /* --- Locked Alert --- */
        .locked-banner { 
            background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; 
            padding: 16px; border-radius: 12px; margin-bottom: 30px; 
            display: flex; align-items: center; gap: 12px; font-size: 0.95rem; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* --- Doc Grid --- */
        .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        .doc-card { 
            font-family: 'Varela Round', sans-serif;
            background: #fff; border-radius: 16px; padding: 25px; 
            border: 1px solid #f1f2f6; position: relative; 
            transition: all 0.3s ease; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            display: flex; flex-direction: column; height: 100%;
            box-sizing: border-box;
        }
        .doc-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); border-color: #ffcc80; }
        
        .doc-header { display: flex; align-items: flex-start; gap: 15px; margin-bottom: 15px; }
        
        .icon-wrapper {
            width: 50px; height: 50px; border-radius: 12px; display: flex; 
            align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0;
        }
        
        .doc-content { overflow: hidden; width: 100%; }
        .doc-title { font-weight: 700; color: #2d3436; font-size: 1rem; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .doc-desc { font-size: 0.85rem; color: #b2bec3; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; margin-bottom: 8px; }

        .tag-badge { 
            display: inline-block; font-size: 0.7rem; padding: 3px 10px; border-radius: 20px; 
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .tag-project { background: #e3f2fd; color: #3498db; }
        .tag-milestone { background: #e8f5e9; color: #2ecc71; }
        .tag-task { background: #fff3e0; color: #ff8c00; }
        .tag-risk { background: #ffebee; color: #e74c3c; }

        .doc-footer { margin-top: auto; padding-top: 15px; border-top: 1px dashed #eee; font-size: 0.8rem; color: #636e72; display: flex; justify-content: space-between; align-items: center; }
        
        .actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn-download { 
            font-family: 'Varela Round', sans-serif;
            flex: 1; text-align: center; padding: 10px; background: #f8f9fa; color: #2d3436; 
            border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 700; 
            transition: 0.2s; border: 1px solid #eee; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-download:hover { background: #2d3436; color: #fff; border-color: #2d3436; }
        
        .btn-del { 
            width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; 
            background: #fff0f0; color: #c0392b; border-radius: 8px; cursor: pointer; transition: 0.2s;
            text-decoration: none; font-size: 0.9rem;
        }
        .btn-del:hover { background: #c0392b; color: #fff; }

        /* --- Modal Styling --- */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(45, 52, 54, 0.6); backdrop-filter: blur(4px); }
        .modal-content { 
            background-color: #fff; margin: 5% auto; padding: 0; border-radius: 16px; 
            width: 500px; max-width: 90%; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); 
            animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        .modal-header { padding: 20px 25px; border-bottom: 1px solid #f1f2f6; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { margin: 0; color: #2d3436; font-size: 1.2rem; font-weight: 800; }
        .close-btn { font-size: 1.5rem; color: #b2bec3; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: #c0392b; }

        .modal-body { padding: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 700; color: #636e72; font-size: 0.9rem; }
        .form-input, .form-select, .form-textarea { 
            width: 100%; padding: 12px 15px; border: 2px solid #f1f2f6; border-radius: 10px; 
            font-family: inherit; font-size: 0.95rem; box-sizing: border-box; transition: 0.2s; color: #2d3436; background: #fdfdfd;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: #ff8c00; background: #fff; outline: none; }
        .hidden-select { display: none; margin-top: 10px; }

        .btn-upload-submit { 
            font-family: 'Varela Round', sans-serif;
            width: 100%; padding: 12px; background: linear-gradient(135deg, #ff8c00 0%, #e67e00 100%); 
            color: #fff; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; 
            margin-top: 10px; transition: 0.2s;
        }
        .btn-upload-submit:hover { box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3); transform: translateY(-1px); }

        .empty-state { grid-column: 1/-1; text-align: center; padding: 60px; background: #fff; border-radius: 16px; border: 2px dashed #e0e0e0; color: #b2bec3; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <?php if ($isLockedStatus): ?>
        <div class="locked-banner">
            <i class="fa-solid fa-lock"></i>
            <div>
                Project is currently <strong><?= ($project['status_id'] == 4 ? 'Rejected' : ($project['status_id'] == 8 ? 'Completed' : 'Locked')) ?></strong>.
                Modifications disabled.
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div style="background:#ffebee; color:#c0392b; padding:15px; border-radius:10px; margin-bottom:25px; border:1px solid #ffcdd2;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="page-header-flex">
        <div class="page-title">
            <h3>Project Documents</h3>
            <p>Manage all files, reports, and attachments.</p>
        </div>
        
        <?php if ($canUpload): ?>
            <button onclick="openModal()" class="btn-grad">
                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
            </button>
        <?php endif; ?>
    </div>

    <div class="doc-grid">
        <?php foreach ($docs as $d): ?>
            <?php 
                $ext = strtolower($d['file_type']);
                $iconClass = 'fa-file'; 
                $iconBg = '#f1f2f6'; $iconColor = '#636e72';

                if(in_array($ext, ['pdf'])) { $iconClass='fa-file-pdf'; $iconBg='#ffebee'; $iconColor='#e74c3c'; }
                elseif(in_array($ext, ['doc','docx'])) { $iconClass='fa-file-word'; $iconBg='#e3f2fd'; $iconColor='#3498db'; }
                elseif(in_array($ext, ['xls','xlsx'])) { $iconClass='fa-file-excel'; $iconBg='#e8f5e9'; $iconColor='#27ae60'; }
                elseif(in_array($ext, ['jpg','jpeg','png'])) { $iconClass='fa-file-image'; $iconBg='#fff3e0'; $iconColor='#f39c12'; }
                elseif(in_array($ext, ['zip','rar'])) { $iconClass='fa-file-zipper'; $iconBg='#f3e5f5'; $iconColor='#8e44ad'; }
                
                $tagClass = 'tag-project'; $tagName = 'Project File';
                if ($d['parent_type'] == 'milestone') { $tagClass = 'tag-milestone'; $tagName = 'Milestone'; }
                if ($d['parent_type'] == 'task') { $tagClass = 'tag-task'; $tagName = 'Task'; }
                if ($d['parent_type'] == 'risk') { $tagClass = 'tag-risk'; $tagName = 'Risk'; }
            ?>
            <div class="doc-card">
                <div class="doc-header">
                    <div class="icon-wrapper" style="background: <?= $iconBg ?>; color: <?= $iconColor ?>;">
                        <i class="fa-solid <?= $iconClass ?>"></i>
                    </div>
                    <div class="doc-content">
                        <div class="doc-title" title="<?= htmlspecialchars($d['title']) ?>">
                            <?= htmlspecialchars($d['title']) ?>
                        </div>
                        <span class="tag-badge <?= $tagClass ?>"><?= $tagName ?></span>
                    </div>
                </div>

                <div class="doc-desc">
                    <?= htmlspecialchars($d['description']) ?>
                </div>

                <div class="doc-footer">
                    <span><i class="fa-regular fa-calendar"></i> <?= date('d M', strtotime($d['uploaded_at'])) ?></span>
                    <span><i class="fa-solid fa-server"></i> <?= formatSizeUnits($d['file_size']) ?></span>
                </div>

                <div class="actions">
                    <a href="<?= BASE_URL . $d['file_path'] ?>" target="_blank" class="btn-download">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                    
                    <?php if($canDelete): ?>
                        <a href="?id=<?= $id ?>&delete_doc=<?= $d['id'] ?>" class="btn-del" onclick="return confirm('Are you sure you want to delete this file?')" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($docs)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open" style="font-size:4rem; margin-bottom:15px; display:block;"></i>
                <h3>No Documents Yet</h3>
                <p>Upload files related to tasks, milestones, or the project in general.</p>
            </div>
        <?php endif; ?>
    </div>

</div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Upload Document</h3>
            <span onclick="closeModal()" class="close-btn">&times;</span>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="upload_doc" value="1">
                
                <div class="form-group">
                    <label class="form-label">Document Title</label>
                    <input type="text" name="title" required class="form-input" placeholder="e.g. Technical Specifications">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Related To</label>
                    <select name="parent_type" id="parentTypeSelect" class="form-select" onchange="toggleParentSelect()">
                        <option value="project">General Project File</option>
                        
                        <?php if(!empty($milestones)): ?>
                            <option value="milestone">Milestone</option>
                        <?php endif; ?>
                        
                        <option value="task">Task</option>

                        <?php if(!empty($risks)): ?>
                            <option value="risk">Risk</option>
                        <?php endif; ?>
                    </select>

                    <?php if(!empty($milestones)): ?>
                    <select name="parent_id_milestone" id="select_milestone" class="form-select hidden-select">
                        <option value="">-- Select Milestone --</option>
                        <?php foreach($milestones as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>

                    <select name="parent_id_task" id="select_task" class="form-select hidden-select">
                        <option value="">-- Select Task --</option>
                        <?php foreach($tasks as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php if(!empty($risks)): ?>
                    <select name="parent_id_risk" id="select_risk" class="form-select hidden-select">
                        <option value="">-- Select Risk --</option>
                        <?php foreach($risks as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                    
                    <input type="hidden" name="parent_id" id="finalParentId">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea" placeholder="Brief details about the file..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <input type="file" name="file" required class="form-input" style="padding:10px;">
                    <small style="color:#b2bec3; font-size:0.75rem; margin-top:5px; display:block;">Supported: PDF, Word, Excel, Images (Max 10MB)</small>
                </div>
                
                <button type="submit" class="btn-upload-submit">Start Upload</button>
            </div>
        </form>
    </div>
</div>

<script src="js/docs.js"></script>
<script>
    function openModal() { document.getElementById('uploadModal').style.display = 'block'; }
    function closeModal() { document.getElementById('uploadModal').style.display = 'none'; }
    
    function toggleParentSelect() {
        var els = document.querySelectorAll('.hidden-select');
        els.forEach(function(el) { el.style.display = 'none'; });
        
        var type = document.getElementById('parentTypeSelect').value;
        
        if(type === 'milestone') {
            var el = document.getElementById('select_milestone'); 
            if(el) el.style.display = 'block';
        } 
        else if(type === 'task') {
            var el = document.getElementById('select_task'); 
            if(el) el.style.display = 'block';
        } 
        else if(type === 'risk') {
            var el = document.getElementById('select_risk'); 
            if(el) el.style.display = 'block';
        }
    }

    document.querySelector('form').addEventListener('submit', function(e) {
        var type = document.getElementById('parentTypeSelect').value;
        var finalInput = document.getElementById('finalParentId');
        
        if (type === 'project') {
            finalInput.value = <?= $id ?>;
        } else if (type === 'milestone') {
            var el = document.getElementById('select_milestone');
            if(el) finalInput.value = el.value;
        } else if (type === 'task') {
            var el = document.getElementById('select_task');
            if(el) finalInput.value = el.value;
        } else if (type === 'risk') {
            var el = document.getElementById('select_risk');
            if(el) finalInput.value = el.value;
        }
        
        if (type !== 'project' && !finalInput.value) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Missing Selection', text: 'Please select the related item from the list.'});
        }
    });

    window.onclick = function(event) {
        if (event.target == document.getElementById('uploadModal')) {
            closeModal();
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
        if(msg == 'uploaded') Toast.fire({icon: 'success', title: 'Document Uploaded Successfully'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Document Deleted'});
    <?php endif; ?>
</script>

</body>
</html>