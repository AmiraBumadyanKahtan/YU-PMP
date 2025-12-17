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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">

    <style>
        /* --- 1. General Styles based on your Theme --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1200px; margin: 0 auto; }

        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { margin: 0; font-size: 1.6rem; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 12px; }
        .page-title i { color: #ff8c00; }

        /* --- 2. Tabs --- */
        .tabs-nav { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #f0f0f0; padding-bottom: 0; }
        .tabs-nav a { 
            padding: 12px 25px; 
            text-decoration: none; 
            color: #7f8c8d; 
            border-radius: 12px 12px 0 0; 
            font-weight: 600; 
            font-size: 0.95rem;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            border-bottom: none;
        }
        .tabs-nav a:hover { color: #ff8c00; background: #fffdf9; }
        .tabs-nav a.active { 
            background: #fff; 
            color: #ff8c00; 
            border-color: #f0f0f0; 
            border-bottom: 2px solid #fff; /* Cover the line */
            margin-bottom: -2px;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.02);
        }

        /* --- 3. Card Styles (Adapted from your request) --- */
        .content-card { 
            background: #fff; 
            padding: 0; /* Padding inside body */
            border-radius: 14px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.03); 
            margin-bottom: 25px; 
            border: 1px solid #f0f0f0; 
            overflow: hidden; /* For header border radius */
            position: relative;
            transition: transform 0.2s;
        }
        .content-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.06); }

        /* Card Status Color Indicator */
        .content-card::before { content: ''; position: absolute; top: 0; left: 0; bottom: 0; width: 5px; z-index: 1; }
        .content-card.in_progress::before { background: #ff8c00; }
        .content-card.approved::before { background: #2ecc71; }
        .content-card.rejected::before { background: #e74c3c; }
        .content-card.returned::before { background: #f39c12; }

        .card-header { padding: 20px 25px; border-bottom: 1px solid #f9f9f9; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { margin: 0; color: #2c3e50; font-size: 1.1rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card-date { font-size: 0.85rem; color: #999; display: flex; align-items: center; gap: 6px; }

        .card-body { padding: 25px; }

        /* --- 4. Metadata Grid --- */
        .meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .meta-item { display: flex; flex-direction: column; }
        .meta-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #aaa; font-weight: 700; margin-bottom: 5px; }
        .meta-value { font-size: 0.95rem; color: #444; font-weight: 600; }
        .meta-value.highlight { color: #ff8c00; }

        .description-box { 
            grid-column: 1 / -1; 
            background: #fafafa; 
            border: 1px solid #eee; 
            border-radius: 8px; 
            padding: 15px; 
            font-size: 0.9rem; 
            color: #666; 
            line-height: 1.6;
        }

        .comment-display {
            background: #fff8e1;
            border: 1px solid #ffe0b2;
            color: #d35400;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        /* --- 5. Forms & Buttons --- */
        .action-footer { 
            background: #fdfdfd; 
            padding: 20px 25px; 
            border-top: 1px solid #f0f0f0; 
            display: flex; 
            flex-direction: column;
            gap: 15px;
        }

        .comment-input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-family: inherit; 
            font-size: 0.95rem; 
            transition: 0.2s; 
            box-sizing: border-box;
        }
        .comment-input:focus { border-color: #ff8c00; outline: none; }

        .btn-group { display: flex; gap: 10px; justify-content: flex-end; align-items: center; }

        .btn { border: none; padding: 10px 22px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 0.9rem; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; }
        
        .btn-approve { background: #2ecc71; color: #fff; }
        .btn-approve:hover { background: #27ae60; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.2); }

        .btn-return { background: #f39c12; color: #fff; }
        .btn-return:hover { background: #d35400; box-shadow: 0 4px 10px rgba(243, 156, 18, 0.2); }

        .btn-reject { background: #fff; color: #e74c3c; border: 1px solid #e74c3c; }
        .btn-reject:hover { background: #fff5f5; }

        .btn-link { color: #3498db; text-decoration: none; font-weight: bold; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px; }
        .btn-link:hover { text-decoration: underline; color: #2980b9; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; color: #aaa; }
        .empty-state i { font-size: 3.5rem; margin-bottom: 15px; color: #eee; }
    </style>
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