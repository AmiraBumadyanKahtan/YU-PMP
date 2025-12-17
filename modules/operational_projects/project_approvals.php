<?php
// modules/operational_projects/project_approvals.php

function submitProjectForApproval($project_id, $user_id) {
    $db = Database::getInstance()->pdo();
    
    // 1. التحقق من حالة المشروع الحالية
    $chk = $db->prepare("SELECT status_id, approved_budget FROM operational_projects WHERE id = ?");
    $chk->execute([$project_id]);
    $projectData = $chk->fetch();
    
    if (!$projectData) return ['ok'=>false, 'error'=>'Project not found'];
    
    $currStatus = $projectData['status_id'];
    // التحقق من الميزانية المعتمدة
    $hasBudget = ($projectData['approved_budget'] > 0); 

    // السماح بالإرسال فقط إذا كان "مسودة" (1) أو "تم إرجاعه" (3)
    if (!in_array($currStatus, [1, 3])) return ['ok'=>false, 'error'=>'Project cannot be submitted in current status'];

    // 2. التحقق من وجود أهداف استراتيجية
    $objChk = $db->prepare("SELECT COUNT(*) FROM project_objectives WHERE project_id = ?");
    $objChk->execute([$project_id]);
    if ($objChk->fetchColumn() == 0) return ['ok'=>false, 'error'=>'Please add at least one strategic objective.'];

    // 3. تحديد نوع مسار الموافقة (Workflow Selection)
    // 3 = Operational Project (With Budget)
    // 6 = Operational Project (Without Budget) - افتراضاً أن ID المسار الجديد هو 6 في جدول approval_workflows
    // *ملاحظة: تأكد من ID المسار في قاعدة البيانات الخاصة بك. في الـ dump السابق كان ID=6 للمسار الجديد*
    
    $workflowId = $hasBudget ? 3 : 6; 

    // 4. جلب المرحلة الأولى من المسار المختار
    $firstStageQ = $db->prepare("
        SELECT s.id 
        FROM approval_workflow_stages s 
        JOIN approval_workflows w ON w.id = s.workflow_id 
        WHERE w.id = ? AND w.is_active = 1 
        ORDER BY s.stage_order ASC 
        LIMIT 1
    ");
    $firstStageQ->execute([$workflowId]);
    $firstStageId = $firstStageQ->fetchColumn();

    if (!$firstStageId) {
        // في حال لم نجد مساراً، نعود للمسار الافتراضي (للاحتياط)
        // هذا يمنع توقف النظام إذا كان المسار الجديد غير مفعل
         $firstStageQ->execute([3]); 
         $firstStageId = $firstStageQ->fetchColumn();
         
         if (!$firstStageId) return ['ok'=>false, 'error'=>'No active approval workflow found.'];
    }

    // 5. تحديث حالة المشروع إلى "قيد الانتظار" (2 = Pending Approval)
    $db->prepare("UPDATE operational_projects SET status_id = 2 WHERE id = ?")->execute([$project_id]);

    // 6. إنشاء سجل الموافقة (Approval Instance)
    // Entity Type ID للمشاريع هو 3 دائماً
    $entityTypeId = 3; 
    $db->prepare("INSERT INTO approval_instances (entity_type_id, entity_id, current_stage_id, status, created_by) VALUES (?, ?, ?, 'in_progress', ?)")
       ->execute([$entityTypeId, $project_id, $firstStageId, $user_id]);

    // 7. إرسال الإشعارات للموافقين
    $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
    
    // تأكد من المسار الصحيح لملف الدوال
    if (file_exists(__DIR__ . '/../../approvals/approval_functions.php')) {
        require_once __DIR__ . '/../../approvals/approval_functions.php';
        notifyStageApprovers($firstStageId, $project_id, $pName);
    }

    return ['ok'=>true];
}

// ... (دالة getProjectWorkflowTracker تبقى كما هي دون تغيير) ...
function getProjectWorkflowTracker($project_id) {
    $db = Database::getInstance()->pdo();
    // نجلب آخر عملية موافقة للمشروع
    $instance = $db->prepare("SELECT * FROM approval_instances WHERE entity_type_id = 3 AND entity_id = ? ORDER BY id DESC LIMIT 1");
    $instance->execute([$project_id]);
    $inst = $instance->fetch();
    
    if (!$inst) return [];

    // هنا النقطة المهمة: نجلب المراحل بناءً على المرحلة الحالية للمسار الذي تم اختياره لهذا الـ instance
    // نستنتج المسار (Workflow ID) من المرحلة الحالية المسجلة في instance
    // أو إذا كانت المرحلة الحالية NULL (مكتمل)، نجلب المسار من آخر إجراء (Action)
    
    // 1. محاولة معرفة Workflow ID
    $workflowId = 0;
    
    if ($inst['current_stage_id']) {
        $wfQ = $db->prepare("SELECT workflow_id FROM approval_workflow_stages WHERE id = ?");
        $wfQ->execute([$inst['current_stage_id']]);
        $workflowId = $wfQ->fetchColumn();
    } else {
        // إذا كان مكتمل، نبحث في الـ actions
        $wfQ = $db->prepare("
            SELECT s.workflow_id 
            FROM approval_actions aa 
            JOIN approval_workflow_stages s ON s.id = aa.stage_id 
            WHERE aa.approval_instance_id = ? 
            LIMIT 1
        ");
        $wfQ->execute([$inst['id']]);
        $workflowId = $wfQ->fetchColumn();
    }
    
    if (!$workflowId) return []; // Fallback empty

    // 2. جلب مراحل هذا المسار المحدد
    $stagesQuery = $db->prepare("
        SELECT s.id AS stage_id, s.stage_name, s.assignee_type, r.role_name, 
               aa.decision, aa.created_at AS action_date, aa.comments, u.full_name_en AS reviewer_name,
               CASE 
                 WHEN s.assignee_type = 'project_manager' THEN 'Project Manager' 
                 WHEN s.assignee_type = 'department_manager' THEN 'Department Head' 
                 ELSE r.role_name 
               END AS stage_label
        FROM approval_workflow_stages s
        LEFT JOIN roles r ON r.id = s.stage_role_id
        LEFT JOIN approval_actions aa ON aa.stage_id = s.id AND aa.approval_instance_id = ?
        LEFT JOIN users u ON u.id = aa.reviewer_user_id
        WHERE s.workflow_id = ? 
        ORDER BY s.stage_order ASC
    ");
    $stagesQuery->execute([$inst['id'], $workflowId]);
    $stages = $stagesQuery->fetchAll();

    $tracker = [];
    $foundCurrent = false;
    foreach ($stages as $s) {
        $status = 'queue';
        if ($s['decision'] == 'approved') $status = 'approved';
        elseif ($s['decision'] == 'rejected') $status = 'rejected';
        elseif ($s['decision'] == 'returned') $status = 'returned';
        else { 
            if (!$foundCurrent && $inst['status'] == 'in_progress' && $inst['current_stage_id'] == $s['stage_id']) { 
                $status = 'pending'; 
                $foundCurrent = true; 
            } elseif ($inst['status'] == 'approved') { 
                $status = 'approved'; 
            } 
        }
        $s['status_visual'] = $status;
        $tracker[] = $s;
    }
    return $tracker;
}
?>