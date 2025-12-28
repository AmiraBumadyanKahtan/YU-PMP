<?php
// modules/operational_projects/collaborations.php
require_once "php/collaborations_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Collaborations - <?= htmlspecialchars($project['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/collaborations.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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
            <i class="fa-solid fa-lock fa-lg"></i>
            <div>
                Project is currently 
                <strong>
                    <?php 
                        if ($project['status_id'] == 4) echo 'Rejected';
                        elseif ($project['status_id'] == 1) echo 'Draft';
                        elseif ($project['status_id'] == 8) echo 'Completed';
                        elseif ($project['status_id'] == 7) echo 'On Hold';
                        else echo 'Pending Review';
                    ?>
                </strong>. 
                New requests are disabled.
            </div>
        </div>
    <?php endif; ?>

    <div class="page-header-flex">
        <div class="page-title">
            <h3>Resource Collaboration</h3>
            <p>Manage inter-departmental resource requests and allocations.</p>
        </div>
        
        <?php if($canRequestCollab): ?>
            <a href="request_collab.php?id=<?= $id ?>" class="btn-primary-pill">
                <i class="fa-solid fa-handshake-angle"></i> Request Resource
            </a>
        <?php endif; ?>
    </div>

    <div class="table-card">
        <div style="overflow-x: auto;">
            <table class="collab-table">
                <thead>
                    <tr>
                        <th width="20%">Department</th>
                        <th width="35%">Reason / Description</th>
                        <th>Requested Date</th>
                        <th>Status</th>
                        <th>Assigned Resource</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($requests)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-paper-plane empty-icon"></i>
                                    <p class="empty-text">No collaboration requests found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($requests as $r): ?>
                        <tr class="collab-row">
                            <td>
                                <div class="dept-name">
                                    <i class="fa-regular fa-building dept-icon"></i> 
                                    <?= htmlspecialchars($r['dept_name']) ?>
                                </div>
                            </td>
                            <td>
                                <div class="reason-text"><?= nl2br(htmlspecialchars($r['reason'])) ?></div>
                            </td>
                            <td>
                                <div class="date-text">
                                    <i class="fa-regular fa-calendar" style="margin-right:5px;"></i>
                                    <?= date('M d, Y', strtotime($r['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $r['status_id'] ?>">
                                    <?= $r['status_name'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if($r['assigned_user']): ?>
                                    <div class="assigned-user">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <?= htmlspecialchars($r['assigned_user']) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color:#cbd5e1; font-style:italic;">- Pending -</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
</body>
</html>