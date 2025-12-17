<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";

// صلاحيات الوصول لصفحة التفاصيل
$allowedRoles = ["super_admin", "strategy_office"];
if (!Auth::check() || !in_array($_SESSION['role_key'], $allowedRoles)) {
    die("Access denied");
}

$db = Database::getInstance()->pdo();

// التحقق من وجود ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request");
}

$objective_id = (int) $_GET['id'];

// جلب بيانات الهدف مع الركيزة
$stmt = $db->prepare("
    SELECT 
        o.*,
        p.pillar_number,
        p.name AS pillar_name,
        p.color AS pillar_color,
        p.icon  AS pillar_icon
    FROM strategic_objectives o
    JOIN pillars p ON o.pillar_id = p.id
    WHERE o.id = ?
");
$stmt->execute([$objective_id]);
$objective = $stmt->fetch();

if (!$objective) {
    die("Objective not found");
}

// جلب المبادرات المرتبطة بهذا الهدف
$initStmt = $db->prepare("
    SELECT 
        i.id,
        i.initiative_code,
        i.name,
        i.progress_percentage,
        s.name  AS status_name,
        s.color AS status_color
    FROM initiatives i
    LEFT JOIN initiative_statuses s ON i.status_id = s.id
    WHERE i.strategic_objective_id = ?
    ORDER BY i.created_at DESC
");
$initStmt->execute([$objective_id]);
$initiatives = $initStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Objective Details</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="stylesheet" href="css/objective_view.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <!-- Header + Actions -->
        <div class="page-header-flex objective-header">
            <div>
                <h1>
                    <i class="fa-solid fa-bullseye"></i>
                    <?= htmlspecialchars($objective['objective_code']) ?>
                </h1>
                <p class="objective-subtitle">
                    <?= nl2br(htmlspecialchars($objective['objective_text'])) ?>
                </p>
            </div>

            <div class="objective-actions">
                <a href="list.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to List
                </a>
                <a href="edit.php?id=<?= $objective['id'] ?>" class="btn-edit">
                    <i class="fa-solid fa-pen"></i> Edit
                </a>
                <a href="delete.php?id=<?= $objective['id'] ?>" 
                   class="btn-delete"
                   onclick="return confirm('Are you sure you want to delete this objective? This action cannot be undone.');">
                    <i class="fa-solid fa-trash"></i> Delete
                </a>
            </div>
        </div>

        <!-- Info Summary -->
        <div class="summary-row">
            <div class="summary-card">
                <div class="summary-label">Objective Code</div>
                <div class="summary-value">
                    <i class="fa-solid fa-code"></i>
                    <?= htmlspecialchars($objective['objective_code']) ?>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Pillar</div>
                <div class="summary-value">
                    <span class="pillar-chip" style="border-color: <?= htmlspecialchars($objective['pillar_color'] ?: '#FF8C00') ?>;">
                        <i class="fa-solid <?= htmlspecialchars($objective['pillar_icon']) ?>"></i>
                        Pillar <?= (int)$objective['pillar_number'] ?> — <?= htmlspecialchars($objective['pillar_name']) ?>
                    </span>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Created At</div>
                <div class="summary-value">
                    <i class="fa-solid fa-calendar"></i>
                    <?= date('Y-m-d', strtotime($objective['created_at'])) ?>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">

            <!-- ================= Tabs Header ================= -->
            <div class="tabs">
                <div class="tab active" data-tab="tab-overview">
                        <i class="fa-solid fa-circle-info"></i> Overview
                </div>
                
                <div class="tab" data-tab="tab-pillar">
                        <i class="fa-solid fa-layer-group"></i> Linked Pillar
                </div>
                
                <div class="tab" data-tab="tab-initiatives">
                        <i class="fa-solid fa-diagram-project"></i> Linked Initiatives
                </div>
                
                <div class="tab" data-tab="tab-history">
                        <i class="fa-solid fa-clock-rotate-left"></i> History
                </div>
            </div>

            <!-- Overview Tab -->
            <div id="tab-overview" class="tab-content  active">
                <div class="box">
                    <div class="box-title">
                        <i class="fa-solid fa-bullseye"></i> Objective Details
                    </div>
                    <div class="box-body">
                        <div class="detail-row">
                            <span class="detail-label">Objective Text</span>
                            <p class="detail-text">
                                <?= nl2br(htmlspecialchars($objective['objective_text'])) ?>
                            </p>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Objective Code</span>
                            <span class="badge badge-code">
                                <i class="fa-solid fa-code"></i>
                                <?= htmlspecialchars($objective['objective_code']) ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Created At</span>
                            <span class="badge badge-date">
                                <i class="fa-solid fa-clock"></i>
                                <?= date('Y-m-d H:i', strtotime($objective['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pillar Tab -->
            <div id="tab-pillar" class="tab-content">
                <div class="box">
                    <div class="box-title">
                        <i class="fa-solid fa-layer-group"></i> Linked Pillar
                    </div>
                    <div class="box-body pillar-box">
                        <div class="pillar-icon-circle" style="background: <?= htmlspecialchars($objective['pillar_color'] ?: '#FF8C00') ?>20; border-color: <?= htmlspecialchars($objective['pillar_color'] ?: '#FF8C00') ?>;">
                            <i class="fa-solid <?= htmlspecialchars($objective['pillar_icon']) ?>"></i>
                        </div>
                        <div>
                            <div class="pillar-title">
                                Pillar <?= (int)$objective['pillar_number'] ?> — <?= htmlspecialchars($objective['pillar_name']) ?>
                            </div>
                            <a href="../pillars/view.php?id=<?= $objective['pillar_id'] ?>" class="pillar-link">
                                View Pillar Details <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Initiatives Tab -->
            <div id="tab-initiatives" class="tab-content">
                <div class="box">
                    <div class="box-title">
                        <i class="fa-solid fa-diagram-project"></i> Linked Initiatives
                    </div>
                    <div class="box-body">

                        <?php if (empty($initiatives)): ?>
                            <p class="empty-text">
                                No initiatives are currently linked to this objective.
                            </p>
                        <?php else: ?>

                        <div class="table-wrapper">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th style="width: 18%;">Code</th>
                                        <th>Name</th>
                                        <th style="width: 18%;">Status</th>
                                        <th style="width: 14%;">Progress</th>
                                        <th style="width: 10%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($initiatives as $i): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-code"><?= htmlspecialchars($i['initiative_code']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($i['name']) ?></td>
                                        <td>
                                            <?php if ($i['status_name']): ?>
                                                <span class="status-pill" style="background: <?= htmlspecialchars($i['status_color'] ?: '#EEE') ?>20; color:#333; border-color: <?= htmlspecialchars($i['status_color'] ?: '#CCC') ?>;">
                                                    <?= htmlspecialchars($i['status_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="status-pill status-pill-muted">Not Set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress-wrap">
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?= (int)$i['progress_percentage'] ?>%;"></div>
                                                </div>
                                                <span class="progress-text"><?= (int)$i['progress_percentage'] ?>%</span>
                                            </div>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="../initiatives/view.php?id=<?= $i['id'] ?>" class="btn-mini">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div id="tab-history" class="tab-content">
                <div class="box">
                    <div class="box-title">
                        <i class="fa-solid fa-clock-rotate-left"></i> Activity Log
                    </div>
                    <div class="box-body">

                        <?php
                        $logStmt = $db->prepare("
                        SELECT 
                            l.*, 
                            u.full_name_en AS user_name
                        FROM activity_log l
                        LEFT JOIN users u ON u.id = l.user_id
                        WHERE l.entity_type = 'strategic_objective'
                        AND l.entity_id = ?
                        ORDER BY l.created_at DESC
                        ");
                        $logStmt->execute([$objective_id]);
                        $history = $logStmt->fetchAll();
                        ?>

                        <?php if (empty($history)): ?>
                            <p class="empty-text">
                                No history recorded for this objective.
                            </p>
                        <?php else: ?>

                            <div class="timeline">
                                <?php foreach ($history as $h): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot"></div>
                                    <div class="timeline-content">
                                        <div class="timeline-header">
                                            <hspan class="timeline-action"><?= htmlspecialchars($h['action']) ?></hspan>

                                            <span class="timeline-date"><?= date("Y-m-d H:i", strtotime($h['created_at'])) ?></span>
                                        </div>

                                        <div class="timeline-user">
                                            <i class="fa-solid fa-user"></i>
                                            <?= htmlspecialchars($h['user_name']) ?>
                                        </div>

                                        <?php if (!empty($h['old_value']) || !empty($h['new_value'])): ?>
                                            <details class="timeline-details">
                                                <summary>Show Changes</summary>
                                                <pre class="log-json">
                                                    Old:
                                                    <?= htmlspecialchars($h['old_value']) ?>


                                                    New:
                                                    <?= htmlspecialchars($h['new_value']) ?>
                                                </pre>
                                            </details>
                                        <?php endif; ?>

                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div> <!-- /tabs-container -->

    </div>
</div>

<script>
document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', function () {
        const target = this.getAttribute('data-tab');

        document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));

        this.classList.add('active');
        document.getElementById(target).classList.add('active');
    });
});
</script>

<script src="../../assets/js/toast.js"></script>

<?php if (!empty($_SESSION['toast_success'])): ?>
<script> showToast("<?= $_SESSION['toast_success'] ?>", "success"); </script>
<?php unset($_SESSION['toast_success']); endif; ?>

<?php if (!empty($_SESSION['toast_error'])): ?>
<script> showToast("<?= $_SESSION['toast_error'] ?>", "error"); </script>
<?php unset($_SESSION['toast_error']); endif; ?>

</body>
</html>
