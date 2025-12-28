<?php
// cron/daily_alerts_cron.php

// 1. تضمين ملفات الاتصال والإعدادات (عدل المسارات حسب مكان ملفك)
require_once __DIR__ . '/../core/config.php';
// نحتاج كلاس قاعدة البيانات فقط (بدون Auth لأن الكرون ليس مستخدماً مسجلاً)
require_once __DIR__ . '/../core/db_class.php'; 
require_once __DIR__ . '/../modules/operational_projects/notification_helper.php';

// تأكد من ضبط التوقيت
date_default_timezone_set('Asia/Riyadh');

echo "--- Start Daily Check: " . date('Y-m-d H:i:s') . " ---\n";

$db = Database::getInstance()->pdo();
$today = date('Y-m-d');

// ========================================================
// أولاً: فحص المشاريع المتأخرة
// ========================================================
// نجلب المشاريع التي انتهى وقتها ولم تكتمل (الحالة ليست 8-مكتمل ولا 4-مرفوض ولا 1-مسودة)
$sqlProjects = "
    SELECT id, name, manager_id, end_date 
    FROM operational_projects 
    WHERE end_date < ? 
    AND status_id NOT IN (1, 4, 8) 
    AND is_deleted = 0
";
$stmtProj = $db->prepare($sqlProjects);
$stmtProj->execute([$today]);
$delayedProjects = $stmtProj->fetchAll();

foreach ($delayedProjects as $proj) {
    // إرسال إشعار للمدير
    if ($proj['manager_id']) {
        sendProjectNotification(
            $proj['manager_id'], 
            "⚠️ Project Overdue Alert", 
            "The project '{$proj['name']}' has passed its deadline ({$proj['end_date']}). Please update the timeline or status.", 
            "project_view", 
            $proj['id']
        );
        echo " - Project Alert sent for: {$proj['name']}\n";
    }
}

// ========================================================
// ثانياً: فحص المهام المتأخرة
// ========================================================
// نجلب المهام التي انتهى وقتها ولم تكتمل (الحالة ليست 3-Completed)
$sqlTasks = "
    SELECT t.id, t.title, t.assigned_to, t.project_id, p.name as project_name, p.manager_id
    FROM project_tasks t
    JOIN operational_projects p ON p.id = t.project_id
    WHERE t.due_date < ? 
    AND t.status_id != 3 
    AND t.is_deleted = 0
    AND p.status_id NOT IN (1, 4, 8) 
";
$stmtTask = $db->prepare($sqlTasks);
$stmtTask->execute([$today]);
$delayedTasks = $stmtTask->fetchAll();

foreach ($delayedTasks as $task) {
    // 1. إشعار للموظف المسؤول (Assignee)
    if ($task['assigned_to']) {
        sendProjectNotification(
            $task['assigned_to'], 
            "⚠️ Task Overdue", 
            "Your task '{$task['title']}' in project '{$task['project_name']}' is overdue. Please update progress.", 
            "task_view", 
            $task['id']
        );
    }

    // 2. إشعار لمدير المشروع (للعلم والإحاطة)
    if ($task['manager_id']) {
        sendProjectNotification(
            $task['manager_id'], 
            "⚠️ Task Delay Alert", 
            "Task '{$task['title']}' assigned to user #{$task['assigned_to']} is overdue.", 
            "task_view", 
            $task['id']
        );
    }
    echo " - Task Alert sent for: {$task['title']}\n";
}

echo "--- Check Completed Successfully ---\n";
?>