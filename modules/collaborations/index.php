<?php
// modules/collaborations/index.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../modules/todos/todo_functions.php"; 
// تأكد من وجود notification_helper هنا أيضاً إذا لم يكن مضمناً في todo_functions
if (file_exists(__DIR__ . '/../../modules/operational_projects/notification_helper.php')) {
    require_once __DIR__ . '/../../modules/operational_projects/notification_helper.php';
}

if (!Auth::check()) die("Access Denied");

$db = Database::getInstance()->pdo();
$userId = $_SESSION['user_id'];

// 1. تحديد نوع المستخدم وصلاحياته
$superRoles = ['super_admin', 'ceo', 'strategy_office'];
$isSuperAdmin = in_array($_SESSION['role_key'], $superRoles);

// إذا لم يكن سوبر أدمن، نجلب أقسامه
$myDepts = [];
if (!$isSuperAdmin) {
    $deptCheck = $db->prepare("SELECT id FROM departments WHERE manager_id = ? AND is_deleted = 0");
    $deptCheck->execute([$userId]);
    $myDepts = $deptCheck->fetchAll(PDO::FETCH_COLUMN);

    if (empty($myDepts)) {
        include "../../layout/header.php";
        include "../../layout/sidebar.php";
        echo "<div class='main-content'><div class='page-wrapper'><div class='alert alert-warning'>Access Restricted. This page is for Department Managers only.</div></div></div>";
        exit;
    }
}

// 2. معالجة القرار (Approve/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collab_id = $_POST['collab_id'];
    $action = $_POST['action'];
    $assigned_user_id = $_POST['assigned_user_id'] ?? null;
    $comments = $_POST['comments'] ?? '';

    // التحقق من الصلاحية
    $sqlVerify = "SELECT * FROM collaborations WHERE id = ?";
    if (!$isSuperAdmin) {
        $deptIdsStr = implode(',', $myDepts);
        $sqlVerify .= " AND department_id IN ($deptIdsStr)";
    }
    
    $stmtVerify = $db->prepare($sqlVerify);
    $stmtVerify->execute([$collab_id]);
    $verify = $stmtVerify->fetch();
    
    if ($verify) {
         if ($action == 'approve') {
            if (!$assigned_user_id) { 
                $error = "Please select a user to assign."; 
            } else {
                $db->prepare("UPDATE collaborations SET status_id = 2, assigned_user_id = ?, reviewed_by = ?, reviewed_at = NOW(), last_comment = ? WHERE id = ?")
                    ->execute([$assigned_user_id, $userId, $comments, $collab_id]);
                
                $pName = $db->query("SELECT name FROM operational_projects WHERE id={$verify['parent_id']}")->fetchColumn();
                
                // [MODIFIED] إشعار لمدير المشروع (إيميل + تودو)
                if (function_exists('sendProjectNotification')) {
                    sendProjectNotification(
                        $verify['requested_by'], 
                        "Collab Approved: $pName", 
                        "Your resource request has been approved. User assigned.", 
                        "project_view", 
                        $verify['parent_id']
                    );
                    
                    // [MODIFIED] إشعار للموظف المعين
                    sendProjectNotification(
                        $assigned_user_id, 
                        "New Assignment: $pName", 
                        "You have been assigned to collaborate on project: $pName.", 
                        "project_view", 
                        $verify['parent_id']
                    );
                } else {
                    // Fallback
                    addSystemTodo($verify['requested_by'], "Collab Approved: $pName", "Your resource request has been approved.", "project", $verify['parent_id']);
                    addSystemTodo($assigned_user_id, "New Assignment: $pName", "You have been assigned to collaborate on this project.", "project", $verify['parent_id']);
                }

                $success = "Request approved and user assigned.";
            }
        } elseif ($action == 'reject') {
            $db->prepare("UPDATE collaborations SET status_id = 3, reviewed_by = ?, reviewed_at = NOW(), last_comment = ? WHERE id = ?")
                ->execute([$userId, $comments, $collab_id]);
                
             $pName = $db->query("SELECT name FROM operational_projects WHERE id={$verify['parent_id']}")->fetchColumn();
             
             // [MODIFIED] إشعار الرفض لمدير المشروع
             if (function_exists('sendProjectNotification')) {
                 sendProjectNotification(
                     $verify['requested_by'], 
                     "Collab Rejected: $pName", 
                     "Your resource request was rejected.\nComment: $comments", 
                     "project_view", 
                     $verify['parent_id']
                 );
             } else {
                 addSystemTodo($verify['requested_by'], "Collab Rejected: $pName", "Your resource request was rejected.", "project", $verify['parent_id']);
             }

            $success = "Request rejected.";
        }
        
        // إغلاق التنبيه الخاص بمدير القسم (لأنه اتخذ القرار)
        // ملاحظة: التودو القديم كان نوعه 'collaboration'
        $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_id = ? AND related_entity_type = 'collaboration_review' AND user_id = ?")->execute([$collab_id, $userId]);
    }
}

// 3. جلب الطلبات الواردة (كما هو)
$sqlRequests = "
    SELECT c.*, p.name as project_name, u.full_name_en as requester_name, d.name as dept_name
    FROM collaborations c
    JOIN operational_projects p ON p.id = c.parent_id
    JOIN users u ON u.id = c.requested_by
    JOIN departments d ON d.id = c.department_id
    WHERE c.status_id = 1
";

if (!$isSuperAdmin) {
    $deptIdsStr = implode(',', $myDepts);
    $sqlRequests .= " AND c.department_id IN ($deptIdsStr)";
}

$sqlRequests .= " ORDER BY c.created_at DESC";
$requests = $db->query($sqlRequests)->fetchAll();

// 4. جلب الموظفين (كما هو)
$sqlStaff = "SELECT department_id, id, full_name_en FROM users WHERE is_active = 1";
if (!$isSuperAdmin) {
    $deptIdsStr = implode(',', $myDepts);
    $sqlStaff .= " AND department_id IN ($deptIdsStr)";
}
$sqlStaff .= " ORDER BY full_name_en";

$allStaff = $db->query($sqlStaff)->fetchAll(PDO::FETCH_GROUP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incoming Collaboration Requests</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <style>
        .btn-reject:hover { background: #feNtce; color: #c0392b; border-color: #c0392b; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-inbox"></i> Incoming Resource Requests</h1>
    </div>

    <?php if(isset($success)): ?><script>Swal.fire({icon: 'success', title: 'Success', text: '<?=$success?>', timer: 2000, showConfirmButton: false});</script><?php endif; ?>
    <?php if(isset($error)): ?><script>Swal.fire({icon: 'error', title: 'Error', text: '<?=$error?>'});</script><?php endif; ?>

    <?php if(empty($requests)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <h3>No Pending Requests</h3>
            <p>You have no pending collaboration requests at the moment.</p>
        </div>
    <?php else: ?>
        <div style="display:grid; gap:25px;">
            <?php foreach($requests as $req): ?>
                <div class="collab-card">
                    <div class="card-header">
                        <h3 class="project-title"><i class="fa-solid fa-diagram-project" style="color:#f39c12; margin-right:5px;"></i> <?= htmlspecialchars($req['project_name']) ?></h3>
                        <span class="request-date"><i class="fa-regular fa-clock"></i> <?= date('d M Y', strtotime($req['created_at'])) ?></span>
                    </div>
                    
                    <div class="requester-info">
                        <div>From: <strong><?= htmlspecialchars($req['requester_name']) ?></strong> (Project Manager)</div>
                        <?php if($isSuperAdmin): ?>
                            <div class="dept-tag"><i class="fa-solid fa-building"></i> Department: <?= htmlspecialchars($req['dept_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="requirement-box">
                        <span class="requirement-label">Request Details:</span>
                        <?= nl2br(htmlspecialchars($req['reason'])) ?>
                    </div>

                    <form method="POST" class="action-area">
                        <input type="hidden" name="collab_id" value="<?= $req['id'] ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Assign Employee <span style="color:red">*</span></label>
                                <select name="assigned_user_id" class="form-select">
                                    <option value="">-- Select Resource --</option>
                                    <?php 
                                        $deptStaff = $allStaff[$req['department_id']] ?? [];
                                        foreach($deptStaff as $s): 
                                    ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['full_name_en']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Comments (Optional)</label>
                                <input type="text" name="comments" placeholder="Add a note or instructions..." class="form-input">
                            </div>
                        </div>

                        <div class="btn-row">
                            <button type="submit" name="action" value="reject" class="btn-action btn-reject" onclick="return confirm('Reject this request?');">
                                <i class="fa-solid fa-xmark"></i> Reject
                            </button>
                            
                            <button type="submit" name="action" value="approve" class="btn-action btn-approve">
                                <i class="fa-solid fa-check"></i> Approve & Assign
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</div>
</body>
</html>