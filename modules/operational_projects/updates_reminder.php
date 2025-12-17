<?php
// modules/operational_projects/updates_reminder.php
require_once "php/updates_reminder_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Updates - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/updates_reminder.css">
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

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'sent'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'success', title: 'Update Sent!', text: 'Your progress update has been submitted successfully.', timer: 2000, showConfirmButton: false });
            });
        </script>
    <?php endif; ?>

    <?php if ($canSubmit): ?>
    <div class="composer-card">
        <div class="composer-header">
            <div style="width:40px; height:40px; background:#2c3e50; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-pen-nib"></i>
            </div>
            <h3 style="margin:0;">Submit Weekly Progress</h3>
        </div>
        <form method="POST">
            <div class="form-row">
                <div style="width: 140px;">
                    <label class="form-label">Current Progress</label>
                    <div style="position: relative;">
                        <input type="number" value="<?= $project['progress_percentage'] ?>" class="form-input" readonly style="font-weight:bold; color:#2c3e50; padding-right: 25px;">
                        <span style="position: absolute; right: 10px; top: 12px; color: #888;">%</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Note</label>
                    <input type="text" class="form-input" placeholder="Progress is calculated automatically from tasks/milestones." disabled style="background:transparent; border:none; color:#999; font-style:italic;">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label class="form-label">Detailed Description</label>
                <textarea name="description" class="form-input" placeholder="What has been achieved? Any blockers? What is the plan for next week?" required></textarea>
            </div>
            <div style="text-align:right;">
                <button type="submit" name="submit_update" class="btn-send">
                    Post Update <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <h3 style="margin-bottom:25px; color:#2c3e50; padding-left:10px; border-left:4px solid #ff8c00; line-height:1;">
        Update History
    </h3>
    
    <div class="timeline-section">
        <?php foreach ($history as $upd): ?>
            <div class="timeline-item">
                <div class="timeline-marker">
                    <?= $upd['progress_percent'] ?>%
                </div>
                
                <div class="timeline-content">
                    <div class="t-header">
                        <div class="t-date">
                            <i class="fa-regular fa-clock"></i> 
                            <?= date('d M Y', strtotime($upd['created_at'])) ?>
                            <span style="color:#ccc;">|</span>
                            <?= date('h:i A', strtotime($upd['created_at'])) ?>
                        </div>
                        <span class="t-status st-<?= $upd['status'] ?>"><?= ucfirst($upd['status']) ?></span>
                    </div>
                    <div class="t-body">
                        <?= nl2br(htmlspecialchars($upd['description'])) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(empty($history)): ?>
            <div class="empty-timeline">
                <i class="fa-solid fa-timeline"></i>
                <h3>No Updates Yet</h3>
                <p>Once the project manager submits an update, it will appear here on the timeline.</p>
            </div>
        <?php endif; ?>
    </div>

</div>
</div>

</body>
</html>