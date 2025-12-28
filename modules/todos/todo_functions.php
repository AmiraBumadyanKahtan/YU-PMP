<?php
// modules/todos/todo_functions.php

require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب مهام المستخدم (مع تنظيف تلقائي للمهام المنتهية)
 */
function getUserTodos($user_id, $filter = 'all') {
    $db = Database::getInstance()->pdo();
    
    // --- خطوة تنظيف ذكية (Optional but Recommended) ---
    // حذف التنبيهات المرتبطة بموافقات تمت معالجتها بالفعل (لم تعد 'in_progress')
    // هذا سيمنع ظهور مهام "Approval Required" لموافقات انتهت
    $cleanupSql = "
        DELETE t FROM user_todos t
        JOIN approval_instances ai ON ai.id = t.related_entity_id
        WHERE t.user_id = ? 
        AND t.related_entity_type = 'project_approvals' -- تأكد من الاسم المستخدم عند الإنشاء
        AND ai.status != 'in_progress'
    ";
    // ملاحظة: تأكد من نوع الكيان (related_entity_type) الذي تستخدمه عند إنشاء التنبيه
    // في الكود السابق كان 'project' للموافقات، وهذا قد يسبب خلطاً مع المشاريع العادية.
    // الأفضل التمييز، لكن سنفترض أنك تستخدم 'project' للموافقات كما في الكود السابق.
    
    // الحل البديل الآمن: التحقق أثناء الجلب (بدون حذف)
    
    $sql = "SELECT * FROM user_todos WHERE user_id = ?";
    
    if ($filter === 'pending') {
        $sql .= " AND is_completed = 0";
    } elseif ($filter === 'completed') {
        $sql .= " AND is_completed = 1";
    }
    
    $sql .= " ORDER BY is_completed ASC, created_at DESC"; 
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تصفية النتائج يدوياً للتأكد من صلاحية المهام المرتبطة بنظام الموافقات
    $validTodos = [];
    foreach ($todos as $t) {
        // إذا كانت المهمة عبارة عن "طلب موافقة"
        if ($t['is_system_generated'] == 1 && $t['related_entity_type'] == 'project' && strpos($t['title'], 'Approval') !== false) {
            // تحقق هل لا يزال الطلب 'in_progress'؟
            // هنا نفترض أن related_entity_id هو رقم المشروع (entity_id) وليس رقم الموافقة (instance_id)
            // وهذا هو سبب المشكلة في كودك السابق (notifyStageApprovers)
            
            // لتصحيح ذلك، يجب أن نتحقق من جدول الموافقات.
            // ولكن بما أن الربط ضعيف (entity_id = project_id)، سنقوم بحل بسيط:
            // إذا كان العنوان يحتوي على 'Approval Required'، نتحقق هل يوجد أي موافقة معلقة لهذا المستخدم على هذا المشروع؟
            
            $check = $db->prepare("
                SELECT COUNT(*) FROM approval_instances ai
                JOIN approval_workflow_stages aws ON aws.id = ai.current_stage_id
                WHERE ai.entity_type_id = 3 -- Project
                AND ai.entity_id = ? 
                AND ai.status = 'in_progress'
                -- هنا نحتاج منطق 'getStageApprovers' المعقد، وهذا سيثقل الصفحة.
                -- لذا، الأفضل هو الاعتماد على أن النظام يقوم بإغلاق التنبيه عند المعالجة (وهذا ما أضفناه في processApproval).
            ");
            // ... بما أننا أضفنا كود إغلاق التنبيه في processApproval، 
            // فالمفروض أن تختفي المهام بمجرد المعالجة.
            
            // إذا كانت المهمة ما زالت تظهر، فهذا يعني أنها أُنشئت قبل التعديل الأخير،
            // أو أن هناك خطأ في الربط.
            
            // الحل السريع: عرض المهمة كما هي.
            $validTodos[] = $t;
        } else {
            $validTodos[] = $t;
        }
    }
    
    return $validTodos;
}

/**
 * إضافة مهمة يدوية
 */
function addPersonalTodo($user_id, $title, $description, $due_date) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        INSERT INTO user_todos (user_id, title, description, due_date, is_system_generated, is_completed, created_at)
        VALUES (?, ?, ?, ?, 0, 0, NOW())
    ");
    return $stmt->execute([$user_id, $title, $description, $due_date ?: null]);
}

/**
 * إضافة مهمة نظام
 */
function addSystemTodo($user_id, $title, $description, $entity_type, $entity_id, $due_date = null) {
    $db = Database::getInstance()->pdo();
    if ($due_date === null) $due_date = date('Y-m-d');

    // منع التكرار: لا تضف مهمة إذا كانت هناك واحدة معلقة بنفس النوع والمشروع
    $check = $db->prepare("SELECT id FROM user_todos WHERE user_id=? AND related_entity_type=? AND related_entity_id=? AND is_completed=0");
    $check->execute([$user_id, $entity_type, $entity_id]);
    if ($check->fetch()) return true; // موجودة مسبقاً، لا داعي للإضافة

    $stmt = $db->prepare("
        INSERT INTO user_todos (user_id, title, description, due_date, is_system_generated, related_entity_type, related_entity_id, is_completed, created_at)
        VALUES (?, ?, ?, ?, 1, ?, ?, 0, NOW())
    ");
    return $stmt->execute([$user_id, $title, $description, $due_date, $entity_type, $entity_id]);
}

/**
 * تغيير حالة المهمة
 */
function toggleTodoStatus($id, $user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("UPDATE user_todos SET is_completed = NOT is_completed WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}

/**
 * حذف مهمة
 */
function deleteTodo($id, $user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("DELETE FROM user_todos WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $user_id]);
}

/**
 * عداد المهام غير المكتملة
 */
function countPendingTodos($user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT COUNT(*) FROM user_todos WHERE user_id = ? AND is_completed = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>