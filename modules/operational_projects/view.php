<?php
// modules/operational_projects/view.php
require_once "php/view_BE.php";

// --- جلب آخر تعليق "إرجاع" ---
$lastReturnData = null;
if ($isReturned) {
    $db = Database::getInstance()->pdo();
    $stmtReturn = $db->prepare("
        SELECT aa.comments, u.full_name_en, aa.created_at
        FROM approval_actions aa
        JOIN approval_instances ai ON ai.id = aa.approval_instance_id
        JOIN users u ON u.id = aa.reviewer_user_id
        WHERE ai.entity_type_id = 3 
          AND ai.entity_id = ? 
          AND aa.decision = 'returned'
        ORDER BY aa.id DESC LIMIT 1
    ");
    $stmtReturn->execute([$id]);
    $lastReturnData = $stmtReturn->fetch();
}
//checkAndNotifyDelays($id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($project['name']) ?> - Overview</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/View.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
       
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <?php if (isset($error)): ?>
        <div style="background:#ffebee; color:#c0392b; padding:15px; border-radius:10px; margin-bottom:25px; font-weight:600; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="page-header-flex">
        <div class="page-title">
            <h3>Project Overview</h3>
            <p>Comprehensive dashboard for project details, status, and approvals.</p>
        </div>
        
        <?php 
            // تحديد لون حالة المشروع
            $sId = $project['status_id'];
            $dotColor = '#bdc3c7'; // Default Grey
            if($sId == 5 || $sId == 8) $dotColor = '#27ae60'; // Green
            if($sId == 6) $dotColor = '#3498db'; // Blue (Keep only for in-progress indication if needed, or change to orange)
            if($sId == 6) $dotColor = '#ff8c00'; // Let's make In Progress Orange to match theme
            if($sId == 4) $dotColor = '#c0392b'; // Red
            if($sId == 2) $dotColor = '#f39c12'; // Yellow/Orange
        ?>
        <div class="status-badge-header">
            <span class="status-dot" style="background-color: <?= $dotColor ?>;"></span>
            <?= htmlspecialchars($project['status_name'] ?? 'Unknown') ?>
        </div>
    </div>

    <?php if ($isReturned && $lastReturnData): ?>
        <div class="return-card">
            <div style="font-size:2rem; color:#f1c40f;"><i class="fa-solid fa-rotate-left"></i></div>
            <div>
                <h3 style="margin:0 0 10px 0; color:#2d3436; font-size:1.1rem;">Returned for Modification</h3>
                <p style="margin:0 0 15px 0; color:#636e72; line-height:1.6;">"<?= nl2br(htmlspecialchars($lastReturnData['comments'])) ?>"</p>
                <div style="font-size:0.85rem; color:#b2bec3; font-weight:600;">
                    Reviewer: <span style="color:#2d3436;"><?= htmlspecialchars($lastReturnData['full_name_en']) ?></span> • 
                    <?= date('d M Y, h:i A', strtotime($lastReturnData['created_at'])) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($tracker)): ?>
    <div class="workflow-card">
        <div style="text-align:center; margin-bottom:25px; color:#2d3436; font-weight:800; font-size:1.1rem; text-transform:uppercase; letter-spacing:1px;">
            Approval Process
        </div>
        <div class="workflow-track">
            <?php foreach($tracker as $step): 
                $nodeClass = '';
                $icon = 'fa-minus'; // Default
                
                if($step['status_visual'] == 'approved') { $nodeClass = 'completed'; $icon = 'fa-check'; }
                elseif($step['status_visual'] == 'rejected') { $nodeClass = 'rejected'; $icon = 'fa-xmark'; }
                elseif($step['status_visual'] == 'returned') { $nodeClass = 'returned'; $icon = 'fa-rotate-left'; }
                elseif($step['status_visual'] == 'pending') { $nodeClass = 'current'; $icon = 'fa-hourglass-half'; }
                else { $icon = 'fa-circle'; } // Queue
            ?>
            <div class="wf-node <?= $nodeClass ?>">
                <div class="wf-icon-circle"><i class="fa-solid <?= $icon ?>"></i></div>
                <div class="wf-label"><?= htmlspecialchars($step['stage_label']) ?></div>
                <div class="wf-sub">
                    <?php if ($step['reviewer_name']): ?>
                        <?= htmlspecialchars(explode(' ', $step['reviewer_name'])[0]) ?> <?php else: ?>
                        <?= ($nodeClass == 'current') ? 'Pending...' : '' ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="content-grid">
        
        <div class="col-left">
            <div class="art-card">
                <div class="card-head">
                    <div class="head-icon"><i class="fa-solid fa-align-left"></i></div>
                    <div class="head-title">About Project</div>
                </div>
                <?php if (!empty($project['description'])): ?>
                    <div class="desc-text">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </div>
                <?php else: ?>
                    <div style="color:#b2bec3; font-style:italic; text-align:center; padding:20px;">No description provided.</div>
                <?php endif; ?>
            </div>

            <div class="art-card">
                <div class="card-head">
                    <div class="head-icon"><i class="fa-solid fa-bullseye"></i></div>
                    <div class="head-title">Strategic Objectives</div>
                </div>
                
                <div style="margin-bottom:20px;">
                    <?php foreach($objectives as $obj): ?>
                        <div class="obj-item">
                            <i class="fa-solid fa-crosshairs"></i>
                            <?= htmlspecialchars($obj['objective_text']) ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($objectives)): ?>
                        <div style="text-align:center; padding:30px; color:#b2bec3; border:2px dashed #f1f2f6; border-radius:10px;">
                            No objectives defined yet.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isEditableStatus && $canEditBasic): ?>
                    <form method="POST" style="display:flex; gap:10px;">
                        <input type="text" name="new_objective" required placeholder="Type a new objective..." class="modern-input">
                        <button type="submit" class="btn-grad" style="padding:12px 20px;"><i class="fa-solid fa-plus"></i></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-right">
            
            <div class="art-card">
                <div class="card-head">
                    <div class="head-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div class="head-title">Project Files</div>
                </div>
                
                <div style="margin-bottom:25px;">
                    <?php foreach($supportDocs as $doc): ?>
                        <div class="doc-row">
                            <div class="doc-main">
                                <div class="doc-icon"><i class="fa-solid fa-file-pdf"></i></div>
                                <div>
                                    <div style="font-weight:700; color:#2d3436; font-size:0.9rem;"><?= htmlspecialchars($doc['title']) ?></div>
                                    <div class="doc-meta"><?= formatSizeUnits($doc['file_size']) ?> • <?= date('d M', strtotime($doc['uploaded_at'])) ?></div>
                                </div>
                            </div>
                            <div class="doc-actions">
                                <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank"><i class="fa-solid fa-cloud-arrow-down"></i></a>
                                <?php if ($isEditableStatus && $canManageDocs): ?>
                                    <a href="?id=<?= $id ?>&delete_doc=<?= $doc['id'] ?>" onclick="return confirm('Delete?')" class="del"><i class="fa-solid fa-trash-can"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($supportDocs)): ?>
                        <div style="text-align:center; color:#b2bec3; padding:10px;">No documents attached.</div>
                    <?php endif; ?>
                </div>

                <?php if ($isEditableStatus && $canManageDocs): ?>
                    <form method="POST" enctype="multipart/form-data" style="border-top:1px solid #f1f2f6; padding-top:20px;">
                        <input type="text" name="doc_title" required placeholder="File Name" class="modern-input" style="margin-bottom:10px;">
                        <div style="position:relative;">
                            <input type="file" name="file" id="fileInp" required style="position:absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                            <div class="btn-upload-box"><i class="fa-solid fa-cloud-arrow-up"></i> Click to Upload File</div>
                        </div>
                        <input type="hidden" name="upload_support_doc" value="1">
                        <button type="submit" class="btn-grad" style="width:100%; justify-content:center; margin-top:10px;">Upload</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($isEditableStatus && $canSubmit): ?>
                <div class="art-card" style="text-align:center; border:2px solid #ff8c00; background:#fffbf0;">
                    <div style="margin-bottom:15px; color:#d35400; font-weight:600;">Ready to proceed?</div>
                    <?php if ($hasDocuments): ?>
                        <form method="POST">
                            <button type="submit" name="submit_approval" class="btn-grad" style="width:100%; justify-content:center; font-size:1rem; padding:15px;" onclick="return confirm('Confirm submission?')">
                                <i class="fa-solid fa-paper-plane"></i> Submit for Approval
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn-grad" style="width:100%; justify-content:center; background:#bdc3c7; cursor:not-allowed; box-shadow:none;">
                            <i class="fa-solid fa-lock"></i> Attach Docs First
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <script>
        const msg = "<?= $_GET['msg'] ?>";
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        if(msg == 'objective_added') Toast.fire({icon: 'success', title: 'Objective Added'});
        if(msg == 'doc_uploaded') Toast.fire({icon: 'success', title: 'File Uploaded'});
        if(msg == 'doc_deleted') Toast.fire({icon: 'success', title: 'File Deleted'});
        if(msg == 'submitted') Swal.fire({icon: 'success', title: 'Submitted!', text: 'Project sent for approval.'});
    </script>
<?php endif; ?>

</body>
</html>