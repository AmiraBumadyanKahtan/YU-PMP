<?php
// modules/initiatives/view.php

// 1. معالجة طلبات AJAX في بداية الملف تماماً
// هذا الجزء مسؤول عن جلب المستخدمين عند اختيار القسم في تبويب الفريق
if (isset($_GET['action']) && $_GET['action'] == 'get_users') {
    // تنظيف أي مخرجات سابقة لمنع فساد ملف JSON
    while (ob_get_level()) ob_end_clean(); 
    
    require_once "../../core/config.php";
    require_once "../../core/auth.php";
    require_once "../../core/Database.php";
    
    header('Content-Type: application/json');

    try {
        if (!Auth::check()) throw new Exception("Access Denied");
        
        $db = Database::getInstance()->pdo();
        $deptId = $_GET['dept_id'] ?? '';
        
        $sql = "SELECT id, full_name_en FROM users WHERE is_active=1 AND is_deleted=0";
        if (!empty($deptId)) {
            $sql .= " AND department_id = " . intval($deptId);
        }
        $sql .= " ORDER BY full_name_en ASC";
        
        $users = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit; // إنهاء التنفيذ هنا فوراً للـ AJAX
}

// 2. تحميل الصفحة العادية
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$activeTab = $_GET['tab'] ?? 'overview';

$db = Database::getInstance()->pdo();

// --- دوال مساعدة ---
function formatMoney($amount) {
    return number_format($amount, 2) . ' <small>SAR</small>';
}

// 3. جلب بيانات المبادرة
$stmt = $db->prepare("
    SELECT i.*, 
           p.name AS pillar_name, p.color AS pillar_color,
           s.name AS status_name, s.color AS status_color,
           u.full_name_en AS owner_name, u.avatar AS owner_avatar
    FROM initiatives i
    LEFT JOIN pillars p ON p.id = i.pillar_id
    LEFT JOIN initiative_statuses s ON s.id = i.status_id
    LEFT JOIN users u ON u.id = i.owner_user_id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$init = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$init) die("Initiative not found");

// 4. الصلاحيات
$isOwner = ($init['owner_user_id'] == $_SESSION['user_id']);
$isSuper = ($_SESSION['role_key'] == 'super_admin');
// يمكن للمالك أو السوبر أدمن إدارة المبادرة
$canManage = ($isOwner || $isSuper);
// حالة المسودة أو المعادة
$isDraft = ($init['status_id'] == 8 || $init['status_id'] == 6); 

// 5. جلب بيانات فرعية للعرض
// الأهداف
$objectives = $db->prepare("
    SELECT so.objective_code, so.objective_text 
    FROM initiative_objectives io
    JOIN strategic_objectives so ON so.id = io.strategic_objective_id
    WHERE io.initiative_id = ?
");
$objectives->execute([$id]);
$linkedObjectives = $objectives->fetchAll(PDO::FETCH_ASSOC);

// إحصائيات سريعة
$risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='initiative' AND parent_id=$id AND status_id != 4")->fetchColumn();

// 6. معالجة الإرسال للموافقة (Submit for Approval)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_approval']) && $canManage && $isDraft) {
    try {
        $db->beginTransaction();

        // أ) تحديث الحالة إلى Pending Review (ID = 1)
        $db->prepare("UPDATE initiatives SET status_id = 1 WHERE id = ?")->execute([$id]);

        // ب) بدء الـ Workflow
        // 1. تحديد الـ Workflow المناسب للمبادرات (سنفترض ID = 7 كما اتفقنا، أو 8)
        $workflowId = 7; 

        // 2. تحديد المرحلة الأولى
        $firstStageQ = $db->prepare("SELECT id, stage_name FROM approval_workflow_stages WHERE workflow_id = ? ORDER BY stage_order ASC LIMIT 1");
        $firstStageQ->execute([$workflowId]);
        $firstStage = $firstStageQ->fetch();
        
        if ($firstStage) {
            // 3. إنشاء Instance
            $stmtIns = $db->prepare("
                INSERT INTO approval_instances (entity_type_id, entity_id, current_stage_id, status, created_by)
                VALUES (2, ?, ?, 'in_progress', ?)
            ");
            $stmtIns->execute([$id, $firstStage['id'], $_SESSION['user_id']]);
            
            // 4. إرسال الإشعارات للموافقين (مهم جداً)
            require_once __DIR__ . '/../../modules/approvals/approval_functions.php';
            notifyStageApprovers($firstStage['id'], $id, $init['name']);
        }

        $db->commit();
        
        // إعادة التوجيه لتجنب إعادة الإرسال عند التحديث
        header("Location: view.php?id=$id&tab=approval&msg=submitted");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($init['initiative_code']) ?> - Details</title>
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Artistic Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* Header Card */
        .init-header-card { 
            background: #fff; padding: 30px; border-radius: 16px; margin-bottom: 30px; 
            border-top: 5px solid #ff8c00; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
        }

        .header-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        
        .badges-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .code-badge { background: #fff3e0; color: #e67e22; padding: 4px 10px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; border: 1px solid #ffe0b2; }
        .status-badge { background: <?= $init['status_color'] ?: '#ccc' ?>; color: #fff; padding: 4px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem; }

        .init-title { margin: 0; font-size: 2.2rem; font-weight: 800; color: #2c3e50; line-height: 1.2; }
        
        .meta-row { display: flex; align-items: center; gap: 20px; color: #7f8c8d; font-size: 0.95rem; margin-top: 10px; }
        .meta-item { display: flex; align-items: center; gap: 6px; }
        .meta-item i { color: #b2bec3; }

        /* Stats Row */
        .header-stats { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 20px; margin-top: 30px; padding-top: 25px; border-top: 1px solid #f0f0f0; 
        }
        .h-stat { display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .stat-text div:first-child { font-size: 0.75rem; color: #aaa; font-weight: 700; text-transform: uppercase; }
        .stat-text div:last-child { font-size: 1.1rem; font-weight: 800; color: #2c3e50; }
        
        /* Circular Progress */
        .progress-circle { position: relative; width: 60px; height: 60px; border-radius: 50%; background: conic-gradient(<?= $init['status_color'] ?: '#3498db' ?> <?= $init['progress_percentage'] * 3.6 ?>deg, #f0f0f0 0deg); display: flex; align-items: center; justify-content: center; }
        .progress-circle::before { content: ''; position: absolute; width: 50px; height: 50px; background: #fff; border-radius: 50%; }
        .progress-value { position: relative; font-weight: 800; font-size: 0.9rem; color: #2d3436; }

        /* Tabs */
        .tabs-nav { display: flex; gap: 20px; border-bottom: 2px solid #f0f0f0; margin-bottom: 25px; overflow-x: auto; padding-bottom: 2px; }
        .tab-btn { 
            padding: 12px 5px; background: none; border: none; cursor: pointer; 
            font-size: 1rem; color: #7f8c8d; border-bottom: 3px solid transparent; 
            transition: 0.3s; font-weight: 700; font-family: inherit; white-space: nowrap;
        }
        .tab-btn:hover { color: #ff8c00; }
        .tab-btn.active { color: #ff8c00; border-bottom-color: #ff8c00; }
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        /* Action Buttons */
        .header-actions { display: flex; gap: 10px; }
        .btn-action { padding: 8px 16px; border-radius: 8px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-edit { background: #e3f2fd; color: #3498db; } .btn-edit:hover { background: #3498db; color: #fff; }
        .btn-delete { background: #ffebee; color: #e74c3c; } .btn-delete:hover { background: #e74c3c; color: #fff; }
        .btn-submit { background: #2ecc71; color: #fff; box-shadow: 0 4px 10px rgba(46,204,113,0.2); } .btn-submit:hover { transform: translateY(-2px); }

        /* General styles for overview */
        .overview-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        .info-card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #f0f0f0; margin-bottom: 25px; }
        .info-title { font-size: 1.1rem; font-weight: 700; color: #2c3e50; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
        .list-item { padding: 12px; border-bottom: 1px solid #f9f9f9; display: flex; align-items: center; gap: 10px; }
        .list-bullet { width: 8px; height: 8px; background: #ff8c00; border-radius: 50%; }

        @media (max-width: 1000px) { .overview-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="init-header-card">
        <div class="header-top">
            <div>
                <div class="badges-row">
                    <span class="code-badge"><?= $init['initiative_code'] ?></span>
                    <span class="status-badge"><?= $init['status_name'] ?></span>
                </div>
                <h1 class="init-title"><?= htmlspecialchars($init['name']) ?></h1>
                <div class="meta-row">
                    <div class="meta-item"><i class="fa-solid fa-user-tie"></i> <?= htmlspecialchars($init['owner_name']) ?></div>
                    <div class="meta-item"><i class="fa-solid fa-layer-group"></i> <?= htmlspecialchars($init['pillar_name']) ?></div>
                    <div class="meta-item"><i class="fa-regular fa-calendar"></i> <?= $init['start_date'] ?> &rarr; <?= $init['due_date'] ?></div>
                </div>
            </div>

            <div class="header-actions">
                <?php if($canManage && $isDraft): ?>
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="submit_approval" class="btn-action btn-submit" onclick="return confirm('Submit for approval?')">
                            <i class="fa-solid fa-paper-plane"></i> Submit
                        </button>
                    </form>
                    <a href="edit.php?id=<?= $id ?>" class="btn-action btn-edit"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a href="delete.php?id=<?= $id ?>" class="btn-action btn-delete" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="header-stats">
            <div class="h-stat">
                <div class="progress-circle">
                    <div class="progress-value"><?= $init['progress_percentage'] ?>%</div>
                </div>
                <div class="stat-text">
                    <div>Overall Progress</div>
                    <div style="color:<?= $init['status_color'] ?>"><?= $init['status_name'] ?></div>
                </div>
            </div>

            <div class="h-stat">
                <div class="stat-icon" style="background:#fff3e0; color:#e67e22;"><i class="fa-solid fa-wallet"></i></div>
                <div class="stat-text">
                    <div>Budget Usage</div>
                    <div><?= formatMoney($init['spent_budget']) ?> <span style="font-size:0.8rem; color:#aaa; font-weight:400;">/ <?= formatMoney($init['approved_budget']) ?></span></div>
                </div>
            </div>

            <div class="h-stat">
                <div class="stat-icon" style="background:#e3f2fd; color:#3498db;"><i class="fa-regular fa-clock"></i></div>
                <div class="stat-text">
                    <div>Days Left</div>
                    <div>
                        <?php 
                            $diff = strtotime($init['due_date']) - time();
                            echo max(0, ceil($diff / (60 * 60 * 24))); 
                        ?> Days
                    </div>
                </div>
            </div>

            <div class="h-stat">
                <div class="stat-icon" style="background:#ffebee; color:#e74c3c;"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-text">
                    <div>Active Risks</div>
                    <div style="color:#e74c3c;"><?= $risksCount ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="tabs-nav">
        <button class="tab-btn <?= $activeTab=='overview'?'active':'' ?>" onclick="openTab('overview')">Overview</button>
        <button class="tab-btn <?= $activeTab=='team'?'active':'' ?>" onclick="openTab('team')">Team</button>
        <button class="tab-btn <?= $activeTab=='permissions'?'active':'' ?>" onclick="openTab('permissions')">Permissions</button>
        <button class="tab-btn <?= $activeTab=='milestones'?'active':'' ?>" onclick="openTab('milestones')">Timeline</button>
        <button class="tab-btn <?= $activeTab=='tasks'?'active':'' ?>" onclick="openTab('tasks')">tasks</button>
        <button class="tab-btn <?= $activeTab=='projects'?'active':'' ?>" onclick="openTab('projects')">Projects</button>
        <button class="tab-btn <?= $activeTab=='kpis'?'active':'' ?>" onclick="openTab('kpis')">KPIs</button>
        <button class="tab-btn <?= $activeTab=='risks'?'active':'' ?>" onclick="openTab('risks')">Risks</button>
        <button class="tab-btn <?= $activeTab=='resources'?'active':'' ?>" onclick="openTab('resources')">resources</button>
        <button class="tab-btn <?= $activeTab=='documents'?'active':'' ?>" onclick="openTab('documents')">Documents</button>
        <button class="tab-btn <?= $activeTab=='mom'?'active':'' ?>" onclick="openTab('mom')">Meeting</button>
        <button class="tab-btn <?= $activeTab=='updates'?'active':'' ?>" onclick="openTab('updates')">Updates</button>
        <button class="tab-btn <?= $activeTab=='approval'?'active':'' ?>" onclick="openTab('approval')" style="color:#e67e22;"><i class="fa-solid fa-stamp"></i> Approval</button>
    </div>

    <div id="overview" class="tab-content <?= $activeTab=='overview'?'active':'' ?>">
        <div class="overview-grid">
            <div>
                <div class="info-card">
                    <div class="info-title">Description</div>
                    <p style="color:#555; line-height:1.6;"><?= nl2br(htmlspecialchars($init['description'])) ?></p>
                </div>

                <div class="info-card">
                    <div class="info-title">Strategic Alignment (Objectives)</div>
                    <?php if(empty($linkedObjectives)): ?>
                        <div style="color:#999; font-style:italic;">No objectives linked.</div>
                    <?php else: ?>
                        <?php foreach($linkedObjectives as $obj): ?>
                            <div class="list-item">
                                <span class="list-bullet"></span>
                                <div>
                                    <strong style="color:#2c3e50; font-size:0.9rem;"><?= $obj['objective_code'] ?></strong>
                                    <span style="color:#555; font-size:0.9rem;"> - <?= htmlspecialchars($obj['objective_text']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <?php if($init['approved_budget'] > 0): ?>
                <div class="info-card">
                    <div class="info-title">Financials</div>
                    <div style="background:#fffbf2; border:1px solid #ffe0b2; padding:20px; border-radius:10px; text-align:center; margin-bottom:20px;">
                        <div style="font-size:0.8rem; color:#d35400; font-weight:700; text-transform:uppercase;">Approved Budget</div>
                        <div style="font-size:1.8rem; font-weight:900; color:#e67e22;"><?= formatMoney($init['approved_budget']) ?></div>
                    </div>
                    <div style="font-size:0.9rem; color:#555; display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span>Range:</span>
                        <strong><?= number_format($init['budget_min']) ?> - <?= number_format($init['budget_max']) ?></strong>
                    </div>
                    <div style="font-size:0.9rem; color:#555; display:flex; justify-content:space-between;">
                        <span>Item:</span>
                        <strong><?= htmlspecialchars($init['budget_item'] ?: 'N/A') ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <div class="info-card">
                    <div class="info-title">Details</div>
                    <div style="font-size:0.9rem; color:#555; line-height:2;">
                        <div><strong>Priority:</strong> <span style="text-transform:uppercase; color:#e67e22;"><?= $init['priority'] ?></span></div>
                        <div><strong>Frequency:</strong> <?= ucfirst($init['update_frequency']) ?></div>
                        <div><strong>Created:</strong> <?= date('d M Y', strtotime($init['created_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="team" class="tab-content <?= $activeTab=='team'?'active':'' ?>">
        <?php include "tabs/team.php"; ?>
    </div>
    

    <div id="permissions" class="tab-content <?= $activeTab=='permissions'?'active':'' ?>">
        <?php include "tabs/permissions.php"; ?>
    </div>

    <div id="approval" class="tab-content <?= $activeTab=='approval'?'active':'' ?>">
        <?php include "tabs/approvals.php"; ?>
    </div>
    <div id="milestones" class="tab-content <?= $activeTab=='milestones'?'active':'' ?>">
        <?php include "tabs/milestones.php"; ?>
    </div>
    <div id="tasks" class="tab-content <?= $activeTab=='tasks'?'active':'' ?>">
        <?php include "tabs/tasks.php"; ?>
    </div>

    <div id="projects" class="tab-content <?= $activeTab=='projects'?'active':'' ?>">
        <?php include "tabs/projects.php"; ?>
    </div>
    <div id="kpis" class="tab-content <?= $activeTab=='kpis'?'active':'' ?>">
        <?php include "tabs/kpis.php"; ?>
    </div>
    <div id="risks" class="tab-content <?= $activeTab=='risks'?'active':'' ?>">
        <?php include "tabs/risks.php"; ?>
    </div>
    <div id="resources" class="tab-content <?= $activeTab=='resources'?'active':'' ?>">
        <?php include "tabs/resources.php"; ?>
    </div>
    <div id="documents" class="tab-content <?= $activeTab=='documents'?'active':'' ?>">
        <?php include "tabs/documents.php"; ?>
    </div>
    <div id="mom" class="tab-content <?= $activeTab=='mom'?'active':'' ?>">
        <?php include "tabs/mom.php"; ?>
    </div>
    <div id="updates" class="tab-content"><div style="padding:40px; text-align:center; color:#aaa;">Updates Module Loading...</div></div>

</div>
</div>

<script>
    function openTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabName).classList.add('active');
        
        const buttons = document.querySelectorAll('.tab-btn');
        buttons.forEach(btn => {
            if(btn.getAttribute('onclick').includes(tabName)) {
                btn.classList.add('active');
            }
        });

        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);
    }

    <?php if(isset($_GET['msg'])): ?>
        let msg = "<?= $_GET['msg'] ?>";
        let title = "Success";
        if(msg == 'submitted') title = "Submitted for Approval";
        if(msg == 'member_added') title = "Member Added Successfully";
        if(msg == 'member_removed') title = "Member Removed";
        
        Swal.fire({
            icon: 'success',
            title: title,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    <?php endif; ?>
</script>

</body>
</html>