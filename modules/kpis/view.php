<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "functions.php";

// فقط السوبر أدمن + الاستراتيجي يشوفون التفاصيل
$allowedRoles = ["super_admin", "strategy_office"];
if (!Auth::check() || !in_array($_SESSION['role_key'], $allowedRoles)) {
    die("Access denied");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request");
}

$id = (int) $_GET['id'];

$db = Database::getInstance()->pdo();
$kpi = get_kpi_by_id($id);

if (!$kpi) {
    die("KPI not found");
}

// Load owners/parents/status labels
$statuses = get_kpi_statuses();
$status_label = $kpi['status_label'] ?? "Not set";
$owner_name   = $kpi['owner_name'] ?? "Not assigned";

$parent_name = "";
if ($kpi['parent_type'] === "initiative") {
    $parent_name = get_initiative_name($kpi['parent_id']);
} else {
    $parent_name = get_project_name($kpi['parent_id']);
}

// KPI history
$logStmt = $db->prepare("
    SELECT 
        l.*,
        u.full_name_en AS user_name
    FROM activity_log l
    LEFT JOIN users u ON u.id = l.user_id
    WHERE l.entity_type = 'kpi'
    AND l.entity_id = ?
    ORDER BY l.created_at DESC
");
$logStmt->execute([$id]);
$history = $logStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KPI Details</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/kpi_view.css">
    <link rel="icon" href="../../assets/images/favicon-32x32.png">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <!-- ================= Header + Actions ================= -->
        <div class="page-header-flex objective-header">
            <div>
                <h1><i class="fa-solid fa-chart-line"></i> <?= htmlspecialchars($kpi['name']) ?></h1>
                <p class="objective-subtitle">
                    <?= nl2br(htmlspecialchars($kpi['description'] ?: "No description provided")) ?>
                </p>
            </div>

            <div class="objective-actions">
                <a href="list.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Back</a>

                <?php if (is_super_admin()): ?>
                    <a href="edit.php?id=<?= $kpi['id'] ?>" class="btn-edit">
                        <i class="fa-solid fa-pen"></i> Edit
                    </a>

                    <a href="delete.php?id=<?= $kpi['id'] ?>"
                       onclick="return confirm('Delete KPI?');"
                       class="btn-delete">
                        <i class="fa-solid fa-trash"></i> Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ================= Summary Cards ================= -->
        <div class="summary-row">

            <div class="summary-card">
                <div class="summary-label">Status</div>
                <div class="summary-value">
                    <span class="pillar-chip"><i class="fa-solid fa-signal"></i><?= $status_label ?></span>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Owner</div>
                <div class="summary-value">
                    <i class="fa-solid fa-user"></i><?= htmlspecialchars($owner_name) ?>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-label">Last Updated</div>
                <div class="summary-value">
                    <i class="fa-solid fa-clock"></i>
                    <?= date('Y-m-d', strtotime($kpi['updated_at'] ?? $kpi['created_at'])) ?>
                </div>
            </div>

        </div>

        <!-- ================= Tabs ================= -->
        <div class="tabs-container">

            <div class="tabs">
                <div class="tab active" data-tab="tab-info">
                    <i class="fa-solid fa-circle-info"></i> KPI Info
                </div>

                <div class="tab" data-tab="tab-parent">
                    <i class="fa-solid fa-layer-group"></i> Parent
                </div>

                <div class="tab" data-tab="tab-history">
                    <i class="fa-solid fa-clock-rotate-left"></i> History
                </div>
            </div>

            <!-- ========== TAB: KPI INFORMATION ========== -->
            <div id="tab-info" class="tab-content active">
                <div class="box">
                    <div class="box-title"><i class="fa-solid fa-circle-info"></i> Details</div>
                    <div class="box-body">

                        <div class="detail-row">
                            <span class="detail-label">KPI Name</span>
                            <span class="detail-text"><?= htmlspecialchars($kpi['name']) ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Description</span>
                            <p class="detail-text">
                                <?= nl2br(htmlspecialchars($kpi['description'] ?: "No description provided")) ?>
                            </p>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Target Value</span>
                            <span class="badge badge-code"><?= $kpi['target_value'] ?> <?= $kpi['unit'] ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Baseline</span>
                            <span class="badge badge-date"><?= $kpi['baseline_value'] ?: "Not set" ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Frequency</span>
                            <span class="badge badge-date"><?= ucfirst($kpi['frequency']) ?></span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Data Source</span>
                            <span class="detail-text"><?= htmlspecialchars($kpi['data_source'] ?: "Not specified") ?></span>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ========== TAB: PARENT ENTITY ========== -->
            <div id="tab-parent" class="tab-content">
                <div class="box">
                    <div class="box-title">
                        <i class="fa-solid fa-layer-group"></i> Parent Entity
                    </div>

                    <div class="box-body pillar-box">
                        <div class="pillar-icon-circle">
                            <i class="fa-solid fa-link"></i>
                        </div>

                        <div>
                            <div class="pillar-title">
                                <?= ucfirst($kpi['parent_type']) ?> — <?= htmlspecialchars($parent_name) ?>
                            </div>

                            <?php if ($kpi['parent_type'] === "initiative"): ?>
                                <a href="../initiatives/view.php?id=<?= $kpi['parent_id'] ?>" class="pillar-link">
                                    Open Initiative <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php else: ?>
                                <a href="../projects/view.php?id=<?= $kpi['parent_id'] ?>" class="pillar-link">
                                    Open Project <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== TAB: HISTORY ========== -->
            <div id="tab-history" class="tab-content">
                <div class="box">
                    <div class="box-title"><i class="fa-solid fa-clock-rotate-left"></i> Activity Log</div>
                    <div class="box-body">

                        <?php if (empty($history)): ?>
                            <p class="empty-text">No changes recorded for this KPI.</p>
                        <?php else: ?>

                        <div class="timeline">
                            <?php foreach ($history as $h): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>

                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <span class="timeline-action"><?= htmlspecialchars($h['action']) ?></span>
                                        <span class="timeline-date"><?= date("Y-m-d H:i", strtotime($h['created_at'])) ?></span>
                                    </div>

                                    <div class="timeline-user">
                                        <i class="fa-solid fa-user"></i> <?= htmlspecialchars($h['user_name']) ?>
                                    </div>

                                    <?php if ($h['old_value'] || $h['new_value']): ?>
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

        </div> <!-- tabs-container -->

    </div>
</div>

<script>
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.tab;

        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(target).classList.add('active');
    });
});
</script>

</body>
</html>