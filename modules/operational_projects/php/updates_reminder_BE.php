<?php
// modules/operational_projects/php/updates_reminder_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php"; 
// نضمن ملف التودو لإرسال التنبيهات الداخلية فقط
require_once __DIR__ . '/../../todos/todo_functions.php';

// 1. التحقق من تسجيل الدخول
if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$project = getProjectById($id);

// 2. التحقق من وجود المشروع
if (!$project) {
    $projectExists = false;
} else {
    $projectExists = true;
}

// ============================================================
// 3. منطق حالة المشروع (Locked Status Logic)
// ============================================================
// الحالات المقفلة: 2 (قيد المراجعة)، 4 (مرفوض)، 7 (معلق)، 8 (مكتمل)
$lockedStatuses = [1, 2, 4, 7, 8]; 
$isLockedStatus = $projectExists && in_array($project['status_id'], $lockedStatuses);

// ============================================================
// 4. التحقق من الصلاحيات (Permissions)
// ============================================================
// العرض: يعتمد على صلاحية لوحة التحكم العامة
$canView = $projectExists && userCanInProject($id, 'proj_view_dashboard');

// الإرسال: يعتمد على الصلاحية الجديدة 'proj_send_update' + المشروع غير مقفل
// (مدير المشروع والسوبر أدمن يملكون هذه الصلاحية تلقائياً عبر userCanInProject)
$canSubmit = $projectExists && userCanInProject($id, 'proj_send_update') && !$isLockedStatus;

// ============================================================
// 5. معالجة الإرسال (POST Handling)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
    
    if ($isLockedStatus) {
        die("Action Denied: Project is locked.");
    }

    if ($canSubmit) {
        $progress = $project['progress_percentage']; 
        $desc = $_POST['description'];
        
        $res = submitProgressUpdate($id, $progress, $desc);
        if ($res['ok']) {
            header("Location: updates_reminder.php?id=$id&msg=sent");
            exit;
        }
    } else {
        die("Access Denied: You do not have permission to submit updates.");
    }
}

// ============================================================
// 6. الدوال المساعدة (Functions)
// ============================================================

/**
 * جلب سجل التحديثات
 */
function getProjectUpdatesHistory($project_id) {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM project_updates WHERE project_id = $project_id ORDER BY created_at DESC")->fetchAll();
}

/**
 * إرسال تحديث جديد (من المدير إلى CEO)
 */
function submitProgressUpdate($project_id, $progress, $description) {
    $db = Database::getInstance()->pdo();
    
    // 1. حفظ التحديث
    $stmt = $db->prepare("
        INSERT INTO project_updates (project_id, user_id, progress_percent, description, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    if ($stmt->execute([$project_id, $_SESSION['user_id'], $progress, $description])) {
        $updateId = $db->lastInsertId();
        
        // 2. تحديث نسبة المشروع (اختياري، لأنها تُحسب أصلاً من المهام، لكن للتأكيد)
        $db->prepare("UPDATE operational_projects SET progress_percentage = ? WHERE id = ?")
            ->execute([$progress, $project_id]);
            
        // 3. إغلاق تذكير المدير (لأنه قام بالمهمة)
        // هذا يغلق التنبيه الذي يطلب من المدير إرسال التحديث
        $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_type = 'project_update' AND related_entity_id = ? AND user_id = ?")
            ->execute([$project_id, $_SESSION['user_id']]);

        // 4. [تعديل] إرسال Todo فقط للرئيس التنفيذي (CEO) بدون إيميل
        $ceoId = $db->query("SELECT id FROM users WHERE primary_role_id = (SELECT id FROM roles WHERE role_key = 'ceo')")->fetchColumn();
        
        if ($ceoId) {
            $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
            
            // استخدام addSystemTodo مباشرة (داخلي فقط)
            addSystemTodo(
                $ceoId, 
                "Project Update: " . substr($pName, 0, 30), 
                "New progress update ($progress%) submitted for review.\n\nSummary: " . substr($description, 0, 50) . "...", 
                "ceo_review", // سيفتح صفحة مراجعة التحديثات (أو نفس الصفحة)
                $updateId
            );
        }

        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Failed to submit update'];
}

// ============================================================
// 7. جلب البيانات ومنطق القراءة التلقائي
// ============================================================
if ($projectExists) {
    
    // إغلاق الإشعار تلقائياً إذا دخل المدير المعني (CEO) وشاهد التحديثات
    if (in_array($_SESSION['role_key'], ['ceo', 'super_admin'])) {
        $db = Database::getInstance()->pdo();
        // جلب آخر تحديث "معلق" لهذا المشروع
        $pendingUpdateId = $db->query("SELECT id FROM project_updates WHERE project_id = $id AND status = 'pending' ORDER BY created_at DESC LIMIT 1")->fetchColumn();
        
        if ($pendingUpdateId) {
            // تحديث حالته إلى "تمت المشاهدة" وإغلاق التنبيه عند الـ CEO
            markUpdateAsViewed($pendingUpdateId);
        }
    }

    $history = getProjectUpdatesHistory($id);
}


?>