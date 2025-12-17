<?php
// modules/operational_projects/docs.php
require_once "php/doc_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documents - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/docs.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <style>
        .doc-desc { font-size: 0.85rem; color: #7f8c8d; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 38px; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <?php if (isset($error)): ?>
        <div style="background:#fee2e2; color:#b91c1c; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #fca5a5;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($canEdit): ?>
    <div style="margin-bottom: 25px; text-align: right;">
        <button onclick="openModal()" class="btn-primary">
            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Document
        </button>
    </div>
    <?php endif; ?>

    <div class="doc-grid">
        <?php foreach ($docs as $d): ?>
            <?php 
                $ext = strtolower($d['file_type']);
                $iconClass = 'fa-file'; $iconColor = '#7f8c8d';
                
                if(in_array($ext, ['pdf'])) { $iconClass='fa-file-pdf'; $iconColor='#e74c3c'; }
                elseif(in_array($ext, ['doc','docx'])) { $iconClass='fa-file-word'; $iconColor='#3498db'; }
                elseif(in_array($ext, ['xls','xlsx'])) { $iconClass='fa-file-excel'; $iconColor='#27ae60'; }
                elseif(in_array($ext, ['jpg','jpeg','png'])) { $iconClass='fa-file-image'; $iconColor='#f39c12'; }
                
                $relationText = "General File";
                if ($d['parent_type'] == 'milestone') $relationText = "Milestone Linked";
                if ($d['parent_type'] == 'task') $relationText = "Task Linked";
                if ($d['parent_type'] == 'risk') $relationText = "Risk Linked";
            ?>
            <div class="doc-card">
                <div class="doc-top">
                    <div class="doc-icon-box">
                        <i class="fa-solid <?= $iconClass ?>" style="color: <?= $iconColor ?>;"></i>
                    </div>
                    <div class="doc-info">
                        <div class="doc-title" title="<?= htmlspecialchars($d['title']) ?>">
                            <?= htmlspecialchars($d['title']) ?>
                        </div>
                        <span class="badge-link"><?= $relationText ?></span>
                        <div class="doc-desc">
                            <?= htmlspecialchars($d['description']) ?>
                        </div>
                    </div>
                </div>

                <div class="doc-meta">
                    <div class="doc-meta-row">
                        <span><i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($d['uploaded_at'])) ?></span>
                        <span><?= formatSizeUnits($d['file_size']) ?></span>
                    </div>
                    <div class="doc-meta-row">
                        <span><i class="fa-solid fa-user"></i> <?= htmlspecialchars($d['uploader_name']) ?></span>
                    </div>
                </div>

                <div class="action-row">
                    <a href="<?= BASE_URL . $d['file_path'] ?>" target="_blank" class="btn-download">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                    <?php if($canEdit): ?>
                        <a href="?id=<?= $id ?>&delete_doc=<?= $d['id'] ?>" class="btn-delete-icon" onclick="return confirm('Delete this file?')" title="Delete">
                            <i class="fa-solid fa-trash-can"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <?php if (empty($docs)): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:60px; color:#999; border:2px dashed #eee; border-radius:12px; background:#fff;">
                <i class="fa-solid fa-folder-open" style="font-size:3rem; margin-bottom:15px; color:#e0e0e0;"></i>
                <p>No documents uploaded yet.</p>
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
                    <input type="text" name="title" required class="form-input" placeholder="e.g. Project Charter">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Related To</label>
                    <select name="parent_type" id="parentTypeSelect" class="form-select" onchange="toggleParentSelect()">
                        <option value="project">General Project File</option>
                        <option value="milestone">Milestone</option>
                        <option value="task">Task</option>
                        <option value="risk">Risk</option>
                    </select>

                    <select name="parent_id_milestone" id="select_milestone" class="form-select hidden-select">
                        <option value="">-- Select Milestone --</option>
                        <?php foreach($milestones as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="parent_id_task" id="select_task" class="form-select hidden-select">
                        <option value="">-- Select Task --</option>
                        <?php foreach($tasks as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select name="parent_id_risk" id="select_risk" class="form-select hidden-select">
                        <option value="">-- Select Risk --</option>
                        <?php foreach($risks as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="hidden" name="parent_id" id="finalParentId">
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-textarea"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select File</label>
                    <input type="file" name="file" required class="form-input" style="padding:8px;">
                    <small style="color:#888; font-size:0.8rem; margin-top:5px; display:block;">Allowed: PDF, Word, Excel, Images</small>
                </div>
                
                <div style="text-align:right; margin-top:20px;">
                    <button type="submit" class="btn-primary" style="width:100%;">Upload Document</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script src="js/docs.js"></script>
<script>
    // قبل الإرسال، نضع القيمة الصحيحة في finalParentId
    document.querySelector('form').addEventListener('submit', function(e) {
        var type = document.getElementById('parentTypeSelect').value;
        var finalInput = document.getElementById('finalParentId');
        
        if (type === 'project') {
            finalInput.value = <?= $id ?>;
        } else if (type === 'milestone') {
            finalInput.value = document.getElementById('select_milestone').value;
        } else if (type === 'task') {
            finalInput.value = document.getElementById('select_task').value;
        } else if (type === 'risk') {
            finalInput.value = document.getElementById('select_risk').value;
        }
        
        if (type !== 'project' && !finalInput.value) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Missing Selection', text: 'Please select the related item from the dropdown.'});
        }
    });
    <?php if(isset($_GET['msg'])): ?>
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        const msg = "<?= $_GET['msg'] ?>";
        if(msg == 'uploaded') Toast.fire({icon: 'success', title: 'Document Uploaded'});
        if(msg == 'deleted') Toast.fire({icon: 'success', title: 'Document Deleted'});
    <?php endif; ?>
</script>

</body>
</html>