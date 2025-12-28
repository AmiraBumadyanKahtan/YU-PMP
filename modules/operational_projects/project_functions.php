<?php
// modules/operational_projects/project_functions.php

// تضمين الملفات الفرعية بالترتيب الصحيح
require_once __DIR__ . '/project_core.php';
require_once __DIR__ . '/project_milestones.php';
require_once __DIR__ . '/project_team.php';
require_once __DIR__ . '/project_approvals.php';
require_once __DIR__ . '/project_kpis.php';
require_once __DIR__ . '/project_risks.php';
require_once __DIR__ . '/project_doc.php';
require_once __DIR__ . '/project_resources.php';
require_once __DIR__ . '/project_update_reminders.php';
require_once __DIR__ . '/notification_helper.php'; // <--- ضروري: تضمين ملف الإشعارات الجديد
// ==========================================
// Automated Status & Logic Functions
// ==========================================

function autoUpdateProjectStatus($project) {
    $db = Database::getInstance()->pdo();
    $pid = $project['id'];
    $currentStatus = $project['status_id'];
    $today = date('Y-m-d');
    
    $changed = false;
    $newStatusId = $currentStatus;

    // إحصائيات المهام
    $totalTasks = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id = $pid AND is_deleted=0")->fetchColumn();
    $incompleteTasks = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id = $pid AND status_id != 3 AND is_deleted=0")->fetchColumn();
    $workStarted = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id = $pid AND status_id IN (2,3) AND is_deleted=0")->fetchColumn();
    
    // [هام جداً] إحصائيات المراحل (للتحقق من الإلزامية)
    $milestonesCount = $db->query("SELECT COUNT(*) FROM project_milestones WHERE project_id = $pid AND is_deleted=0")->fetchColumn();

    // --- السيناريو 1: التحويل من Approved (5) إلى In Progress (6) ---
    if ($currentStatus == 5) {
        $dateStarted = ($project['start_date'] && $project['start_date'] <= $today);
        if ($dateStarted || $workStarted > 0) {
            $newStatusId = 6; 
            $changed = true;
        }
    }

    // --- السيناريو 2: التحويل من In Progress (6) إلى Completed (8) ---
    if ($currentStatus == 6) {
        // الشروط المعدلة:
        // 1. يوجد مهام.
        // 2. جميع المهام مكتملة.
        // 3. [الشرط الجديد] يوجد مرحلة واحدة على الأقل.
        if ($totalTasks > 0 && $incompleteTasks == 0 && $milestonesCount > 0) {
            $newStatusId = 8; 
            $changed = true;

            // إشعار الانتهاء
            $ceoId = $db->query("SELECT id FROM users WHERE primary_role_id = (SELECT id FROM roles WHERE role_key = 'ceo')")->fetchColumn();
            if ($ceoId) {
                sendProjectNotification($ceoId, "Project Completed: " . $project['name'], "The project is fully completed.", "project_view", $pid);
            }

            if (!empty($project['department_id'])) {
                $deptHeadId = $db->query("SELECT manager_id FROM departments WHERE id = " . $project['department_id'])->fetchColumn();
                if ($deptHeadId) {
                    sendProjectNotification($deptHeadId, "Project Completed: " . $project['name'], "Project completion requires final review.", "project_view", $pid);
                }
            }
        }
    }

    // --- السيناريو 3: العودة من Completed (8) إلى In Progress (6) ---
    // إذا أضاف مهام جديدة، أو حذف المراحل كلها بالخطأ
    if ($currentStatus == 8) {
        if ($incompleteTasks > 0 || $milestonesCount == 0) {
            $newStatusId = 6;
            $changed = true;
        }
    }

    if ($changed) {
        $db->prepare("UPDATE operational_projects SET status_id = ? WHERE id = ?")->execute([$newStatusId, $pid]);
        return $db->query("SELECT id, name, color FROM operational_project_statuses WHERE id = $newStatusId")->fetch(PDO::FETCH_ASSOC);
    }

    return false;
}

// دالة التبديل اليدوي
function toggleProjectHold($project_id, $action) {
    $db = Database::getInstance()->pdo();
    if ($action == 'hold') {
        $sql = "UPDATE operational_projects SET status_id = 7 WHERE id = ? AND status_id = 6";
    } elseif ($action == 'resume') {
        $sql = "UPDATE operational_projects SET status_id = 6 WHERE id = ? AND status_id = 7";
    } else {
        return false;
    }
    $stmt = $db->prepare($sql);
    return $stmt->execute([$project_id]);
}

// ==========================================
// Permissions & Access Control Functions
// ==========================================

function userCanInProject($project_id, $permission_key, $user_id = null) {
    if (!$user_id) $user_id = $_SESSION['user_id'];
    $role_key = $_SESSION['role_key'] ?? '';
    $db = Database::getInstance()->pdo();

    // 1. صلاحيات "سوبر" (دائماً نعم) - هذا يحل مشكلة السوبر أدمن
    if (in_array($role_key, ['super_admin', 'ceo', 'strategy_office', 'pmo_manager'])) return true;

    // بقية الفحوصات (مدير، رئيس قسم، صلاحيات خاصة، فريق)
    $stmt = $db->prepare("SELECT manager_id, department_id FROM operational_projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $proj = $stmt->fetch();

    if (!$proj) return false;
    if ($proj['manager_id'] == $user_id) return true;

    $deptStmt = $db->prepare("SELECT manager_id FROM departments WHERE id = ?");
    $deptStmt->execute([$proj['department_id']]);
    $deptManager = $deptStmt->fetchColumn();
    if ($deptManager == $user_id) return true;

    $permIdStmt = $db->prepare("SELECT id FROM permissions WHERE permission_key = ?");
    $permIdStmt->execute([$permission_key]);
    $permId = $permIdStmt->fetchColumn();
    
    if (!$permId) return false;

    $overrideStmt = $db->prepare("SELECT is_granted FROM project_user_permissions WHERE project_id=? AND user_id=? AND permission_id=?");
    $overrideStmt->execute([$project_id, $user_id, $permId]);
    $override = $overrideStmt->fetchColumn();

    if ($override !== false) return ($override == 1);

    $roleStmt = $db->prepare("
        SELECT 1 FROM project_team pt
        JOIN project_role_permissions prp ON prp.role_id = pt.role_id
        WHERE pt.project_id = ? AND pt.user_id = ? AND prp.permission_id = ? AND pt.is_active = 1
    ");
    $roleStmt->execute([$project_id, $user_id, $permId]);
    
    return (bool) $roleStmt->fetchColumn();
}

function getProjectPermissionsList() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM permissions WHERE module = 'projects' ORDER BY permission_key")->fetchAll();
}

function isProjectEditable($status_id) {
    $lockedStatuses = [2, 4, 8, 7]; 
    if (in_array($status_id, $lockedStatuses)) return false;
    return true; 
}
// ==========================================
// [NEW] Notification Logic Wrappers
// ==========================================
// هذه الدوال الجديدة تستخدم نظام الإشعارات الموحد (notification_helper.php)

/**
 * إشعار عند إضافة/إزالة عضو من الفريق
 * يجب استدعاء هذه الدالة في صفحة team.php
 */
function notifyTeamChange($project_id, $user_id, $action) {
    $db = Database::getInstance()->pdo();
    $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
    
    $title = ($action == 'added') ? "Added to Project" : "Removed from Project";
    $msg   = ($action == 'added') 
             ? "You have been added to the project team: $pName" 
             : "You have been removed from the project team: $pName";
             
    sendProjectNotification($user_id, $title, $msg, "project_view", $project_id);
}

/**
 * إشعار عند توكيل مهمة جديدة لمستخدم
 * يجب استدعاء هذه الدالة في صفحة tasks.php
 */
function notifyTaskAssignment($task_id, $user_id, $project_id) {
    $db = Database::getInstance()->pdo();
    $taskTitle = $db->query("SELECT title FROM project_tasks WHERE id = $task_id")->fetchColumn();
    
    sendProjectNotification(
        $user_id, 
        "New Task Assigned", 
        "You have been assigned a new task: $taskTitle", 
        "task_view", 
        $task_id
    );
}

/**
 * إشعار بنتيجة الموافقة (Approval Result)
 * يرسل للمدير ورئيس القسم عند القبول أو الرفض في ستيج معين
 */
function notifyApprovalResult($project_id, $stage_name, $status) {
    $db = Database::getInstance()->pdo();
    $proj = $db->query("SELECT manager_id, department_id, name FROM operational_projects WHERE id = $project_id")->fetch(PDO::FETCH_ASSOC);
    
    $title = "Approval Update: " . $proj['name'];
    $msg = "The stage '$stage_name' has been " . strtoupper($status) . ".";

    // 1. إشعار مدير المشروع
    if ($proj['manager_id']) {
        sendProjectNotification($proj['manager_id'], $title, $msg, "project_approvals", $project_id);
    }

    // 2. إشعار رئيس القسم (إذا لم يكن هو المدير)
    $deptHead = $db->query("SELECT manager_id FROM departments WHERE id = " . $proj['department_id'])->fetchColumn();
    if ($deptHead && $deptHead != $proj['manager_id']) {
        sendProjectNotification($deptHead, $title, $msg, "project_approvals", $project_id);
    }
}

/**
 * دالة فحص التأخير الشامل (مشروع، مهام)
 * يفضل تشغيلها دورياً (مثلاً عند تحميل الداشبورد أو عبر Cron Job)
 */
function checkAndNotifyDelays($project_id) {
    $db = Database::getInstance()->pdo();
    $today = date('Y-m-d');
    
    // 1. فحص تأخير المشروع ككل
    $proj = $db->query("SELECT * FROM operational_projects WHERE id = $project_id")->fetch();
    if ($proj['end_date'] < $today && $proj['status_id'] != 8) {
        // نرسل تنبيهاً للمدير
        sendProjectNotification(
            $proj['manager_id'], 
            "Project Delay Alert", 
            "The project '{$proj['name']}' is past its due date ({$proj['end_date']}).", 
            "project_view", 
            $project_id
        );
    }

    // 2. فحص تأخير المهام
    $delayedTasks = $db->query("SELECT id, title, assigned_to FROM project_tasks WHERE project_id = $project_id AND due_date < '$today' AND status_id != 3 AND is_deleted=0")->fetchAll();
    foreach ($delayedTasks as $task) {
        if ($task['assigned_to']) {
            // إشعار للموظف المسؤول
            sendProjectNotification(
                $task['assigned_to'], 
                "Overdue Task Alert", 
                "Task '{$task['title']}' is overdue. Please update status.", 
                "task_view", 
                $task['id']
            );
        }
        // نسخة للمدير أيضاً
        sendProjectNotification(
            $proj['manager_id'], 
            "Task Delay Alert", 
            "Task '{$task['title']}' assigned to user #{$task['assigned_to']} is overdue.", 
            "task_view", 
            $task['id']
        );
    }
}

?>