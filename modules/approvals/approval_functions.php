<?php
// modules/approvals/approval_functions.php

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../modules/todos/todo_functions.php'; 

// استدعاء مساعد الإشعارات
if (file_exists(__DIR__ . '/../../modules/operational_projects/notification_helper.php')) {
    require_once __DIR__ . '/../../modules/operational_projects/notification_helper.php';
}

/**
 * جلب الموافقات الخاصة بالمستخدم (للعرض في الداشبورد)
 */
function getUserApprovals($user_id, $user_role_id, $status_filter = 'in_progress') {
    $db = Database::getInstance()->pdo();

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
                -- 1. أدوار النظام العامة (باستثناء مدراء الأقسام، المالية، والاستراتيجية)
                (aws.assignee_type = 'system_role' AND aws.stage_role_id = :role_id AND aws.stage_role_id NOT IN (12, 14, 11))
                OR 
                -- 2. مدير المشروع
                (
                    aws.assignee_type = 'project_manager' 
                    AND et.entity_key = 'operational_project'
                    AND ai.entity_id IN (SELECT id FROM operational_projects WHERE manager_id = :user_id)
                )
                OR 
                -- 3. مدير القسم (للمشروع)
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
                -- 4. مدير المالية (ID = 5)
                (
                    (aws.assignee_type = 'system_role' AND aws.stage_role_id = 14) 
                    AND 
                    :user_id IN (SELECT manager_id FROM departments WHERE id = 5 AND is_deleted=0)
                )
                OR
                -- 5. مدير مكتب الاستراتيجية (ID = 11)
                (
                    (aws.assignee_type = 'system_role' AND aws.stage_role_id = 11) 
                    AND 
                    :user_id IN (SELECT manager_id FROM departments WHERE id = 11 AND is_deleted=0)
                )
            )
            ORDER BY ai.created_at ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':role_id' => $user_role_id, ':user_id' => $user_id]);

    } else {
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
 * إرسال تنبيهات للموافقين
 */
function notifyStageApprovers($stage_id, $entity_id, $entity_name) {
    $approvers = getStageApprovers($stage_id, $entity_id);
    
    if (!empty($approvers)) {
        $db = Database::getInstance()->pdo();
        $sName = $db->query("SELECT stage_name FROM approval_workflow_stages WHERE id=$stage_id")->fetchColumn();
        
        foreach ($approvers as $userId) {
            if (function_exists('sendProjectNotification')) {
                sendProjectNotification(
                    $userId,
                    "Approval Required: " . substr($entity_name, 0, 30),
                    "Your approval is required for stage: $sName.\nRequest: $entity_name",
                    "project_approvals", 
                    $entity_id
                );
            } else {
                addSystemTodo(
                    $userId, 
                    "Approval Pending: " . substr($entity_name, 0, 40), 
                    "Action required for stage: " . $sName, 
                    "project", 
                    $entity_id,
                    date('Y-m-d', strtotime('+2 days'))
                );
            }
        }
    }
}

/**
 * دالة تحديد المراجعين (تم التعديل لتشمل منطق المبادرات)
 */
function getStageApprovers($stageId, $entityId = null) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM approval_workflow_stages WHERE id = ?");
    $stmt->execute([$stageId]);
    $stage = $stmt->fetch();
    
    if (!$stage) return [];

    $approvers = [];

    // --- المنطق الخاص بالمبادرات (Initiative Approval Flow) ---
    // إذا كانت المرحلة تتبع Workflow المبادرات (سواء بوجود ميزانية أو لا)
    // ولكن هنا نعتمد على نوع "المُعيّن" (Assignee Type) والـ Role ID
    
    // 1. مدير القسم (للمشروع)
    if ($stage['assignee_type'] == 'department_manager' || ($stage['assignee_type'] == 'system_role' && $stage['stage_role_id'] == 12)) {
        if ($entityId) {
            // هنا نفترض أنه للمشاريع، للمبادرات ليس لدينا قسم مباشر مرتبط بنفس الطريقة (إلا عبر المالك)
            // سنبقيه كما هو للمشاريع
            $deptQ = $db->prepare("SELECT d.manager_id FROM operational_projects p JOIN departments d ON d.id = p.department_id WHERE p.id = ?");
            $deptQ->execute([$entityId]);
            $mgr = $deptQ->fetchColumn();
            if ($mgr) $approvers[] = $mgr;
        }
    }
    // 2. مدير المالية (ID=5)
    // هذا الدور يستخدم للمشاريع وللمبادرات (إذا كان هناك ميزانية)
    elseif ($stage['assignee_type'] == 'system_role' && $stage['stage_role_id'] == 14) {
        // التحقق من وجود ميزانية للمبادرة قبل الإرسال (تخطي شرطي)
        $skipFinance = false;
        if ($entityId) {
            // نفحص نوع الكيان المرتبط بهذه المرحلة
            $wfType = $db->query("SELECT entity_type_id FROM approval_workflows WHERE id = " . $stage['workflow_id'])->fetchColumn();
            
            // إذا كان مبادرة (ID=2)
            if ($wfType == 2) {
                $budget = $db->query("SELECT approved_budget FROM initiatives WHERE id = $entityId")->fetchColumn();
                if ($budget <= 0) {
                    $skipFinance = true; // لا يوجد ميزانية -> لا نحتاج مدير المالية
                }
            }
        }

        if (!$skipFinance) {
            $financeHead = $db->prepare("SELECT manager_id FROM departments WHERE id = 5 AND is_deleted=0"); 
            $financeHead->execute();
            $managerId = $financeHead->fetchColumn();
            if ($managerId) $approvers[] = $managerId;
        }
    }
    // 3. مدير مكتب الاستراتيجية (ID=11)
    // يستخدم للركائز والمبادرات
    elseif ($stage['assignee_type'] == 'system_role' && $stage['stage_role_id'] == 11) {
        $stratHead = $db->prepare("SELECT manager_id FROM departments WHERE id = 11 AND is_deleted=0"); 
        $stratHead->execute();
        $managerId = $stratHead->fetchColumn();
        
        if ($managerId) {
            $approvers[] = $managerId;
        } else {
             $roleUsers = $db->prepare("SELECT id FROM users WHERE primary_role_id = 11 AND is_active = 1");
             $roleUsers->execute();
             $approvers = array_merge($approvers, $roleUsers->fetchAll(PDO::FETCH_COLUMN));
        }
    }
    // 4. مدير المشروع
    elseif ($stage['assignee_type'] == 'project_manager') {
        if ($entityId) {
            $pmQ = $db->prepare("SELECT manager_id FROM operational_projects WHERE id = ?");
            $pmQ->execute([$entityId]);
            $pm = $pmQ->fetchColumn();
            if ($pm) $approvers[] = $pm;
        }
    }
    // 5. أدوار النظام العامة الأخرى (CEO, etc.)
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
function processApproval($instance_id, $user_id, $decision, $comments, $approved_budget = null) {
    $db = Database::getInstance()->pdo();
    
    try {
        $db->beginTransaction();

        $stmt = $db->prepare("SELECT * FROM approval_instances WHERE id = ?");
        $stmt->execute([$instance_id]);
        $instance = $stmt->fetch();

        if (!$instance || $instance['status'] != 'in_progress') {
            throw new Exception("Request not found or already processed.");
        }

        // تحديث الميزانية (للمشاريع والمبادرات)
        if ($decision === 'approved' && $approved_budget !== null) {
            if ($instance['entity_type_id'] == 3) { // Project
                $upd = $db->prepare("UPDATE operational_projects SET approved_budget = ? WHERE id = ?");
                $upd->execute([$approved_budget, $instance['entity_id']]);
                $comments .= " [Budget Approved: " . number_format($approved_budget) . " SAR]";
            } elseif ($instance['entity_type_id'] == 2) { // Initiative [NEW]
                $upd = $db->prepare("UPDATE initiatives SET approved_budget = ? WHERE id = ?");
                $upd->execute([$approved_budget, $instance['entity_id']]);
                $comments .= " [Budget Approved: " . number_format($approved_budget) . " SAR]";
            }
        }

        // تسجيل القرار
        $logAction = $db->prepare("
            INSERT INTO approval_actions (approval_instance_id, stage_id, reviewer_user_id, decision, comments, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $logAction->execute([$instance_id, $instance['current_stage_id'], $user_id, $decision, $comments]);

        // جلب اسم الكيان
        $entityName = "Request";
        $viewLink = "#";
        if ($instance['entity_type_id'] == 3) {
            $entityName = $db->query("SELECT name FROM operational_projects WHERE id = {$instance['entity_id']}")->fetchColumn();
            $viewLink = "project_view";
        } elseif ($instance['entity_type_id'] == 2) { // Initiative
            $entityName = $db->query("SELECT name FROM initiatives WHERE id = {$instance['entity_id']}")->fetchColumn();
            $viewLink = "initiative_view"; // رابط افتراضي
        } elseif ($instance['entity_type_id'] == 1) { // Pillar
            $entityName = $db->query("SELECT name FROM pillars WHERE id = {$instance['entity_id']}")->fetchColumn();
            $viewLink = "pillar_view";
        }

        // منطق الانتقال
        if ($decision === 'rejected') {
            $update = $db->prepare("UPDATE approval_instances SET status = 'rejected' WHERE id = ?");
            $update->execute([$instance_id]);
            updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'rejected');

            if (function_exists('sendProjectNotification')) {
                sendProjectNotification(
                    $instance['created_by'],
                    "Request Rejected: $entityName",
                    "Your request has been rejected.\nReason: $comments",
                    $viewLink,
                    $instance['entity_id']
                );
            }

        } elseif ($decision === 'returned') {
             $update = $db->prepare("UPDATE approval_instances SET status = 'returned' WHERE id = ?");
             $update->execute([$instance_id]);
             updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'returned');

             if (function_exists('sendProjectNotification')) {
                sendProjectNotification(
                    $instance['created_by'],
                    "Request Returned: $entityName",
                    "Your request has been returned for modifications.\nComments: $comments",
                    $viewLink,
                    $instance['entity_id']
                );
            }

        } else {
            // Approved
            $currStageQ = $db->prepare("SELECT * FROM approval_workflow_stages WHERE id = ?");
            $currStageQ->execute([$instance['current_stage_id']]);
            $currentStageData = $currStageQ->fetch();

            if ($currentStageData['is_final']) {
                $update = $db->prepare("UPDATE approval_instances SET status = 'approved', current_stage_id = NULL WHERE id = ?");
                $update->execute([$instance_id]);
                updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'approved');

                if (function_exists('sendProjectNotification')) {
                    sendProjectNotification(
                        $instance['created_by'],
                        "Request Approved: $entityName",
                        "Congratulations! Your request has been fully approved.",
                        $viewLink,
                        $instance['entity_id']
                    );
                }

            } else {
                // الانتقال للمرحلة التالية (مع منطق التخطي للمبادرات)
                $nextStageId = getNextStageIdWithSkipLogic($currentStageData, $instance['entity_type_id'], $instance['entity_id']);

                if ($nextStageId) {
                    $update = $db->prepare("UPDATE approval_instances SET current_stage_id = ? WHERE id = ?");
                    $update->execute([$nextStageId, $instance_id]);
                    
                    notifyStageApprovers($nextStageId, $instance['entity_id'], $entityName);
                    
                } else {
                    // إذا لم توجد مراحل تالية (أو تم تخطي الباقي)، ننهي كـ Approved
                    $update = $db->prepare("UPDATE approval_instances SET status = 'approved', current_stage_id = NULL WHERE id = ?");
                    $update->execute([$instance_id]);
                    updateEntityStatus($instance['entity_type_id'], $instance['entity_id'], 'approved');
                    
                    if (function_exists('sendProjectNotification')) {
                        sendProjectNotification(
                            $instance['created_by'],
                            "Request Approved: $entityName",
                            "Congratulations! Your request has been fully approved.",
                            $viewLink,
                            $instance['entity_id']
                        );
                    }
                }
            }
        }

        // إغلاق التنبيه
        $closeTodo = $db->prepare("
            UPDATE user_todos 
            SET is_completed = 1 
            WHERE user_id = ? 
              AND related_entity_id = ? 
              AND related_entity_type = 'project_approvals' 
              AND is_completed = 0
        ");
        $closeTodo->execute([$user_id, $instance['entity_id']]);

        $db->commit();
        return true;

    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

/**
 * دالة مساعدة جديدة لتحديد المرحلة التالية مع منطق التخطي (Skip Logic)
 */
function getNextStageIdWithSkipLogic($currentStageData, $entityType, $entityId) {
    $db = Database::getInstance()->pdo();
    
    // جلب كل المراحل التالية بالترتيب
    $nextStagesQ = $db->prepare("
        SELECT * FROM approval_workflow_stages 
        WHERE workflow_id = ? AND stage_order > ? 
        ORDER BY stage_order ASC
    ");
    $nextStagesQ->execute([$currentStageData['workflow_id'], $currentStageData['stage_order']]);
    $nextStages = $nextStagesQ->fetchAll();

    foreach ($nextStages as $stage) {
        // فحص شرط التخطي للمبادرات
        if ($entityType == 2 && $stage['assignee_type'] == 'system_role' && $stage['stage_role_id'] == 14) { 
            // مرحلة المالية للمبادرات
            $budget = $db->query("SELECT approved_budget FROM initiatives WHERE id = $entityId")->fetchColumn();
            if ($budget <= 0) {
                continue; // تخطي هذه المرحلة والذهاب للتالية
            }
        }
        
        // إذا لم يتم التخطي، هذه هي المرحلة التالية
        return $stage['id'];
    }

    return null; // لا توجد مراحل تالية
}

function updateEntityStatus($type_id, $entity_id, $status_code) {
    $db = Database::getInstance()->pdo();
    
    $statusId = 0;
    // PENDING: قد تحتاج لضبط الـ IDs حسب الجداول لديك بدقة
    if ($status_code == 'approved') $statusId = 5; // Approved
    elseif ($status_code == 'rejected') $statusId = 7; // Rejected for Initiatives (ID=7)
    elseif ($status_code == 'returned') $statusId = 6; // Returned for Initiatives (ID=6)

    if ($type_id == 3) { // Project
        // IDs للمشاريع قد تختلف (4=Rejected, 3=Returned, 5=Approved)
        if ($status_code == 'rejected') $statusId = 4;
        if ($status_code == 'returned') $statusId = 3;
        $db->prepare("UPDATE operational_projects SET status_id = ? WHERE id = ?")->execute([$statusId, $entity_id]);
    }
    elseif ($type_id == 2) { // Initiative
        $db->prepare("UPDATE initiatives SET status_id = ? WHERE id = ?")->execute([$statusId, $entity_id]);
    }
    elseif ($type_id == 1) { // Pillar
        // IDs للركائز (13=Rejected, 11=Approved)
        if ($status_code == 'approved') $statusId = 11;
        if ($status_code == 'rejected') $statusId = 13;
        // Returned للركائز غير محدد في جدولك بدقة، سنفترضه draft مؤقتاً أو ID جديد
        if ($status_code == 'returned') $statusId = 12; 
        $db->prepare("UPDATE pillars SET status_id = ? WHERE id = ?")->execute([$statusId, $entity_id]);
    }
}
?>