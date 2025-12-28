<?php
// modules/operational_projects/php/permissions_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);
if (!$project) die("Project not found");

// ============================================================
// 1. منطق حالة المشروع
// ============================================================
$lockedStatuses = [1, 2, 4, 8, 7]; 
$isLockedStatus = in_array($project['status_id'], $lockedStatuses);

// ============================================================
// 2. التحقق من الصلاحية (Access Check)
// ============================================================
// هل المستخدم يملك صلاحية إدارة الأذونات؟ (السوبر أدمن + مدير المشروع + من يملك الصلاحية)
$canManageAccess = userCanInProject($id, 'proj_manage_permissions');

if (!$canManageAccess) {
    include "../../layout/header.php";
    echo "<div class='main-content'><div class='page-wrapper'>";
    include "project_header_inc.php";
    echo "<div class='alert alert-danger' style='margin-top:20px;'><i class='fa-solid fa-lock'></i> <strong>Access Denied:</strong> You do not have permission to manage permissions for this project.</div>";
    echo "</div></div>";
    exit;
}

// هل يمكنه التعديل؟ (يجب أن يملك الصلاحية + المشروع غير مقفل)
$canEdit = $canManageAccess && !$isLockedStatus;


// ============================================================
// 3. معالجة التغييرات (Update Permissions)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_user_perm'])) {
    if (!$canEdit) die("Action Denied: Project is locked.");

    $targetUserId = $_POST['user_id'];
    $permId = $_POST['permission_id'];
    $action = $_POST['action']; // grant, deny, reset

    // لا تسمح بتعديل صلاحيات مدير المشروع نفسه (لتجنب الحظر الذاتي الخطأ)
    if ($targetUserId == $project['manager_id']) {
        header("Location: permissions.php?id=$id&msg=error_owner");
        exit;
    }

    $db = Database::getInstance()->pdo();
    
    if ($action == 'reset') {
        // حذف الاستثناء والعودة للافتراضي
        $stmt = $db->prepare("DELETE FROM project_user_permissions WHERE project_id=? AND user_id=? AND permission_id=?");
        $stmt->execute([$id, $targetUserId, $permId]);
    } else {
        // 1 = Grant (Allow), 0 = Deny (Forbid)
        $isGranted = ($action == 'grant') ? 1 : 0;
        $stmt = $db->prepare("
            INSERT INTO project_user_permissions (project_id, user_id, permission_id, is_granted, created_at) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE is_granted = ?
        ");
        $stmt->execute([$id, $targetUserId, $permId, $isGranted, $isGranted]);
    }
    
    header("Location: permissions.php?id=$id&msg=updated");
    exit;
}

// ============================================================
// 4. جلب البيانات للعرض (Filtering & Mapping)
// ============================================================
$db = Database::getInstance()->pdo();

// أ) جلب الصلاحيات الخاصة بالمشاريع فقط
// نحدد الـ modules التي نريدها فقط
$allowedModules = [
    'projects', 
    'project_tasks', 
    'project_milestones', 
    'project_kpis', 
    'project_risks', 
    'project_docs', 
    'project_resources'
];
// تحويل المصفوفة لنص لاستخدامه في الاستعلام IN ('...', '...')
$inQuery = "'" . implode("','", $allowedModules) . "'";

$allPerms = $db->query("
    SELECT * FROM permissions 
    WHERE module IN ($inQuery) 
    ORDER BY module, id
")->fetchAll();

// ب) جلب أعضاء الفريق
$teamMembers = getProjectTeam($id);

// ج) جلب الاستثناءات الحالية (Overrides) لهذا المشروع
$overrides = [];
$stmtOv = $db->prepare("SELECT user_id, permission_id, is_granted FROM project_user_permissions WHERE project_id = ?");
$stmtOv->execute([$id]);
while($row = $stmtOv->fetch()) {
    $overrides[$row['user_id']][$row['permission_id']] = $row['is_granted'];
}

// د) جلب الافتراضيات (Defaults) من أدوار المشروع
// نستخدم جدول project_role_permissions وليس role_permissions العام
$roleDefaults = [];
$stmtDef = $db->query("SELECT role_id, permission_id FROM project_role_permissions");
while($row = $stmtDef->fetch()) {
    $roleDefaults[$row['role_id']][] = $row['permission_id'];
}

// هـ) مصفوفة الأسماء المفهومة (Readable Names Mapping)
function getReadableName($key) {
    $map = [
        // Projects General
        'proj_view_dashboard' => 'View Dashboard',
        'proj_edit_basic' => 'Edit Details',
        'proj_manage_team' => 'Manage Team',
        'proj_submit_approval' => 'Submit Approval',
        'proj_delete' => 'Delete Project',
        'proj_send_update' => 'Send Updates',
        'proj_create' => 'Create Project',
        'proj_manage_permissions' => 'Manage Permissions',
        
        // Tasks
        'ptask_view' => 'View Tasks',
        'ptask_create' => 'Create Tasks',
        'ptask_edit' => 'Edit Tasks',
        'ptask_delete' => 'Delete Tasks',
        'ptask_update_progress' => 'Update Progress',

        // Milestones
        'pmilestone_manage' => 'Manage Milestones',

        // KPIs
        'pkpi_view' => 'View KPIs',
        'pkpi_manage' => 'Manage KPIs',
        'pkpi_update_reading' => 'Update Readings',

        // Risks
        'prisk_manage' => 'Manage Risks',
        'prisk_create' => 'Create Risk',

        // Docs
        'pdoc_manage' => 'Upload Docs',
        'pdoc_delete' => 'Delete Docs',

        // Resources
        'presource_manage' => 'Manage Resources'
    ];

    return $map[$key] ?? ucwords(str_replace('_', ' ', $key));
}
?>