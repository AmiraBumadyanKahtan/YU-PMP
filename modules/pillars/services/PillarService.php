<?php
// modules/pillars/services/PillarService.php

require_once __DIR__ . '/../../../core/Database.php';

class PillarService {
    
    public static function create($data) {
        $db = Database::getInstance()->pdo();
        $draftStatusId = 12; 
        $stmt = $db->prepare("
            INSERT INTO pillars (pillar_number, name, description, lead_user_id, start_date, end_date, status_id, progress_percentage, color, icon, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, NOW())
        ");
        if ($stmt->execute([
            $data['pillar_number'], $data['name'], $data['description'], 
            $data['lead_user_id'], $data['start_date'], $data['end_date'], 
            $draftStatusId, $data['color'], $data['icon']
        ])) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function update($id, $data) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            UPDATE pillars SET 
                pillar_number=?, name=?, description=?, lead_user_id=?, 
                start_date=?, end_date=?, color=?, icon=?, updated_at=NOW()
            WHERE id=?
        ");
        return $stmt->execute([
            $data['pillar_number'], $data['name'], $data['description'], 
            $data['lead_user_id'], $data['start_date'], $data['end_date'], 
            $data['color'], $data['icon'], $id
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance()->pdo();
        return $db->prepare("UPDATE pillars SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$id]);
    }

    public static function getById($id) {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("
            SELECT p.*, u.full_name_en as lead_name, s.name as status_name, s.color as status_color 
            FROM pillars p
            LEFT JOIN users u ON u.id = p.lead_user_id
            LEFT JOIN pillar_statuses s ON s.id = p.status_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getAll($search = '', $status_id = '') {
        $db = Database::getInstance()->pdo();
        $sql = "SELECT p.*, u.full_name_en as lead_name, u.avatar as lead_avatar, s.name as status_name, s.color as status_color 
                FROM pillars p
                LEFT JOIN users u ON u.id = p.lead_user_id
                LEFT JOIN pillar_statuses s ON s.id = p.status_id
                WHERE (p.is_deleted = 0 OR p.is_deleted IS NULL)";
        
        $params = [];
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($status_id) {
            $sql .= " AND p.status_id = ?";
            $params[] = $status_id;
        }
        $sql .= " ORDER BY p.pillar_number ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getStatuses() {
        $db = Database::getInstance()->pdo();
        return $db->query("SELECT * FROM pillar_statuses")->fetchAll();
    }

    // Logic Engine
    public static function updateStatusAutomatic($pillar_id) {
        $db = Database::getInstance()->pdo();
        $pillar = $db->query("SELECT * FROM pillars WHERE id=$pillar_id")->fetch();
        $initiatives = $db->query("SELECT status_id, start_date FROM initiatives WHERE pillar_id=$pillar_id AND is_deleted=0")->fetchAll();
        
        $currentStatus = $pillar['status_id'];
        $today = date('Y-m-d');
        
        if ($currentStatus == 11 && count($initiatives) > 0) { // Approved -> Pending
            $db->prepare("UPDATE pillars SET status_id = 2 WHERE id=?")->execute([$pillar_id]);
        }
        
        // Pending/Approved -> In Progress
        if ($currentStatus == 2 || $currentStatus == 11) {
            $shouldStart = false;
            if ($pillar['start_date'] <= $today) $shouldStart = true;
            foreach ($initiatives as $init) {
                if ($init['status_id'] == 9 || ($init['start_date'] <= $today && $init['start_date'] != null)) {
                    $shouldStart = true;
                    break;
                }
            }
            if ($shouldStart) {
                $db->prepare("UPDATE pillars SET status_id = 3 WHERE id=?")->execute([$pillar_id]);
            }
        }
    }

    public static function submitForApproval($pillar_id, $user_id) {
        $db = Database::getInstance()->pdo();
        $db->prepare("UPDATE pillars SET status_id = 9 WHERE id = ?")->execute([$pillar_id]);
        $entityTypeId = 1; 
        $firstStageQ = $db->prepare("SELECT s.id FROM approval_workflow_stages s JOIN approval_workflows w ON w.id = s.workflow_id WHERE w.entity_type_id = ? AND w.is_active = 1 ORDER BY s.stage_order ASC LIMIT 1");
        $firstStageQ->execute([$entityTypeId]);
        $firstStageId = $firstStageQ->fetchColumn();

        if ($firstStageId) {
            $db->prepare("INSERT INTO approval_instances (entity_type_id, entity_id, current_stage_id, status, created_by) VALUES (?, ?, ?, 'in_progress', ?)")
            ->execute([$entityTypeId, $pillar_id, $firstStageId, $user_id]);
            
            require_once __DIR__ . '/../../approvals/approval_functions.php';
            $pName = $db->query("SELECT name FROM pillars WHERE id = $pillar_id")->fetchColumn();
            notifyStageApprovers($firstStageId, $pillar_id, $pName, 'pillar');
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'No workflow defined'];
    }

    public static function getWorkflowTracker($pillar_id) {
        $db = Database::getInstance()->pdo();
        $instance = $db->prepare("SELECT * FROM approval_instances WHERE entity_type_id = 1 AND entity_id = ? ORDER BY id DESC LIMIT 1");
        $instance->execute([$pillar_id]);
        $inst = $instance->fetch();
        if (!$inst) return [];

        $stagesQuery = $db->prepare("
            SELECT s.id AS stage_id, s.stage_name, r.role_name, 
                aa.decision, aa.created_at AS action_date, aa.comments, u.full_name_en AS reviewer_name
            FROM approval_workflow_stages s
            JOIN approval_workflows w ON w.id = s.workflow_id
            LEFT JOIN roles r ON r.id = s.stage_role_id
            LEFT JOIN approval_actions aa ON aa.stage_id = s.id AND aa.approval_instance_id = ?
            LEFT JOIN users u ON u.id = aa.reviewer_user_id
            WHERE w.entity_type_id = 1 AND w.is_active = 1
            ORDER BY s.stage_order ASC
        ");
        $stagesQuery->execute([$inst['id']]);
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
}
?>