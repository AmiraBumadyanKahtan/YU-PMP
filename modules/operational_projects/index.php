<?php
// modules/operational_projects/index.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";
require_once "../../core/GranularAuth.php"; // ✅ استدعاء المحرك الجديد

// ✅ 1. التحقق من صلاحية العرض
if (!Auth::can('proj_view_dashboard')) {
    header("Location: ../../error/403.php");
    exit;
}

// --- [معالجة تغيير الحالة يدوياً (Hold / Resume)] ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_id'])) {
    $pId = (int) $_POST['toggle_status_id'];
    $action = $_POST['action_type']; // 'hold' or 'resume'
    
    // ✅ التحقق باستخدام المحرك الجديد (المدير، رئيس القسم، السوبر)
    // نعتبر تغيير الحالة جزءاً من التعديل الأساسي
    if (GranularAuth::can('project', $pId, 'proj_edit_basic')) {
        toggleProjectHold($pId, $action);
        header("Location: index.php?msg=status_updated");
        exit;
    } else {
        // إذا حاول التحايل
        header("Location: index.php?msg=error");
        exit;
    }
}
// --- [نهاية المعالجة] ---

// Filters
$filters = [
    'search'        => $_GET['search'] ?? '',
    'department_id' => $_GET['department_id'] ?? '',
    'status_id'     => $_GET['status_id'] ?? '',
    'manager_id'    => '' 
];

// Fetch Data
$projects = getProjects($filters);

// Dropdowns
$db = Database::getInstance()->pdo();
$departments = $db->query("SELECT id, name FROM departments WHERE is_deleted=0 ORDER BY name")->fetchAll();
$statuses = $db->query("SELECT id, name FROM operational_project_statuses ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Operational Projects</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/projects_index.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:wght@400;600&family=Cairo:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        .is-collab {
            font-size: 0.75rem; background: #e0f2fe; color: #0284c7; 
            padding: 2px 6px; border-radius: 4px; margin-left: 5px; border: 1px solid #bae6fd;
        }
        /* Progress Bar Styles */
        .progress-track { background: #e5e7eb; height: 6px; width: 100px; border-radius: 3px; display: inline-block; vertical-align: middle; margin-right: 5px; }
        .progress-fill { height: 100%; border-radius: 3px; background: #10b981; }
        .progress-text { font-size: 0.8rem; color: #6b7280; font-weight: 600; }
        
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-diagram-project"></i> Operational Projects
        </h1>
        
        <?php if (Auth::can('proj_create')): ?>
            <a href="create.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> New Project
            </a>
        <?php endif; ?>
    </div>

    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="filter-input" placeholder="Search Code or Name..." value="<?= htmlspecialchars($filters['search']) ?>">
        
        <select name="department_id" class="filter-select">
            <option value="">All Departments</option>
            <?php foreach($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $filters['department_id'] == $d['id'] ? 'selected':'' ?>><?= $d['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status_id" class="filter-select">
            <option value="">All Statuses</option>
            <?php foreach($statuses as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filters['status_id'] == $s['id'] ? 'selected':'' ?>><?= $s['name'] ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-primary" style="height: 42px;">Filter</button>
        
        <?php if(!empty($filters['search']) || !empty($filters['department_id']) || !empty($filters['status_id'])): ?>
            <a href="index.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Project Name</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Progress</th> <th>Priority</th>
                    <th>Visibility</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 40px; color: #999;">
                            <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px; display: block; color: #ddd;"></i>
                            No projects found matching your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($projects as $p): ?>
                        <tr>
                            <td style="font-weight: bold; color: #555;"><?= htmlspecialchars($p['project_code']) ?></td>
                            <td style="text-align: left; font-weight: 600;">
                                <a href="view.php?id=<?= $p['id'] ?>" style="color:#2c3e50; text-decoration:none;">
                                    <?= htmlspecialchars($p['name']) ?>
                                </a>
                                <?php if(isset($p['is_team_member']) && $p['is_team_member'] && $p['manager_id'] != $_SESSION['user_id']): ?>
                                    <span class="is-collab" title="You are a team member"><i class="fa-solid fa-users"></i> Team</span>
                                <?php endif; ?>
                                <div style="font-size: 0.8rem; color:#777; font-weight: normal; margin-top:2px;">
                                    Mgr: <?= htmlspecialchars($p['manager_name'] ?? 'Unassigned') ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['department_name'] ?? '-') ?></td>
                            <td>
                                <span class="status-badge" style="background-color: <?= $p['status_color'] ?: '#95a5a6' ?>;">
                                    <?= htmlspecialchars($p['status_name']) ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php $pct = $p['progress_percentage'] ?? 0; ?>
                                <div style="display:flex; align-items:center;">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?= $pct ?>%; background: <?= ($pct==100)?'#10b981': (($pct < 30)?'#ef4444':'#3b82f6') ?>"></div>
                                    </div>
                                    <span class="progress-text"><?= $pct ?>%</span>
                                </div>
                            </td>

                            <td>
                                <?php 
                                    $prioClass = 'prio-' . strtolower($p['priority']); 
                                    echo "<span class='$prioClass'>" . ucfirst($p['priority']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <?php if ($p['visibility'] == 'public'): ?>
                                    <span class="vis-badge vis-public"><i class="fa-solid fa-globe"></i> Public</span>
                                <?php else: ?>
                                    <span class="vis-badge"><i class="fa-solid fa-lock"></i> Private</span>
                                <?php endif; ?>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="view.php?id=<?= $p['id'] ?>" class="action-btn btn-view" title="View Details"><i class="fa-solid fa-eye"></i></a>
                                
                                <?php 
                                // ✅ 3. فحص صلاحية التعديل باستخدام المحرك الجديد
                                // GranularAuth::can(النوع, الـ ID, الصلاحية)
                                // هذا الفحص يشمل: السوبر أدمن + مدير المشروع + رئيس القسم
                                // ✅ استخدام القيمة المحسوبة من الاستعلام (بدون اتصال جديد بقاعدة البيانات)
                                $canModify = ($p['can_edit_calculated'] == 1); 

                                
                                if ($canModify): ?>
                                    <a href="edit.php?id=<?= $p['id'] ?>" class="action-btn btn-edit" title="Edit Project"><i class="fa-solid fa-pen"></i></a>
                                    
                                    <?php if ($p['status_id'] == 6): // In Progress -> Show Hold ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Put this project On Hold?');">
                                            <input type="hidden" name="toggle_status_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action_type" value="hold">
                                            <button type="submit" class=" btn-hold action-btn" title="Put On Hold"><i class="fa-solid fa-pause"></i></button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($p['status_id'] == 7): // On Hold -> Show Resume ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Resume this project?');">
                                            <input type="hidden" name="toggle_status_id" value="<?= $p['id'] ?>">
                                            <input type="hidden" name="action_type" value="resume">
                                            <button type="submit" class="action-btn btn-resume" title="Resume Project"><i class="fa-solid fa-play"></i></button>
                                        </form>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <script>
        const msg = "<?= $_GET['msg'] ?>";
        let title = 'Operation Successful';
        if(msg == 'created') title = 'Project created successfully';
        if(msg == 'updated') title = 'Project updated successfully';
        if(msg == 'deleted') title = 'Project deleted successfully';
        if(msg == 'status_updated') title = 'Project status updated';
        if(msg == 'error') title = 'Action Failed (Access Denied)';

        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        Toast.fire({icon: msg == 'error' ? 'error' : 'success', title: title});
    </script>
<?php endif; ?>

</body>
</html>