<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once $_SERVER['DOCUMENT_ROOT'] . '/strategic-project-system/modules/shared/functions/approval_workflow.php';

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

$roleKey = $_SESSION['role_key'] ?? null;
$userId  = $_SESSION['user_id'] ?? null;

$isSuperAdmin     = $roleKey === 'super_admin';
$isCEO            = $roleKey === 'ceo';
$isStrategyOffice = $roleKey === 'strategy_office';
$isStrategyStaff  = $roleKey === 'strategy_staff';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    $_SESSION['toast_error'] = "Invalid pillar ID.";
    header("Location: list.php");
    exit;
}

$stmt = $db->prepare("
    SELECT p.*, s.name AS status_name, s.color AS status_color
    FROM pillars p
    LEFT JOIN pillar_statuses s ON s.id = p.status_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pillar = $stmt->fetch();

if (!$pillar) {
    $_SESSION['toast_error'] = "Pillar not found.";
    header("Location: list.php");
    exit;
}

/* --------------------------------------------------
    Load related pillar data
---------------------------------------------------*/
$stmt = $db->prepare("SELECT * FROM strategic_objectives WHERE pillar_id=?");
$stmt->execute([$id]);
$objectives = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT i.*, ist.name AS status_name 
    FROM initiatives i 
    LEFT JOIN initiative_statuses ist ON ist.id = i.status_id
    WHERE i.pillar_id=?
");
$stmt->execute([$id]);
$initiatives = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM documents WHERE parent_type='pillar' AND parent_id=?");
$stmt->execute([$id]);
$documents = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM meeting_minutes WHERE parent_type='pillar' AND parent_id=?");
$stmt->execute([$id]);
$meetings = $stmt->fetchAll();


/* --------------------------------------------------
    APPROVAL WORKFLOW SYSTEM  
---------------------------------------------------*/

// 1) Load Flow Stages
$stages = getFlowStages($db, "pillar");

// 2) Load (or not) approval instance
$instance = getApprovalInstance($db, "pillar", $id);
$instanceId = $instance['id'] ?? null;
$currentStageId = $instance['current_stage_id'] ?? null;

// 3) Does this user have a pending approval?
$pendingApproval = $instanceId ? getPendingApprovalForUser($db, $instanceId, $userId) : null;
$hasApprovalToAct = $pendingApproval ? true : false;

// 4) Load timeline
$approvalTimeline = $instanceId ? getApprovalTimeline($db, $instanceId) : [];

// 5) Can user send for approval?
$canSendForApproval = canUserSendForApproval(
    $roleKey,
    count($objectives),
    $pillar['status_name'],
    ($instanceId !== null)
);

// 6) Can user approve this stage?
$canApprove = false;
if ($pendingApproval && $pendingApproval['stage_id']) {
    $canApprove = canUserApproveStage($db, $pendingApproval['stage_id'], $roleKey);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pillar Details</title>

<link rel="stylesheet" href="../../assets/css/layout.css">
<link rel="stylesheet" href="../../assets/css/toast.css">
<link rel="stylesheet" href="css/view.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title" style="color:<?= $pillar['color'] ?>">
            <i class="fa-solid <?= $pillar['icon'] ?>" style="margin-right:10px;color:<?= $pillar['color'] ?>"></i>
            <?= htmlspecialchars($pillar['name']) ?>
        </h1>

        <!-- Top Action Buttons -->
        <div class="details-actions">

            <a href="list.php" class="btn btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>

            <?php if ($isSuperAdmin): ?>
            <a href="edit.php?id=<?= $pillar['id'] ?>" class="btn btn-edit">
                <i class="fa-solid fa-pen"></i> Edit
            </a>
            <a href="delete.php?id=<?= $pillar['id'] ?>" class="btn btn-delete">
                <i class="fa-solid fa-trash"></i> Delete
            </a>
            <?php endif; ?>

            <?php if ($canSendForApproval): ?>
                <a href="send_for_approval.php?id=<?= $pillar['id'] ?>" class="btn btn-approve">
                    <i class="fa-solid fa-paper-plane"></i> Send for Approval
                </a>
            <?php endif; ?>

            <?php if ($canApprove): ?>
                <a href="approve.php?id=<?= $pillar['id'] ?>" class="btn btn-approve">
                    <i class="fa-solid fa-check"></i> Approve / Reject
                </a>
            <?php endif; ?>

        </div>

    </div>

        <div class="details-wrapper">
            

            <!-- ================= Tabs Header ================= -->
            <div class="tabs">
                <div class="tab active" data-tab="details">Details</div>
                <div class="tab" data-tab="objectives">Objectives</div>
                <div class="tab" data-tab="initiatives">Initiatives</div>
                <div class="tab" data-tab="documents">Documents</div>
                <div class="tab" data-tab="meetings">Meetings</div>
                <!-- NEW TAB -->
                <div class="tab" data-tab="approvals">Approvals</div>
            </div>

            <!-- =============== DETAILS TAB ================= -->
            <div class="tab-content active" id="details">

                <div class="card-grid">
                    <div class="card-box">
                        <h4>Pillar Number</h4>
                        <p><?= $pillar['pillar_number'] ?></p>
                    </div>

                    <div class="card-box">
                        <h4>Status</h4>
                        <p>
                            <span class="status-badge" style="background:<?= $pillar['status_color'] ?>">
                                <?= $pillar['status_name'] ?>
                            </span>
                        </p>
                    </div>

                    <div class="card-box">
                        <h4>Start Date</h4>
                        <p><?= $pillar['start_date'] ?></p>
                    </div>

                    <div class="card-box">
                        <h4>End Date</h4>
                        <p><?= $pillar['end_date'] ?></p>
                    </div>

                    <div class="card-box full">
                        <h4>Description</h4>
                        <p><?= nl2br(htmlspecialchars($pillar['description'])) ?></p>
                    </div>
                </div>


            </div>

            <!-- =============== OBJECTIVES TAB ================= -->
            <div class="tab-content" id="objectives">
                <div class="tab-section-header">
                    <h3><i class="fa-solid fa-bullseye"></i> objectives</h3>

                    <a href="../pillar_objectives/create.php?pillar_id=<?= $pillar['id'] ?>" class="btn-small-add">
                        <i class="fa-solid fa-plus"></i> Add objectives
                    </a>
                </div>

                <div class="card-list">
                    <?php if (empty($objectives)): ?>
                        <div class="empty-box">No objectives added yet.</div>
                    <?php else: ?>
                        <?php foreach ($objectives as $o): ?>
                            <div class="card-item">
                                <h4><?= htmlspecialchars($o['objective_text']) ?></h4>
                                <p><?= htmlspecialchars($o['objective_code']) ?></p>

                                <div class="item-actions">
                                    <a class="btn btn-edit" href="../pillar_objectives/edit.php?id=<?= $o['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                                    <a class="btn btn-delete" href="../pillar_objectives/delete.php?id=<?= $o['id'] ?>"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- =============== INITIATIVES TAB ================= -->
            <div class="tab-content" id="initiatives">

                <div class="tab-section-header">
                    <h3><i class="fa-solid fa-diagram-project"></i> Initiatives</h3>

                    <?php if ($canAddInitiatives): ?>
                        <a href="../initiatives/create.php?pillar_id=<?= $pillar['id'] ?>" class="btn-small-add">
                            <i class="fa-solid fa-plus"></i> Add Initiative
                        </a>
                    <?php else: ?>
                        <span class="hint-text">
                            You can add initiatives after CEO approval.
                        </span>
                    <?php endif; ?>
                </div>


                <div class="card-list">
                    <?php if (empty($initiatives)): ?>
                        <div class="empty-box">No initiatives found.</div>
                    <?php else: ?>
                        <?php foreach ($initiatives as $i): ?>
                            <div class="card-item">
                                
                                <div class="card-item-header">
                                    <h4><?= htmlspecialchars($i['title']) ?></h4>
                                    <span class="badge" style="background:<?= $i['status_color'] ?>">
                                        <?= $i['status_name'] ?>
                                    </span>
                                </div>

                                <p><?= htmlspecialchars($i['description']) ?></p>

                                <div class="item-actions">
                                    <a href="../initiatives/details.php?id=<?= $i['id'] ?>"><i class="fa-solid fa-eye"></i></a>
                                    <a href="../initiatives/edit.php?id=<?= $i['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                                    <a href="../initiatives/delete.php?id=<?= $i['id'] ?>"><i class="fa-solid fa-trash"></i></a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- =============== DOCUMENTS TAB ================= -->
            <div class="tab-content" id="documents">
                <div class="tab-section-header">
                    <h3><i class="fa-solid fa-file"></i> Documents</h3>

                    <a href="../pillar_documents/upload.php?pillar_id=<?= $pillar['id'] ?>" class="btn-small-add">
                        <i class="fa-solid fa-upload"></i> Upload Document
                    </a>
                </div>

                <div class="card-list">
                    <?php if (empty($documents)): ?>
                        <div class="empty-box">No documents uploaded.</div>
                    <?php else: ?>
                        <?php foreach ($documents as $d): ?>
                            <div class="card-item">
                                <h4><i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($d['file_name']) ?></h4>

                                <p>Uploaded by: <?= htmlspecialchars($d['uploaded_by']) ?></p>

                                <div class="item-actions">
                                    <a href="/uploads/<?= $d['file_path'] ?>" target="_blank"><i class="fa-solid fa-download"></i></a>
                                    <a href="../pillar_documents/delete.php?id=<?= $d['id'] ?>"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- =============== MEETINGS TAB ================= -->
            <div class="tab-content" id="meetings">
                <div class="tab-section-header">
                    <h3><i class="fa-solid fa-clipboard-list"></i> Meetings</h3>

                    <a href="../pillar_meetings/create.php?pillar_id=<?= $pillar['id'] ?>" class="btn-small-add">
                        <i class="fa-solid fa-calendar-plus"></i> Add Meeting
                    </a>
                </div>

                <div class="card-list">
                    <?php if (empty($meetings)): ?>
                        <div class="empty-box">No meetings recorded.</div>
                    <?php else: ?>
                        <?php foreach ($meetings as $m): ?>
                            <div class="card-item">

                                <div class="card-item-header">
                                    <h4><i class="fa-solid fa-handshake"></i> <?= htmlspecialchars($m['title']) ?></h4>
                                    <span class="badge"><?= $m['meeting_date'] ?></span>
                                </div>

                                <p><?= htmlspecialchars($m['notes']) ?></p>

                                <div class="item-actions">
                                    <a href="../pillar_meetings/edit.php?id=<?= $m['id'] ?>"><i class="fa-solid fa-pen"></i></a>
                                    <a href="../pillar_meetings/delete.php?id=<?= $m['id'] ?>"><i class="fa-solid fa-trash"></i></a>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>

            <!-- =============== APPROVALS TAB ================= -->
            <div class="tab-content" id="approvals">

                <div class="tab-section-header">
                    <h3><i class="fa-solid fa-gavel"></i> Approval Workflow</h3>

                    <?php if ($canApprove): ?>
                        <a href="approve.php?id=<?= $pillar['id'] ?>" class="btn-small-add">
                            <i class="fa-solid fa-check"></i> Review Approval
                        </a>
                    <?php endif; ?>
                </div>


                <!-- Current Stage -->
                <?php if ($instanceId): ?>
                    <div class="approval-latest-box">
                        <h4><i class="fa-solid fa-circle-info"></i> Current Stage</h4>

                        <?php
                        $currentStage = null;
                        foreach ($stages as $s) {
                            if ($s['id'] == $currentStageId) $currentStage = $s;
                        }
                        ?>

                        <?php if ($currentStage): ?>
                        <p><strong>Stage:</strong> <?= $currentStage['stage_name'] ?></p>
                        <p><strong>Role Needed:</strong> <?= $currentStage['reviewer_role'] ?></p>
                        <?php endif; ?>

                        <?php if ($pendingApproval): ?>
                        <p><strong>Status:</strong>
                            <span class="badge-approval status-pending">Pending Your Action</span>
                        </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>


                <!-- Timeline -->
                <h3 style="margin-top:30px;">
                    <i class="fa-solid fa-clock-rotate-left"></i> Approval Timeline
                </h3>

                <?php if (empty($approvalTimeline)): ?>
                    <div class="empty-box">No approval activity recorded.</div>
                <?php else: ?>
                    <div class="approval-history-list">
                        <?php foreach ($approvalTimeline as $row): ?>
                            <div class="history-row">
                                <span class="badge-approval status-<?= strtolower($row['decision']) ?>">
                                    <?= ucfirst($row['decision']) ?>
                                </span>

                                <div>
                                    <strong><?= htmlspecialchars($row['reviewer_name']) ?></strong><br>
                                    <small><?= $row['created_at'] ?> — <?= $row['stage_name'] ?></small>

                                    <?php if ($row['comments']): ?>
                                        <div class="comment-box">
                                            <?= nl2br(htmlspecialchars($row['comments'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

    <script>
    document.querySelectorAll(".tab").forEach(tab => {
        tab.addEventListener("click", () => {
            document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
            document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));

            tab.classList.add("active");
            document.getElementById(tab.dataset.tab).classList.add("active");
        });
    });
    </script>

</body>
</html>
