<?php
// modules/collaborations/index.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../modules/todos/todo_functions.php"; 

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
                
                // إشعار لمدير المشروع
                addSystemTodo($verify['requested_by'], "Collab Approved: $pName", "Your resource request has been approved.", "project", $verify['parent_id']);
                
                // إشعار للموظف المعين
                addSystemTodo($assigned_user_id, "New Assignment: $pName", "You have been assigned to collaborate on this project.", "project", $verify['parent_id']);

                $success = "Request approved and user assigned.";
            }
        } elseif ($action == 'reject') {
            $db->prepare("UPDATE collaborations SET status_id = 3, reviewed_by = ?, reviewed_at = NOW(), last_comment = ? WHERE id = ?")
               ->execute([$userId, $comments, $collab_id]);
               
             $pName = $db->query("SELECT name FROM operational_projects WHERE id={$verify['parent_id']}")->fetchColumn();
             addSystemTodo($verify['requested_by'], "Collab Rejected: $pName", "Your resource request was rejected.", "project", $verify['parent_id']);

            $success = "Request rejected.";
        }
        
        // إغلاق التنبيه
        $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_id = ? AND related_entity_type = 'collaboration' AND user_id = ?")->execute([$collab_id, $userId]);
    }
}

// 3. جلب الطلبات الواردة
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

// 4. جلب الموظفين
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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Unified Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1100px; margin: 0 auto; }
        
        /* Header */
        .page-header-flex { display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; }
        .page-title { font-size: 1.8rem; color: #2c3e50; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
        .page-title i { color: #ff8c00; }

        /* Collaboration Card */
        .collab-card { 
            background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 25px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; 
            border-left: 6px solid #f39c12; transition: transform 0.2s; position: relative;
        }
        .collab-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.06); }
        
        .card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 15px; }
        .project-title { font-size: 1.3rem; color: #333; font-weight: 700; margin: 0; }
        .request-date { font-size: 0.85rem; color: #888; background: #f9f9f9; padding: 4px 10px; border-radius: 20px; display: flex; align-items: center; gap: 6px; }

        .requester-info { font-size: 0.95rem; color: #555; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .requester-info strong { color: #333; }
        .dept-tag {font-family: "Varela Round", sans-serif; background: #e3f2fd; color: #1565c0; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }

        .requirement-box { background: #fff8e1; border: 1px solid #ffe0b2; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #5d4037; line-height: 1.5; font-size: 0.95rem; }
        .requirement-label { display: block; font-weight: 700; font-size: 0.85rem; text-transform: uppercase; color: #d35400; margin-bottom: 5px; letter-spacing: 0.5px; }

        /* Action Form */
        .action-area { background: #fdfdfd; padding: 20px; border-radius: 10px; border: 1px solid #eee; margin-top: 15px; }
        .form-row { display: flex; gap: 20px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 250px; }
        .form-label { display: block; font-weight: 600; color: #444; font-size: 0.9rem; margin-bottom: 8px; }
        
        .form-select, .form-input { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; 
            font-size: 0.95rem; transition: all 0.2s; box-sizing: border-box; background: #fff;
        }
        .form-select:focus, .form-input:focus { border-color: #ff8c00; outline: none; box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1); }

        .btn-row { margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px; }
        .btn-action { 
            border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; 
            font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; transition: 0.2s;
        }
        .btn-approve { font-family: "Varela Round", sans-serif;background: linear-gradient(135deg, #2ecc71, #27ae60); color: white; box-shadow: 0 4px 10px rgba(46, 204, 113, 0.2); }
        .btn-approve:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(46, 204, 113, 0.3); }
        
        .btn-reject {font-family: "Varela Round", sans-serif; background: #fff; border: 1px solid #e74c3c; color: #e74c3c; }
        .btn-reject:hover { background: #feNtce; color: #c0392b; border-color: #c0392b; }

        /* Empty State */
        .empty-state { text-align: center; padding: 60px; color: #a0aec0; background: #fff; border-radius: 12px; border: 2px dashed #e2e8f0; }
        .empty-state i { font-size: 3rem; margin-bottom: 15px; color: #cbd5e0; }
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
                            <div class="dept-tag"><i class="fa-solid fa-building"></i> Target: <?= htmlspecialchars($req['dept_name']) ?></div>
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