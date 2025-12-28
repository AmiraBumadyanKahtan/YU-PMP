<?php

// =========================================================
// 7. PROJECT UPDATES & REMINDERS (CEO REPORTING)
// =========================================================

/**
 * التحقق من التذكيرات وإرسالها لمدراء المشاريع
 * (يتم استدعاء هذه الدالة في الهيدر أو الداشبورد)
 */
/*function checkAndSendProjectReminders() {
    $db = Database::getInstance()->pdo();
    $today = date('Y-m-d');

    // 1. جلب التذكيرات المستحقة اليوم
    $sql = "
        SELECT r.*, p.name as project_name 
        FROM project_update_reminders r
        JOIN operational_projects p ON p.id = r.project_id
        WHERE r.next_reminder_date <= ? AND r.is_active = 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$today]);
    $reminders = $stmt->fetchAll();

    foreach ($reminders as $r) {
        // إرسال Todo للمدير
        require_once __DIR__ . '/../../modules/todos/todo_functions.php';
        
        // التحقق من عدم وجود تذكير سابق مفتوح لنفس المشروع
        // (لتجنب التكرار إذا لم يدخل المدير للنظام ليومين)
        $checkTodo = $db->prepare("SELECT id FROM user_todos WHERE related_entity_type = 'project_update' AND related_entity_id = ? AND is_completed = 0");
        $checkTodo->execute([$r['project_id']]);
        
        if (!$checkTodo->fetch()) {
            addSystemTodo(
                $r['manager_id'],
                "Update Required: " . $r['project_name'],
                "It's time to submit your periodic progress update.",
                "project_update", // سيتم توجيهه لصفحة التحديث
                $r['project_id'],
                $today
            );
        }

        // تحديث تاريخ التذكير القادم
        $nextDate = $today;
        if ($r['frequency'] == 'daily') $nextDate = date('Y-m-d', strtotime('+1 day'));
        elseif ($r['frequency'] == 'every_2_days') $nextDate = date('Y-m-d', strtotime('+2 days'));
        elseif ($r['frequency'] == 'weekly') $nextDate = date('Y-m-d', strtotime('+1 week'));
        elseif ($r['frequency'] == 'monthly') $nextDate = date('Y-m-d', strtotime('+1 month'));

        $upd = $db->prepare("UPDATE project_update_reminders SET next_reminder_date = ? WHERE id = ?");
        $upd->execute([$nextDate, $r['id']]);
    }
}*/

/**
 * إرسال تحديث من مدير المشروع
 *//*
function submitProgressUpdate($project_id, $progress, $description) {
    $db = Database::getInstance()->pdo();
    
    // 1. حفظ التحديث
    $stmt = $db->prepare("
        INSERT INTO project_updates (project_id, user_id, progress_percent, description, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    if ($stmt->execute([$project_id, $_SESSION['user_id'], $progress, $description])) {
        $updateId = $db->lastInsertId();
        
        // 2. تحديث نسبة إنجاز المشروع الفعلية
        $db->prepare("UPDATE operational_projects SET progress_percentage = ? WHERE id = ?")
           ->execute([$progress, $project_id]);
           
        // 3. إغلاق التذكير في الـ Todo (إذا وجد)
        $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_type = 'project_update' AND related_entity_id = ?")
           ->execute([$project_id]);

        // 4. إرسال إشعار للرئيس التنفيذي (CEO)
        // نحتاج لمعرفة من هو الـ CEO (role_key = 'ceo')
        $ceoId = $db->query("SELECT id FROM users WHERE primary_role_id = (SELECT id FROM roles WHERE role_key = 'ceo')")->fetchColumn();
        
        if ($ceoId) {
            $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
            require_once __DIR__ . '/../../modules/todos/todo_functions.php';
            
            addSystemTodo(
                $ceoId, 
                "Project Update: $pName", 
                "New progress update ($progress%) submitted for review.", 
                "ceo_review", 
                $updateId // نرسل رقم التحديث
            );
        }

        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Failed to submit update'];
}*/
/**
 * [محدث] التحقق من التذكيرات وإرسالها لمدراء المشاريع
 * الآن ترسل إيميل أيضاً باستخدام sendProjectNotification
 */
function checkAndSendProjectReminders() {
    $db = Database::getInstance()->pdo();
    $today = date('Y-m-d');

    // جلب التذكيرات المستحقة
    $sql = "
        SELECT r.*, p.name as project_name 
        FROM project_update_reminders r
        JOIN operational_projects p ON p.id = r.project_id
        WHERE r.next_reminder_date <= ? AND r.is_active = 1
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$today]);
    $reminders = $stmt->fetchAll();

    foreach ($reminders as $r) {
        // التحقق من عدم وجود تذكير سابق مفتوح
        $checkTodo = $db->prepare("SELECT id FROM user_todos WHERE related_entity_type = 'project_update' AND related_entity_id = ? AND is_completed = 0");
        $checkTodo->execute([$r['project_id']]);
        
        if (!$checkTodo->fetch()) {
            // [MODIFIED] استخدام دالة الإشعارات (إيميل + تودو)
            if (function_exists('sendProjectNotification')) {
                sendProjectNotification(
                    $r['manager_id'],
                    "Update Required: " . $r['project_name'],
                    "It's time to submit your periodic progress update for project: {$r['project_name']}.",
                    "project_update", // الرابط يودي لصفحة الإرسال
                    $r['project_id']
                );
            }
        }

        // تحديث التاريخ القادم
        $nextDate = $today;
        if ($r['frequency'] == 'daily') $nextDate = date('Y-m-d', strtotime('+1 day'));
        elseif ($r['frequency'] == 'every_2_days') $nextDate = date('Y-m-d', strtotime('+2 days'));
        elseif ($r['frequency'] == 'weekly') $nextDate = date('Y-m-d', strtotime('+1 week'));
        elseif ($r['frequency'] == 'monthly') $nextDate = date('Y-m-d', strtotime('+1 month'));

        $db->prepare("UPDATE project_update_reminders SET next_reminder_date = ? WHERE id = ?")->execute([$nextDate, $r['id']]);
    }
}
/**
 * وضع التحديث كـ "تمت المشاهدة" (للرئيس التنفيذي)
 */
/**
 * وضع التحديث كـ "تمت المشاهدة"
 *//*
function markUpdateAsViewed($updateId) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("UPDATE project_updates SET status = 'viewed' WHERE id = ?");
    $stmt->execute([$updateId]);
    
    // إغلاق إشعار الـ CEO
    $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_type = 'ceo_review' AND related_entity_id = ? AND user_id = ?")
       ->execute([$updateId, $_SESSION['user_id']]);
}*/
/**
 * وضع التحديث كـ "تمت المشاهدة"
 */
function markUpdateAsViewed($updateId) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("UPDATE project_updates SET status = 'viewed' WHERE id = ?");
    $stmt->execute([$updateId]);
    
    // إغلاق تنبيه الـ CEO المرتبط بهذا التحديث
    $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_type = 'ceo_review' AND related_entity_id = ? AND user_id = ?")
       ->execute([$updateId, $_SESSION['user_id']]);
}
?>