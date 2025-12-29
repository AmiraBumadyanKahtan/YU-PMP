<?php
// modules/approvals/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "approval_functions.php";

if (!Auth::check()) die("Access Denied");

$db = Database::getInstance()->pdo();

// --- منطق التحقق من مدير المالية ---
// التحقق باستخدام ID القسم رقم 5
$isFinanceManager = false;
$stmtFinance = $db->prepare("SELECT COUNT(*) FROM departments WHERE manager_id = ? AND id = 5 AND is_deleted = 0");
$stmtFinance->execute([$_SESSION['user_id']]);
if ($stmtFinance->fetchColumn() > 0) {
    $isFinanceManager = true;
}

// Tabs Logic
$tab = $_GET['tab'] ?? 'pending';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $instance_id = $_POST['instance_id'];
    $decision = $_POST['decision'];
    $comments = trim($_POST['comments']);
    
    $error = null;
    
    // الإعادة والرفض يتطلبان تعليقاً إجبارياً
    if (($decision == 'returned' || $decision == 'rejected') && empty($comments)) {
        $error = "Comments are mandatory for Rejection or Return actions.";
    } 
    
    if (!$error) {
        if (processApproval($instance_id, $_SESSION['user_id'], $decision, $comments)) {
            $success = "Action taken successfully.";
        } else {
            $error = "Failed to process request.";
        }
    }
}

// Fetch Data
$status_filter = 'in_progress';
if ($tab == 'approved') $status_filter = 'approved';
if ($tab == 'returned') $status_filter = 'returned';
if ($tab == 'rejected') $status_filter = 'rejected';

$approvals = getUserApprovals($_SESSION['user_id'], $_SESSION['role_id'], $status_filter);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approval Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/Dashboard-approvals.css">
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

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-list-check"></i> Approval Dashboard</h1>
    </div>

    <?php if (isset($success)): ?> <script>Swal.fire('Success', '<?= $success ?>', 'success');</script> <?php endif; ?>
    <?php if (isset($error)): ?> <script>Swal.fire('Error', '<?= $error ?>', 'error');</script> <?php endif; ?>

    <div class="tabs-nav">
        <a href="?tab=pending" class="<?= $tab=='pending'?'active':'' ?>">Pending My Action</a>
        <a href="?tab=approved" class="<?= $tab=='approved'?'active':'' ?>">Approved History</a>
        <a href="?tab=returned" class="<?= $tab=='returned'?'active':'' ?>">Returned History</a>
        <a href="?tab=rejected" class="<?= $tab=='rejected'?'active':'' ?>">Rejected History</a>
    </div>

    <?php if (empty($approvals)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <p>No records found in this category.</p>
        </div>
    <?php else: ?>
        
        <?php foreach ($approvals as $req): ?>
            <?php 
                $budgetInfo = isset($req['budget_range']) ? explode('|', $req['budget_range']) : ['0','0']; 
                $min = number_format((float)$budgetInfo[0]);
                $max = number_format((float)$budgetInfo[1]);
                $currentApproved = isset($req['current_approved_budget']) && $req['current_approved_budget'] > 0 
                                   ? number_format($req['current_approved_budget']) . ' SAR' 
                                   : 'Not Set';
            ?>
            <div class="content-card <?= $status_filter ?>">
                
                <div class="card-header">
                    <h3>
                        <?php if($req['entity_key'] == 'operational_project'): ?>
                            <i class="fa-solid fa-diagram-project" style="color:#3498db;"></i>
                        <?php elseif($req['entity_key'] == 'initiative'): ?>
                            <i class="fa-solid fa-lightbulb" style="color:#f1c40f;"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-file"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($req['entity_title']) ?>
                    </h3>
                    <span class="card-date">
                        <i class="fa-regular fa-clock"></i> <?= date('M d, Y • h:i A', strtotime($req['request_date'] ?? $req['action_date'])) ?>
                    </span>
                </div>

                <div class="card-body">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Type</span>
                            <span class="meta-value"><?= ucfirst(str_replace('_', ' ', $req['entity_key'])) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Department</span>
                            <span class="meta-value"><?= htmlspecialchars($req['department_name']) ?></span>
                        </div>
                        
                        <?php if ($tab == 'pending'): ?>
                            <div class="meta-item">
                                <span class="meta-label">Requested By</span>
                                <span class="meta-value"><?= htmlspecialchars($req['requester_name']) ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Current Stage</span>
                                <span class="meta-value highlight"><?= htmlspecialchars($req['stage_name']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if($req['entity_key'] == 'operational_project'): ?>
                            <div class="meta-item">
                                <span class="meta-label">Est. Budget Range</span>
                                <span class="meta-value"><?= $min ?> - <?= $max ?> <small style="color:#aaa;">SAR</small></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Approved Budget</span>
                                <span class="meta-value" style="color:#2ecc71;"><?= $currentApproved ?></span>
                            </div>
                            <?php if(!empty($req['budget_item'])): ?>
                                <div class="meta-item" style="grid-column: span 2;">
                                    <span class="meta-label">Budget Item</span>
                                    <span class="meta-value"><?= htmlspecialchars($req['budget_item']) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($req['entity_description'])): ?>
                            <div class="description-box">
                                <span class="meta-label">Description:</span><br>
                                <?= nl2br(htmlspecialchars(substr($req['entity_description'], 0, 350))) . (strlen($req['entity_description']) > 350 ? '...' : '') ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($tab != 'pending'): ?>
                        <div class="comment-display">
                            <i class="fa-solid fa-comment-dots"></i> <strong>Your Comment:</strong> <?= htmlspecialchars($req['comments']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="action-footer">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <?php 
                            $link = "#";
                            if ($req['entity_key'] == 'operational_project') $link = BASE_URL . "modules/operational_projects/view.php?id=" . $req['entity_id'];
                            elseif ($req['entity_key'] == 'initiative') $link = BASE_URL . "modules/initiatives/view.php?id=" . $req['entity_id'];
                        ?>
                        <a href="<?= $link ?>" target="_blank" class="btn-link">
                            <i class="fa-solid fa-up-right-from-square"></i> View Full Details
                        </a>
                    </div>

                    <?php if ($tab == 'pending'): ?>
                    <form method="POST" id="form-<?= $req['instance_id'] ?>">
                        <input type="hidden" name="instance_id" value="<?= $req['instance_id'] ?>">
                        <input type="hidden" name="decision" id="decision-<?= $req['instance_id'] ?>">

                        <textarea name="comments" id="comment-<?= $req['instance_id'] ?>" class="comment-input" rows="1" placeholder="Write a comment (Required for Return/Reject)..."></textarea>

                        <div class="btn-group" style="margin-top:15px;">
                            <?php if ($isFinanceManager && $req['entity_key'] == 'operational_project'): ?>
                                <button type="button" onclick="submitDecision(<?= $req['instance_id'] ?>, 'approved')" class="btn btn-approve">
                                    <i class="fa-solid fa-check-double"></i> Budget Available (Approve)
                                </button>
                                <button type="button" onclick="submitDecision(<?= $req['instance_id'] ?>, 'returned')" class="btn btn-return">
                                    <i class="fa-solid fa-money-bill-transfer"></i> No Budget (Return)
                                </button>
                            <?php else: ?>
                                <button type="button" onclick="submitDecision(<?= $req['instance_id'] ?>, 'approved')" class="btn btn-approve">
                                    <i class="fa-solid fa-check"></i> Approve
                                </button>
                                <button type="button" onclick="submitDecision(<?= $req['instance_id'] ?>, 'returned')" class="btn btn-return">
                                    <i class="fa-solid fa-rotate-left"></i> Return
                                </button>
                                <button type="button" onclick="submitDecision(<?= $req['instance_id'] ?>, 'rejected')" class="btn btn-reject">
                                    <i class="fa-solid fa-xmark"></i> Reject
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</div>

<script>
function submitDecision(id, decision) {
    const comment = document.getElementById('comment-' + id).value.trim();
    
    // 1. التحقق من التعليق
    if ((decision === 'rejected' || decision === 'returned') && comment === "") {
        Swal.fire('Comment Required', 'Please provide a reason for rejecting or returning this request.', 'warning');
        return;
    }

    // 2. تأكيد الرفض النهائي
    if (decision === 'rejected') {
        if (!confirm("Are you sure you want to REJECT this entirely? This cannot be undone.")) return;
    }

    document.getElementById('decision-' + id).value = decision;
    document.getElementById('form-' + id).submit();
}
</script>

</body>
</html>