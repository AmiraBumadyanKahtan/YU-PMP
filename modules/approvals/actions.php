<?php
require_once "../../core/init.php";
require_once "approval_functions.php";
require_once "../../core/functions.php";

if (!Auth::can('approve_requests')) {
    die("Access denied");
}

$approvalId = (int)($_POST['approval_id'] ?? 0);
$action     = $_POST['action'] ?? '';

if (!$approvalId || !in_array($action, ['approve','reject','return'])) {
    die("Invalid request");
}

$result = processApprovalAction(
    $approvalId,
    $_SESSION['user_id'],
    $action
);

if (!$result) {
    die("Action failed");
}

/* ✅ هذا هو السطر المطلوب بالضبط */
advanceApprovalStage($approvalId);

/* ✅ تسجيل النشاط (اختياري لكن مهم) */
log_activity(
    $_SESSION['user_id'],
    $action,
    'approval',
    $approvalId,
    null,
    $action
);

header("Location: dashboard.php");
exit;
