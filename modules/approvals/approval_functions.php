<?php
// modules/approvals/approval_functions.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../modules/todos/todo_functions.php'; 

/**
 * جلب الموافقات الخاصة بالمستخدم
 * التعديل: تم إضافة حقول الوصف، القسم، بند الميزانية، والميزانية المعتمدة
 */
function getUserApprovals($user_id, $user_role_id, $status_filter = 'in_progress') {
    $db = Database::getInstance()->pdo();

    // جملة الـ SELECT المشتركة (لتجنب التكرار)
    // نستخدم Subqueries لجلب البيانات حسب نوع الكيان
    $selectFields = "
        ai.id AS instance_id,
        ai.created_at AS request_date,
        et.entity_name AS type,
        et.entity_key,
        ai.entity_id,
        ai.entity_type_id,
        
        CASE 
            WHEN et.entity_key = 'operational_project' THEN (SELECT name FROM operational_projects WHERE id = ai.entity_id)
            WHEN et.entity_key = 'initiative' THEN (SELECT name FROM initiatives WHERE id = ai.entity_id)
            WHEN et.entity_key = 'pillar' THEN (SELECT name FROM pillars WHERE id = ai.entity_id)
            ELSE 'Unknown Entity'
        END AS entity_title,

        CASE 
            WHEN et.entity_key = 'operational_project' THEN (SELECT description FROM operational_projects WHERE id = ai.entity_id)
            WHEN et.entity_key = 'initiative' THEN (SELECT description FROM initiatives WHERE id = ai.entity_id)
            ELSE '' 
        END AS entity_description,

        CASE 
            WHEN et.entity_key = 'operational_project' THEN 
                (SELECT d.name FROM operational_projects p JOIN departments d ON d.id = p.department_id WHERE p.id = ai.entity_id)
            ELSE 'N/A' 
        END AS department_name,

        CASE 
            WHEN et.entity_key = 'operational_project' THEN 
                (SELECT CONCAT(budget_min, '|', budget_max) FROM operational_projects WHERE id = ai.entity_id)
            ELSE NULL 
        END AS budget_range,

        CASE 
            WHEN et.entity_key = 'operational_project' THEN (SELECT approved_budget FROM operational_projects WHERE id = ai.entity_id)
            ELSE NULL 
        END AS current_approved_budget,

        CASE 
            WHEN et.entity_key = 'operational_project' THEN (SELECT budget_item FROM operational_projects WHERE id = ai.entity_id)
            ELSE NULL 
        END AS budget_item
    ";

    if ($status_filter === 'in_progress') {
        
        $sql = "
            SELECT 
                $selectFields,
                aws.stage_name,
                aws.id AS stage_id,
                u.full_name_en AS requester_name

            FROM approval_instances ai
            JOIN approval_entity_types et ON et.id = ai.entity_type_id
            JOIN approval_workflow_stages aws ON aws.id = ai.current_stage_id
            JOIN users u ON u.id = ai.created_by
            WHERE ai.status = 'in_progress'
            AND (
                -- 1. موافقة مسندة لدور النظام (System Role)
                (aws.assignee_type = 'system_role' AND aws.stage_role_id = :role_id AND aws.stage_role_id NOT IN (12, 14))
                
                OR 
                
                -- 2. موافقة مسندة لمدير المشروع
                (
                    aws.assignee_type = 'project_manager' 
                    AND et.entity_key = 'operational_project'
                    AND ai.entity_id IN (SELECT id FROM operational_projects WHERE manager_id = :user_id)
                )
                
                OR
                
                -- 3. موافقة مسندة لمدير القسم
                (
                    (aws.assignee_type = 'department_manager' OR (aws.assignee_type = 'system_role' AND aws.stage_role_id = 12))
                    AND et.entity_key = 'operational_project'
                    AND ai.entity_id IN (
                        SELECT p.id FROM operational_projects p 
                        JOIN departments d ON d.id = p.department_id 
                        WHERE d.manager_id = :user_id
                    )
                )

                OR
                
                -- 4. موافقة مسندة لمدير المالية (ID = 5)
                (
                    (aws.assignee_type = 'system_role' AND aws.stage_role_id = 14) 
                    AND 
                    :user_id IN (SELECT manager_id FROM departments WHERE id = 5 AND is_deleted=0)
                )
            )
            ORDER BY ai.created_at ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':role_id' => $user_role_id, ':user_id' => $user_id]);

    } else {
        // --- 2. جلب السجل التاريخي (History) ---
        $sql = "
            SELECT 
                $selectFields,
                aa.approval_instance_id AS instance_id,
                aa.created_at AS action_date,
                aa.decision,
                aa.comments

            FROM approval_actions aa
            JOIN approval_instances ai ON ai.id = aa.approval_instance_id
            JOIN approval_entity_types et ON et.id = ai.entity_type_id
            WHERE aa.reviewer_user_id = :user_id
            AND aa.decision = :status
            ORDER BY aa.created_at DESC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $user_id, ':status' => $status_filter]);
    }

    return $stmt->fetchAll();
}

/**
 * إرسال تنبيهات (Todos + Notifications)
 */
function notifyStageApprovers($stage_id, $entity_id, $entity_name) {
    $approvers = getStageApprovers($stage_id, $entity_id);
    
    if (!empty($approvers)) {
        $db = Database::getInstance()->pdo();
        $sName = $db->query("SELECT stage_name FROM approval_workflow_stages WHERE id=$stage_id")->fetchColumn();
        
        foreach ($approvers as $userId) {
            addSystemTodo(
                $userId, 
                "Approval Pending: " . substr($entity_name, 0, 40), 
                "Action required for stage: " . $sName, 
                "project", 
                $entity_id,
                date('Y-m-d', strtotime('+2 days'))
            );
            
            $notif = $db->prepare("INSERT INTO notifications (user_id, title, message, type, related_entity_type, related_entity_id) VALUES (?, ?, ?, 'approval', 'project', ?)");
            $notif->execute([$userId, "Approval Request", "Project '$entity_name' requires your approval.", $entity_id]);
        }
    }
}

/**
 * دالة تحديد المراجعين
 */
function getStageApprovers($stageId, $entityId = null) {
    $db = Database::getInstance()->pdo();
    
    $stmt = $db->prepare("SELECT * FROM approval_workflow_stages WHERE id = ?");
    $stmt->execute([$stageId]);
    $stage = $stmt->fetch();
    
    if (!$stage) return [];

    $approvers = [];

    // --- حالة 1: مدير القسم ---
    if ($stage['assignee_type'] == 'department_manager' || ($stage['assignee_type'] == 'system_role' && $stage['stage_role_id'] == 12)) {
        if ($entityId) {
            $deptQ = $db->prepare("
                SELECT d.manager_id 
                FROM operational_projects p 
                JOIN departments d ON d.id = p.department_id 
                WHERE p.id = ?
            ");
            $deptQ->execute([$entityId]);
            $mgr = $deptQ->fetchColumn();
            if ($mgr) $approvers[] = $mgr;
        }
    }
    // --- حالة 2: مدير المالية (ID = 5) ---
    elseif ($stage['assignee_type'] == 'system_role' && $stage['stage_role_id'] == 14) {
        $financeDeptId = 5; 
        $financeHead = $db->prepare("SELECT manager_id FROM departments WHERE id = ? AND is_deleted=0");
        $financeHead->execute([$financeDeptId]);
        $managerId = $financeHead->fetchColumn();
        if ($managerId) $approvers[] = $managerId;
    }
    // --- حالة 3: مدير المشروع ---
    elseif ($stage['assignee_type'] == 'project_manager') {
        if ($entityId) {
            $pmQ = $db->prepare("SELECT manager_id FROM operational_projects WHERE id = ?");
            $pmQ->execute([$entityId]);
            $pm = $pmQ->fetchColumn();
            if ($pm) $approvers[] = $pm;
        }
    }
    // --- حالة 4: أدوار النظام العامة ---
    elseif ($stage['assignee_type'] == 'system_role') {
        $roleUsers = $db->prepare("SELECT id FROM users WHERE primary_role_id = ? AND is_active = 1");
        $roleUsers->execute([$stage['stage_role_id']]);
        $approvers = array_merge($approvers, $roleUsers->fetchAll(PDO::FETCH_COLUMN));
        
        $extraRoleUsers = $db->prepare("SELECT user_id FROM user_roles WHERE role_id = ?");
        $extraRoleUsers->execute([$stage['stage_role_id']]);
        $approvers = array_merge($approvers, $extraRoleUsers->fetchAll(PDO::FETCH_COLUMN));
    }

    return array_unique($approvers);
}


/**
 * تنفيذ قرار الموافقة أو الرفض
 */
function processApproval($instance_id, $user_id, $decision, $comments) {
    $db = Database::getInstance()->pdo();
    
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM approval_instances WHERE id = ?");
        $stmt->execute([$instance_id]);
        $instance = $stmt->fetch();

        if (!$instance || $instance['status'] != 'in_progress') {
            throw new Exception("Request not found or already processed.");
        }

        // تسجيل القرار
        $logAction = $db->prepare("
            INSERT INTO approval_actions (approval_instance_id, stage_id, reviewer_user_id, decision, comments, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $logAction->execute([$instance_id, $instance['current_stage_id'], $user_id, $decision, $comments]);

        // منطق الانتقال
        if ($decision === 'rejected') {
            $update = $db->prepare("UPDATE approval_instances SET status = 'rejected' WHERE id = ?");
            $update->execute([$instance_id]);
            updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'rejected');

        } elseif ($decision === 'returned') {
             $update = $db->prepare("UPDATE approval_instances SET status = 'returned' WHERE id = ?");
             $update->execute([$instance_id]);
             updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'returned');

        } else {
            // Approved
            $currStageQ = $db->prepare("SELECT * FROM approval_workflow_stages WHERE id = ?");
            $currStageQ->execute([$instance['current_stage_id']]);
            $currentStageData = $currStageQ->fetch();

            if ($currentStageData['is_final']) {
                $update = $db->prepare("UPDATE approval_instances SET status = 'approved', current_stage_id = NULL WHERE id = ?");
                $update->execute([$instance_id]);
                updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'approved');
            } else {
                $nextStageQ = $db->prepare("
                    SELECT id FROM approval_workflow_stages 
                    WHERE workflow_id = ? AND stage_order > ? 
                    ORDER BY stage_order ASC LIMIT 1
                ");
                $nextStageQ->execute([$currentStageData['workflow_id'], $currentStageData['stage_order']]);
                $nextStageId = $nextStageQ->fetchColumn();

                if ($nextStageId) {
                    $update = $db->prepare("UPDATE approval_instances SET current_stage_id = ? WHERE id = ?");
                    $update->execute([$nextStageId, $instance_id]);

                    // إشعار المرحلة التالية
                    $entityName = "Unknown";
                    if ($instance['entity_type_id'] == 3) { // Project
                        $entityName = $db->query("SELECT name FROM operational_projects WHERE id = {$instance['entity_id']}")->fetchColumn();
                    } elseif ($instance['entity_type_id'] == 2) { // Initiative
                        $entityName = $db->query("SELECT name FROM initiatives WHERE id = {$instance['entity_id']}")->fetchColumn();
                    } elseif ($instance['entity_type_id'] == 1) { // Pillar
                        $entityName = $db->query("SELECT name FROM pillars WHERE id = {$instance['entity_id']}")->fetchColumn();
                    }

                    notifyStageApprovers($nextStageId, $instance['entity_id'], $entityName);
                    
                } else {
                    // Fallback Finish
                    $update = $db->prepare("UPDATE approval_instances SET status = 'approved', current_stage_id = NULL WHERE id = ?");
                    $update->execute([$instance_id]);
                    updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'approved');
                }
            }
        }

        // إغلاق التنبيه
        $entityTypeKey = ($instance['entity_type_id'] == 3) ? 'project' : 'initiative';
        $closeTodo = $db->prepare("
            UPDATE user_todos 
            SET is_completed = 1 
            WHERE user_id = ? 
              AND related_entity_type = ? 
              AND related_entity_id = ? 
              AND is_completed = 0
        ");
        $closeTodo->execute([$user_id, $entityTypeKey, $instance['entity_id']]);

        $db->commit();
        return true;

    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

function updateEntityStatus($type_id, $entity_id, $status_code) {
    $db = Database::getInstance()->pdo();
    
    $statusId = 0;
    if ($status_code == 'approved') $statusId = 5; 
    elseif ($status_code == 'rejected') $statusId = 4;
    elseif ($status_code == 'returned') $statusId = 3;

    if ($type_id == 3) { 
        $db->prepare("UPDATE operational_projects SET status_id = ? WHERE id = ?")->execute([$statusId, $entity_id]);
    }
    elseif ($type_id == 2) { 
        $db->prepare("UPDATE initiatives SET status_id = ? WHERE id = ?")->execute([$statusId, $entity_id]);
    }
}
?>