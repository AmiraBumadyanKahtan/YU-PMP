<?php

// =========================================================
// 4. KPI MANAGEMENT (WITH REMINDERS)
// =========================================================

/**
 * جلب مؤشرات الأداء للمشروع
 */
function getProjectKPIs($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "
        SELECT k.*, s.status_name, s.id as status_id_code, u.full_name_en as owner_name, u.avatar
        FROM kpis k
        LEFT JOIN kpi_statuses s ON s.id = k.status_id
        LEFT JOIN users u ON u.id = k.owner_id
        WHERE k.parent_type = 'project' AND k.parent_id = ? AND k.is_deleted = 0
        ORDER BY k.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * إنشاء مؤشر جديد + إضافة تذكير (Todo)
 */
// استبدل دالة createKPI القديمة بهذه:
function createKPI($data) {
    $db = Database::getInstance()->pdo();
    
    $stmt = $db->prepare("
        INSERT INTO kpis 
        (name, description, target_value, current_value, baseline_value, unit, frequency, data_source, 
         parent_type, parent_id, status_id, owner_id, created_at, updated_at)
        VALUES 
        (:name, :desc, :target, :base, :base, :unit, :freq, :source,
         'project', :pid, 1, :owner, NOW(), NOW())
    ");
    // ملاحظة: جعلنا القيمة الحالية تبدأ من الـ baseline
    
    if ($stmt->execute([
        ':name'    => $data['name'],
        ':desc'    => $data['description'],
        ':target'  => $data['target_value'],
        ':base'    => $data['baseline_value'] ?? 0,
        ':unit'    => $data['unit'],
        ':freq'    => $data['frequency'],
        ':source'  => $data['data_source'] ?? '', // الحقل الجديد
        ':pid'     => $data['project_id'],
        ':owner'   => $data['owner_id']
    ])) {
        $kpiId = $db->lastInsertId();
        scheduleKPIReminder($kpiId, $data['owner_id'], $data['name'], $data['frequency']);
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Database error'];
}

/**
 * تحديث قراءة المؤشر + تحديث الحالة + تجديد التذكير
 */
function updateKPIReading($kpi_id, $new_value, $note = "") {
    $db = Database::getInstance()->pdo();
    
    // جلب البيانات القديمة لحساب الحالة
    $kpi = $db->query("SELECT * FROM kpis WHERE id = $kpi_id")->fetch();
    
    // حساب الحالة تلقائياً
    // 4=Achieved, 1=On Track, 2=At Risk, 3=Needs Work
    $status_id = 1; // Default On Track
    $target = $kpi['target_value'];
    
    if ($target != 0) {
        $percent = ($new_value / $target) * 100;
        if ($percent >= 100) $status_id = 4; // Achieved
        elseif ($percent >= 80) $status_id = 1; // On Track
        elseif ($percent >= 50) $status_id = 3; // Needs Work
        else $status_id = 2; // At Risk
    }

    // التحديث
    $upd = $db->prepare("UPDATE kpis SET current_value = ?, status_id = ?, last_updated = NOW(), updated_at = NOW() WHERE id = ?");
    $upd->execute([$new_value, $status_id, $kpi_id]);

    // --- تجديد التذكير (Todo) ---
    // نقوم بإغلاق التذكيرات القديمة لهذا الـ KPI وننشئ واحداً جديداً للموعد القادم
    $db->prepare("UPDATE user_todos SET is_completed = 1 WHERE related_entity_type = 'kpi' AND related_entity_id = ?")->execute([$kpi_id]);
    
    scheduleKPIReminder($kpi_id, $kpi['owner_id'], $kpi['name'], $kpi['frequency']);

    return ['ok' => true];
}

/**
 * دالة مساعدة لجدولة التذكير
 */
function scheduleKPIReminder($kpiId, $userId, $kpiName, $frequency) {
    require_once __DIR__ . '/../../modules/todos/todo_functions.php';
    
    $daysToAdd = 7; // Default Weekly
    if ($frequency == 'daily') $daysToAdd = 1;
    if ($frequency == 'monthly') $daysToAdd = 30;
    if ($frequency == 'quarterly') $daysToAdd = 90;
    
    $dueDate = date('Y-m-d', strtotime("+$daysToAdd days"));
    
    addSystemTodo(
        $userId,
        "Update KPI: $kpiName",
        "It's time to update the reading for this KPI ($frequency).",
        "kpi", // تأكد من التعامل مع هذا النوع في صفحة التودو إذا أردت رابطاً
        $kpiId,
        $dueDate
    );
}

function deleteKPI($kpi_id) {
    $db = Database::getInstance()->pdo();
    return $db->prepare("UPDATE kpis SET is_deleted = 1 WHERE id = ?")->execute([$kpi_id]);
}
?>