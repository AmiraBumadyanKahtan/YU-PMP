<?php
// modules/operational_projects/view.php
require_once "php/view_BE.php";

// --- [إضافة جديدة] جلب آخر تعليق "إرجاع" إذا كان المشروع في حالة Returned ---
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/view.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* ستايل صندوق التنبيه عند الإرجاع */
        .return-alert-box {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            border-left: 5px solid #ffc107;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .return-icon { font-size: 1.5rem; color: #ffc107; margin-top: 2px; }
        .return-content h4 { margin: 0 0 5px 0; font-size: 1rem; color: #533f03; }
        .return-content p { margin: 0 0 8px 0; line-height: 1.5; }
        .return-meta { font-size: 0.85rem; color: #856404; opacity: 0.8; }

        /* باقي الستايلات السابقة */
        .project-desc { background: #fdfdfd; padding: 15px; border-left: 4px solid #3498db; margin-bottom: 20px; color: #555; line-height: 1.6; border-radius: 4px; }
        .wf-step-detail { font-size: 0.8rem; color: #888; margin-top: 5px; line-height: 1.4; }
        .wf-user { color: #333; font-weight: 600; display: block; }
        .wf-date { font-size: 0.75rem; color: #aaa; display: block; }
        .wf-status-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; margin-bottom: 4px; text-transform: uppercase; }
        .st-approved { background: #d1e7dd; color: #0f5132; }
        .st-pending { background: #fff3cd; color: #856404; }
        .st-rejected { background: #f8d7da; color: #721c24; }
        .st-queue { background: #e9ecef; color: #6c757d; }
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

    <?php if ($isReturned && $lastReturnData): ?>
        <div class="return-alert-box">
            <div class="return-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="return-content">
                <h4>Action Required: Project Returned for Revision</h4>
                <p><strong>Reviewer Comment:</strong> "<?= nl2br(htmlspecialchars($lastReturnData['comments'])) ?>"</p>
                <div class="return-meta">
                    <i class="fa-regular fa-user"></i> Returned by: <?= htmlspecialchars($lastReturnData['full_name_en']) ?> &nbsp;|&nbsp; 
                    <i class="fa-regular fa-clock"></i> <?= date('d M Y, h:i A', strtotime($lastReturnData['created_at'])) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($tracker)): ?>
    <div class="content-card">
        <h3 class="card-title"><i class="fa-solid fa-route"></i> Approval Timeline</h3>
        <div class="workflow-bar">
            <?php foreach($tracker as $step): ?>
                <div class="wf-step <?= $step['status_visual'] ?>">
                    <div class="wf-circle">
                        <?php 
                        if($step['status_visual']=='approved') echo '<i class="fa-solid fa-check"></i>'; 
                        elseif($step['status_visual']=='rejected') echo '<i class="fa-solid fa-xmark"></i>';
                        elseif($step['status_visual']=='returned') echo '<i class="fa-solid fa-rotate-left"></i>';
                        elseif($step['status_visual']=='pending') echo '<i class="fa-solid fa-clock"></i>';
                        else echo '<i class="fa-solid fa-minus"></i>'; 
                        ?>
                    </div>
                    <div class="wf-label"><?= htmlspecialchars($step['stage_label']) ?></div>
                    <div class="wf-step-detail">
                        <?php if ($step['reviewer_name']): ?>
                            <span class="wf-user"><i class="fa-regular fa-user"></i> <?= htmlspecialchars($step['reviewer_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($step['action_date']): ?>
                            <span class="wf-date">
                                <i class="fa-regular fa-calendar-check"></i> <?= date('M d, H:i', strtotime($step['action_date'])) ?>
                            </span>
                        <?php elseif ($step['status_visual'] == 'pending'): ?>
                             <span class="wf-status-badge st-pending">Current Stage</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="content-card">
        <h3 class="card-title" style="margin:0 0 15px 0; border:none;">
            <i class="fa-solid fa-circle-info"></i> Project Overview
        </h3>
        
        <?php if (!empty($project['description'])): ?>
            <div class="project-desc">
                <strong>Description:</strong><br>
                <?= nl2br(htmlspecialchars($project['description'])) ?>
            </div>
        <?php endif; ?>

        <h4 style="margin:15px 0 10px 0; color:#2c3e50; font-size:1rem; border-bottom:1px solid #eee; padding-bottom:5px;">
            <i class="fa-solid fa-bullseye"></i> Strategic Objectives
        </h4>
        <ul class="obj-list">
            <?php foreach($objectives as $obj): ?>
                <li class="obj-item"><?= htmlspecialchars($obj['objective_text']) ?></li>
            <?php endforeach; ?>
            <?php if(empty($objectives)): ?>
                <li style="color:#999; font-style:italic; padding:10px;">No objectives added yet.</li>
            <?php endif; ?>
        </ul>
        
        <?php if (($isDraft || $isReturned) && $canEdit): ?>
            <form method="POST" style="margin-top:20px;" class="input-group">
                <input type="text" name="new_objective" required placeholder="Type a new objective..." class="form-input">
                <button type="submit" class="btn-primary"><i class="fa-solid fa-plus"></i> Add</button>
            </form>
        <?php endif; ?>
    </div>

    

    <div class="content-card" style="border: 2px solid <?= $hasDocuments ? '#eee' : '#f39c12' ?>;">
        <h3 class="card-title">
            <i class="fa-solid fa-paperclip"></i> Supporting Documents 
            <?php if(!$hasDocuments): ?>
                <span style="color:#e74c3c; font-size:0.8rem; margin-left:10px;">(Required for Approval)</span>
            <?php endif; ?>
        </h3>
        
        <div style="margin-bottom: 20px;">
            <?php foreach($supportDocs as $doc): ?>
                <div class="file-item">
                    <div class="file-info">
                        <i class="fa-solid fa-file-lines file-icon"></i>
                        <div>
                            <div style="font-weight:bold; color:#333;"><?= htmlspecialchars($doc['title']) ?></div>
                            <small style="color:#888;">Uploaded on <?= date('d M Y', strtotime($doc['uploaded_at'])) ?> (<?= formatSizeUnits($doc['file_size']) ?>)</small>
                        </div>
                    </div>
                    <div class="file-actions">
                        <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank" title="Download"><i class="fa-solid fa-download"></i></a>
                        <?php if (($isDraft || $isReturned) && $canEdit): ?>
                            <a href="?id=<?= $id ?>&delete_doc=<?= $doc['id'] ?>" onclick="return confirm('Delete document?')" class="delete-icon" title="Delete"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($supportDocs)): ?>
                <div style="background:#fff8e1; color:#d35400; padding:15px; border-radius:8px; text-align:center;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Warning: You must upload at least one document to submit the project.
                </div>
            <?php endif; ?>
        </div>

        <?php if (($isDraft || $isReturned) && $canEdit): ?>
            <form method="POST" enctype="multipart/form-data" style="background:#f9f9f9; padding:15px; border-radius:8px; border:1px dashed #ccc;">
                <h4 style="margin:0 0 10px 0; font-size:0.9rem; color:#555;">Attach New Document</h4>
                <div class="input-group">
                    <input type="text" name="doc_title" required placeholder="Document Title (e.g. Project Charter)" class="form-input">
                    <input type="file" name="file" required class="form-input" style="padding:7px;">
                    <button type="submit" name="upload_support_doc" class="btn-primary"><i class="fa-solid fa-upload"></i> Upload</button>
                </div>
                <input type="hidden" name="upload_support_doc" value="1">
            </form>
        <?php endif; ?>
    </div>

    <?php if (($isDraft || $isReturned) && $canEdit): ?>
        <div class="content-card" style="text-align:right; background:#fef9e7; border:1px solid #f1c40f; border-left: 5px solid #f1c40f;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="text-align:left; color:#7f8c8d;">
                    <i class="fa-solid fa-circle-info"></i> Ensure all objectives are added and documents are attached before submitting.
                </div>
                <form method="POST" style="display:inline;">
                    <?php if ($hasDocuments): ?>
                        <button type="submit" name="submit_approval" class="btn-primary" style="padding:12px 30px; font-size:1rem;" onclick="return confirm('Submit project for approval?')">
                            <i class="fa-solid fa-paper-plane"></i> Submit for Approval
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-disabled" style="padding:12px 30px; font-size:1rem;" title="Upload documents first">
                            <i class="fa-solid fa-ban"></i> Attach Documents First
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    <?php endif; ?>

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
        if(msg == 'doc_uploaded') Toast.fire({icon: 'success', title: 'Document Attached'});
        if(msg == 'doc_deleted') Toast.fire({icon: 'success', title: 'Document Removed'});
        if(msg == 'submitted') Swal.fire({icon: 'success', title: 'Submitted!', text: 'Project sent for approval successfully.'});
        if(msg == 'updated') Toast.fire({icon: 'success', title: 'Project Updated'});
    </script>
<?php endif; ?>

</body>
</html>