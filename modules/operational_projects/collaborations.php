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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php include "project_header_inc.php"; ?>

    <div class="content-card">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0; color:#333;"><i class="fa-solid fa-handshake"></i> Resource Requests</h3>
            <?php if(userCanInProject($id, 'manage_project_team')): ?>
                <a href="request_collab.php?id=<?= $id ?>" class="btn-primary">Request Resource</a>
            <?php endif; ?>
        </div>
        <table class="modern-table">
            <thead>
                <tr><th>Department</th><th>Reason</th><th>Requested</th><th>Status</th><th>Assigned</th></tr>
            </thead>
            <tbody>
                <?php if(empty($requests)): ?><tr><td colspan="5" align="center" style="padding:30px; color:#999;">No requests found.</td></tr><?php endif; ?>
                <?php foreach($requests as $r): ?>
                <tr>
                    <td><i class="fa-solid fa-building" style="color:#bdc3c7;"></i> <?= htmlspecialchars($r['dept_name']) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['reason'])) ?></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                    <td><span class="status-badge status-<?= $r['status_id'] ?>"><?= $r['status_name'] ?></span></td>
                    <td><?= $r['assigned_user'] ? '<i class="fa-solid fa-check" style="color:green"></i> '.$r['assigned_user'] : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</div>
</body>
</html>