<?php
// modules/operational_projects/view.php
require_once "php/view_BE.php";

// --- جلب آخر تعليق "إرجاع" إذا كان المشروع في حالة Returned ---
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
// التحقق من التأخيرات وإرسال إشعارات
checkAndNotifyDelays($id);

// --- تجهيز البيانات للعرض (دون تعديل الـ BE) ---
// حساب نسبة الميزانية المصروفة
$budget = $project['approved_budget'] > 0 ? $project['approved_budget'] : ($project['budget_max'] > 0 ? $project['budget_max'] : 0);
$spent = $project['spent_budget'] ?? 0;
$budgetPercent = ($budget > 0) ? ($spent / $budget) * 100 : 0;

// تحديد لون الأولوية
$priorityClass = 'p-medium';
if($project['priority'] == 'high') $priorityClass = 'p-high';
if($project['priority'] == 'critical') $priorityClass = 'p-critical';
if($project['priority'] == 'low') $priorityClass = 'p-low';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- General Layout --- */
        body { background-color: #f8f9fa; font-family: 'Varela Round', sans-serif; color: #2c3e50; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* --- Header --- */
        .proj-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-start; }
        .proj-title-group h2 { margin: 0; font-size: 1.8rem; font-weight: 800; color: #1e293b; }
        .proj-code { display: inline-block; background: #e2e8f0; color: #64748b; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; }
        .status-badge-lg { padding: 8px 20px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Status Colors */
        .st-draft { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .st-pending { background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; }
        .st-approved { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
        .st-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
        .st-returned { background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; }
        .st-inprogress { background: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd; }
        .st-completed { background: #ecfccb; color: #3f6212; border: 1px solid #bef264; }

        /* --- Grid Layout for Details --- */
        .details-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 24px; margin-bottom: 30px; }
        
        /* Cards */
        .d-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; position: relative; overflow: hidden; transition: transform 0.2s; }
        .d-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .card-header { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* --- Financials Card (Matching Image) --- */
        .col-financials { grid-column: span 5; }
        .finance-box { text-align: center; background: #fffaf0; border: 1px solid #ffedd5; border-radius: 12px; padding: 25px 15px; margin-bottom: 15px; }
        .finance-label { color: #d97706; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .finance-amount { font-size: 2.2rem; font-weight: 800; color: #ea580c; background: linear-gradient(135deg, #ea580c, #d97706); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .finance-sub { font-size: 1rem; color: #9a3412; font-weight: 600; }
        
        .finance-details { display: flex; justify-content: space-between; font-size: 0.9rem; color: #64748b; margin-top: 15px; border-top: 1px dashed #e2e8f0; padding-top: 15px; }
        .finance-progress { height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: 10px; }
        .finance-bar { height: 100%; background: #ea580c; border-radius: 4px; }

        /* --- Details Card --- */
        .col-details { grid-column: span 7; }
        .info-list { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item { display: flex; flex-direction: column; }
        .info-label { font-size: 0.85rem; color: #94a3b8; font-weight: 600; margin-bottom: 4px; }
        .info-val { font-size: 1rem; color: #1e293b; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        
        /* Priority Badges */
        .p-badge { padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
        .p-medium { background: #eff6ff; color: #3b82f6; }
        .p-high { background: #fff7ed; color: #f97316; }
        .p-critical { background: #fef2f2; color: #ef4444; }
        .p-low { background: #f0fdf4; color: #22c55e; }

        /* --- Progress Section --- */
        .progress-circle-wrap { position: absolute; top: 20px; right: 20px; text-align: center; }
        
        /* --- Alert Box (Returned) --- */
        .return-alert-box { 
            background: #fffbeb; border: 1px solid #fcd34d; border-left: 5px solid #d97706; 
            padding: 20px; border-radius: 8px; margin-bottom: 30px; display: flex; gap: 15px; 
            color: #92400e; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        /* --- Workflow --- */
        .workflow-card { margin-top: 30px; }
        .workflow-bar { display: flex; justify-content: space-between; position: relative; margin-top: 20px; }
        .workflow-bar::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 3px; background: #e2e8f0; z-index: 0; }
        .wf-step { position: relative; z-index: 1; text-align: center; flex: 1; }
        .wf-circle { width: 34px; height: 34px; border-radius: 50%; background: #fff; border: 3px solid #cbd5e0; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; color: #cbd5e0; transition: all 0.3s; }
        .wf-step.approved .wf-circle { background: #22c55e; border-color: #22c55e; color: #fff; box-shadow: 0 0 0 4px #dcfce7; }
        .wf-step.rejected .wf-circle { background: #ef4444; border-color: #ef4444; color: #fff; }
        .wf-step.pending .wf-circle { background: #f59e0b; border-color: #f59e0b; color: #fff; box-shadow: 0 0 0 4px #fef3c7; }
        .wf-label { font-size: 0.85rem; font-weight: 700; color: #64748b; }
        .wf-detail { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }

        /* --- Objectives & Files --- */
        .obj-list { list-style: none; padding: 0; margin: 0; }
        .obj-item { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; display: flex; gap: 12px; align-items: flex-start; }
        .obj-item:last-child { border-bottom: none; }
        .obj-item::before { content: "\f140"; font-family: "Font Awesome 6 Free"; font-weight: 900; color: #f59e0b; margin-top: 3px; }
        
        .file-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; background: #f8fafc; border-radius: 8px; margin-bottom: 10px; border: 1px solid #e2e8f0; transition: 0.2s; }
        .file-item:hover { background: #fff; border-color: #cbd5e1; }
        .file-icon { font-size: 1.2rem; color: #f59e0b; }
        
        /* Buttons */
        .btn-primary { 
            background: linear-gradient(135deg, #ff8c00 0%, #ea580c 100%); 
            color: #fff; border: none; padding: 10px 24px; border-radius: 50px; 
            font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(234, 88, 12, 0.3); }

        .input-group { display: flex; gap: 10px; }
        .form-input { flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; }
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

    <div class="proj-header">
        <div class="proj-title-group">
            <span class="proj-code"><?= htmlspecialchars($project['project_code']) ?></span>
            <h2><?= htmlspecialchars($project['name']) ?></h2>
            <div style="color:#64748b; margin-top:5px; font-size:0.95rem;">
                <i class="fa-solid fa-sitemap"></i> Initiative: <?= htmlspecialchars($project['initiative_name'] ?? 'None') ?>
            </div>
        </div>
        <div>
            <?php 
                // تحديد كلاس الحالة
                $stClass = 'st-draft';
                if($project['status_id'] == 2) $stClass = 'st-pending';
                if($project['status_id'] == 5) $stClass = 'st-approved';
                if($project['status_id'] == 4) $stClass = 'st-rejected';
                if($project['status_id'] == 3) $stClass = 'st-returned';
                if($project['status_id'] == 6) $stClass = 'st-inprogress';
                if($project['status_id'] == 8) $stClass = 'st-completed';
            ?>
            <span class="status-badge-lg <?= $stClass ?>">
                <?= htmlspecialchars($project['status_name'] ?? 'Unknown') ?>
            </span>
        </div>
    </div>

    <?php if ($isReturned && $lastReturnData): ?>
        <div class="return-alert-box">
            <div style="font-size:1.5rem;"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div>
                <h4 style="margin:0 0 5px 0;">Returned for Revision</h4>
                <p style="margin:0 0 8px 0; color:#b45309;">"<?= nl2br(htmlspecialchars($lastReturnData['comments'])) ?>"</p>
                <div style="font-size:0.85rem; opacity:0.8;">
                    By: <?= htmlspecialchars($lastReturnData['full_name_en']) ?> &bull; <?= date('d M Y, h:i A', strtotime($lastReturnData['created_at'])) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="details-grid">
        
        <div class="d-card col-financials">
            <div class="card-header"><i class="fa-solid fa-coins" style="color:#f59e0b;"></i> Financials</div>
            
            <div class="finance-box">
                <div class="finance-label">Approved Budget</div>
                <div class="finance-amount">
                    <?= ($budget > 0) ? number_format($budget) : '0.00' ?> <span class="finance-sub">SAR</span>
                </div>
            </div>

            <div>
                <div style="display:flex; justify-content:space-between; font-weight:700; color:#334155; font-size:0.9rem;">
                    <span>Spent Budget</span>
                    <span><?= number_format($spent, 2) ?> SAR</span>
                </div>
                <div class="finance-progress">
                    <div class="finance-bar" style="width: <?= min($budgetPercent, 100) ?>%"></div>
                </div>
                <div class="finance-details">
                    <span>Range: <?= number_format($project['budget_min']) ?> - <?= number_format($project['budget_max']) ?></span>
                    <span>Utilized: <?= round($budgetPercent, 1) ?>%</span>
                </div>
            </div>
        </div>

        <div class="d-card col-details">
            <div class="card-header"><i class="fa-solid fa-circle-info" style="color:#3b82f6;"></i> Project Details</div>
            
            <div class="progress-circle-wrap">
                <div style="position:relative; width:60px; height:60px; border-radius:50%; background: conic-gradient(#10b981 <?= $project['progress_percentage'] * 3.6 ?>deg, #e2e8f0 0deg); display:flex; align-items:center; justify-content:center;">
                    <div style="width:50px; height:50px; background:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:0.9rem; color:#0f766e;">
                        <?= $project['progress_percentage'] ?>%
                    </div>
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:5px; font-weight:600;">Progress</div>
            </div>

            <div class="info-list">
                <div class="info-item">
                    <span class="info-label">Manager</span>
                    <span class="info-val">
                        <img src="<?= BASE_URL ?>assets/uploads/avatars/default-profile.png" style="width:24px; height:24px; border-radius:50%;"> 
                        <?= htmlspecialchars($project['manager_name'] ?? 'N/A') ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Department</span>
                    <span class="info-val"><i class="fa-regular fa-building" style="color:#94a3b8;"></i> <?= htmlspecialchars($project['department_name'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Start Date</span>
                    <span class="info-val"><i class="fa-regular fa-calendar" style="color:#94a3b8;"></i> <?= $project['start_date'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">End Date</span>
                    <span class="info-val"><i class="fa-regular fa-calendar-check" style="color:#94a3b8;"></i> <?= $project['end_date'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Priority</span>
                    <div><span class="p-badge <?= $priorityClass ?>"><?= ucfirst($project['priority']) ?></span></div>
                </div>
                <div class="info-item">
                    <span class="info-label">Updates</span>
                    <span class="info-val"><i class="fa-solid fa-repeat" style="color:#94a3b8;"></i> <?= ucfirst(str_replace('_', ' ', $project['update_frequency'])) ?></span>
                </div>
            </div>

            <?php if (!empty($project['description'])): ?>
                <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f1f5f9;">
                    <span class="info-label">Description</span>
                    <p style="margin:5px 0 0; font-size:0.9rem; color:#475569; line-height:1.5;">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

    </div> <div class="details-grid">
        <div class="d-card" style="grid-column: span 6;">
            <div class="card-header"><i class="fa-solid fa-bullseye" style="color:#ef4444;"></i> Strategic Objectives</div>
            <ul class="obj-list">
                <?php foreach($objectives as $obj): ?>
                    <li class="obj-item"><?= htmlspecialchars($obj['objective_text']) ?></li>
                <?php endforeach; ?>
                <?php if(empty($objectives)): ?>
                    <li style="color:#94a3b8; font-style:italic; padding:10px;">No objectives added yet.</li>
                <?php endif; ?>
            </ul>
            
            <?php if ($isEditableStatus && $canEditBasic): ?>
                <form method="POST" style="margin-top:20px;" class="input-group">
                    <input type="text" name="new_objective" required placeholder="Add new objective..." class="form-input">
                    <button type="submit" class="btn-primary" style="padding: 8px 15px;"><i class="fa-solid fa-plus"></i></button>
                </form>
            <?php endif; ?>
        </div>

        <div class="d-card" style="grid-column: span 6; border: 2px solid <?= $hasDocuments ? '#f1f5f9' : '#fbbf24' ?>;">
            <div class="card-header">
                <i class="fa-solid fa-paperclip" style="color:#6366f1;"></i> Supporting Documents
                <?php if(!$hasDocuments): ?>
                    <span style="font-size:0.75rem; color:#d97706; background:#fffbeb; padding:2px 8px; border-radius:4px; margin-left:auto;">Required</span>
                <?php endif; ?>
            </div>
            
            <div>
                <?php foreach($supportDocs as $doc): ?>
                    <div class="file-item">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <i class="fa-solid fa-file-lines file-icon"></i>
                            <div>
                                <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($doc['title']) ?></div>
                                <div style="font-size:0.75rem; color:#94a3b8;"><?= date('d M Y', strtotime($doc['uploaded_at'])) ?></div>
                            </div>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <a href="<?= BASE_URL . $doc['file_path'] ?>" target="_blank" style="color:#64748b;"><i class="fa-solid fa-download"></i></a>
                            <?php if ($isEditableStatus && $canManageDocs): ?>
                                <a href="?id=<?= $id ?>&delete_doc=<?= $doc['id'] ?>" onclick="return confirm('Delete?')" style="color:#ef4444;"><i class="fa-solid fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($supportDocs)): ?>
                    <div style="text-align:center; padding:20px; color:#94a3b8; font-size:0.9rem;">No documents attached.</div>
                <?php endif; ?>
            </div>

            <?php if ($isEditableStatus && $canManageDocs): ?>
                <form method="POST" enctype="multipart/form-data" style="margin-top:15px; border-top:1px dashed #e2e8f0; padding-top:15px;">
                    <div class="input-group">
                        <input type="text" name="doc_title" required placeholder="Doc Title" class="form-input">
                        <input type="file" name="file" required class="form-input" style="font-size:0.85rem;">
                        <button type="submit" name="upload_support_doc" class="btn-primary" style="padding: 8px 15px;"><i class="fa-solid fa-upload"></i></button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($tracker)): ?>
    <div class="d-card workflow-card">
        <div class="card-header"><i class="fa-solid fa-route" style="color:#8b5cf6;"></i> Approval Timeline</div>
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
                    <div class="wf-detail">
                        <?php if ($step['reviewer_name']): ?>
                            <?= htmlspecialchars($step['reviewer_name']) ?><br>
                        <?php endif; ?>
                        <?php if ($step['action_date']): ?>
                            <?= date('M d', strtotime($step['action_date'])) ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($isEditableStatus && $canSubmit): ?>
        <div style="margin-top:30px; text-align:right;">
            <?php if ($hasDocuments): ?>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="submit_approval" class="btn-primary" style="font-size:1rem; padding:12px 35px;" onclick="return confirm('Submit project for approval?')">
                        <i class="fa-solid fa-paper-plane"></i> Submit for Approval
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="btn-primary" style="background:#cbd5e0; cursor:not-allowed;" title="Upload documents first">
                    <i class="fa-solid fa-paper-plane"></i> Submit (Attach Docs First)
                </button>
            <?php endif; ?>
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
    </script>
<?php endif; ?>

</body>
</html>