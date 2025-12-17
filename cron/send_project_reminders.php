<?php
require_once "../core/init.php";

$today = date('Y-m-d');

$stmt = db()->prepare("
    SELECT r.*, p.name, u.full_name_en
    FROM project_update_reminders r
    JOIN operational_projects p ON p.id = r.project_id
    JOIN users u ON u.id = r.manager_id
    WHERE r.next_reminder_date = ?
      AND r.is_active = 1
");
$stmt->execute([$today]);

$reminders = $stmt->fetchAll();

foreach ($reminders as $r) {

    // إنشاء To-Do للمدير
    db()->prepare("
        INSERT INTO user_todos (user_id, title, description)
        VALUES (?, ?, ?)
    ")->execute([
        $r['manager_id'],
        "Project Update Required",
        "Please submit update for project: {$r['name']}"
    ]);

    // تحديث موعد التنبيه القادم
    $next = match ($r['frequency']) {
        'daily' => date('Y-m-d', strtotime('+1 day')),
        'every_2_days' => date('Y-m-d', strtotime('+2 days')),
        'weekly' => date('Y-m-d', strtotime('+1 week')),
        'monthly' => date('Y-m-d', strtotime('+1 month')),
    };

    db()->prepare("
        UPDATE project_update_reminders 
        SET next_reminder_date = ?
        WHERE id = ?
    ")->execute([$next, $r['id']]);
}
