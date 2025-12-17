<?php
// modules/operational_projects/project_core.php

require_once __DIR__ . '/../../core/Database.php';

function getProjects($filters = []) {
    $db = Database::getInstance()->pdo();
    $user_id = $_SESSION['user_id'];
    $role_key = $_SESSION['role_key'];
    $user_dept = $_SESSION['department_id'];

    $sql = "SELECT p.*, d.name AS department_name, u.full_name_en AS manager_name, s.name AS status_name, s.color AS status_color
            FROM operational_projects p
            LEFT JOIN departments d ON d.id = p.department_id
            LEFT JOIN users u ON u.id = p.manager_id
            LEFT JOIN operational_project_statuses s ON s.id = p.status_id
            WHERE (p.is_deleted = 0 OR p.is_deleted IS NULL)";

    if (!in_array($role_key, ['super_admin', 'ceo', 'strategy_office'])) {
        if ($role_key == 'department_manager') {
            $sql .= " AND p.department_id = $user_dept";
        } else {
            $sql .= " AND (p.manager_id = $user_id OR p.visibility = 'public' OR p.id IN (SELECT project_id FROM project_team WHERE user_id = $user_id AND is_active=1))";
        }
    }

    $params = [];
    if (!empty($filters['search'])) { $sql .= " AND (p.name LIKE :s OR p.project_code LIKE :s)"; $params[':s'] = "%".$filters['search']."%"; }
    if (!empty($filters['department_id'])) { $sql .= " AND p.department_id = :dept"; $params[':dept'] = $filters['department_id']; }
    if (!empty($filters['status_id'])) { $sql .= " AND p.status_id = :st"; $params[':st'] = $filters['status_id']; }
    
    $sql .= " ORDER BY p.id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProjectById($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT p.*, d.name AS department_name, u.full_name_en AS manager_name, s.name AS status_name 
                          FROM operational_projects p
                          LEFT JOIN departments d ON d.id = p.department_id
                          LEFT JOIN users u ON u.id = p.manager_id
                          LEFT JOIN operational_project_statuses s ON s.id = p.status_id
                          WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function generateProjectCode() {
    $db = Database::getInstance()->pdo();
    $year = date('Y');
    $prefix = "OP-$year-";
    $stmt = $db->prepare("SELECT project_code FROM operational_projects WHERE project_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["$prefix%"]);
    $lastCode = $stmt->fetchColumn();
    $number = $lastCode ? intval(substr($lastCode, strrpos($lastCode, '-') + 1)) + 1 : 1;
    return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
}

function createProject($data) {
    $db = Database::getInstance()->pdo();

    // 1. التحقق من الميزانية (Server Side Validation)
    $maxBudget = floatval($data['budget_max'] ?? 0);
    $approvedBudget = floatval($data['approved_budget'] ?? 0);

    if ($approvedBudget > 0 && $maxBudget > 0 && $approvedBudget > $maxBudget) {
        // يمكن إرجاع مصفوفة خطأ، أو إرجاع false (حسب طريقة معالجة الأخطاء لديك)
        // هنا سأقوم بتصحيحها تلقائياً للحد الأقصى أو إرجاع خطأ (سأختار إرجاع false للأمان)
        return false; 
    }

    $stmt = $db->prepare("
        INSERT INTO operational_projects 
        (project_code, name, description, department_id, manager_id, 
         budget_min, budget_max, approved_budget, budget_item, 
         start_date, end_date, priority, visibility,
         status_id, update_frequency, created_at, updated_at, is_deleted)
        VALUES 
        (:code, :name, :desc, :dept, :mgr, 
         :b_min, :b_max, :approved, :b_item, 
         :start, :end, :prio, :vis,
         1, :freq, NOW(), NOW(), 0)
    ");

    $res = $stmt->execute([
        ':code'   => $data['project_code'],
        ':name'   => $data['name'],
        ':desc'   => $data['description'],
        ':dept'   => !empty($data['department_id']) ? $data['department_id'] : null,
        ':mgr'    => !empty($data['manager_id']) ? $data['manager_id'] : null,
        ':b_min'  => $data['budget_min'] ?: 0,
        ':b_max'  => $data['budget_max'] ?: 0,
        ':approved' => $approvedBudget ?: null, // الحقل الجديد
        ':b_item'   => $data['budget_item'] ?? null, // الحقل الجديد
        ':start'  => $data['start_date'] ?: null,
        ':end'    => $data['end_date'] ?: null,
        ':prio'   => $data['priority'],
        ':vis'    => $data['visibility'] ?? 'private',
        ':freq'   => $data['update_frequency']
    ]);

    if ($res) {
        $newId = $db->lastInsertId();
        try {
            $log = $db->prepare("INSERT INTO activity_log (user_id, action, entity_type, entity_id, new_value, created_at) VALUES (?, 'create', 'project', ?, ?, NOW())");
            $log->execute([$_SESSION['user_id'], $newId, "Created Project: " . $data['name']]);
        } catch(Exception $e) {}
        return $newId;
    }
    return false;
}

function updateProject($id, $data) {
    $db = Database::getInstance()->pdo();

    // التحقق من الميزانية عند التحديث أيضاً
    $maxBudget = floatval($data['budget_max'] ?? 0);
    $approvedBudget = floatval($data['approved_budget'] ?? 0);
    if ($approvedBudget > 0 && $maxBudget > 0 && $approvedBudget > $maxBudget) {
        return false;
    }

    $stmt = $db->prepare("
        UPDATE operational_projects 
        SET name = :name, description = :desc, manager_id = :mgr, department_id = :dept,
            budget_min = :b_min, budget_max = :b_max, approved_budget = :approved, budget_item = :b_item,
            start_date = :start, end_date = :end, 
            priority = :prio, visibility = :vis, update_frequency = :freq, updated_at = NOW()
        WHERE id = :id
    ");

    return $stmt->execute([
        ':name'  => $data['name'], ':desc'  => $data['description'],
        ':mgr'   => !empty($data['manager_id']) ? $data['manager_id'] : null,
        ':dept'  => !empty($data['department_id']) ? $data['department_id'] : null,
        ':b_min' => $data['budget_min'] ?: 0, 
        ':b_max' => $data['budget_max'] ?: 0,
        ':approved' => $approvedBudget ?: null,
        ':b_item' => $data['budget_item'] ?? null,
        ':start' => $data['start_date'] ?: null, ':end'   => $data['end_date'] ?: null,
        ':prio'  => $data['priority'], ':vis'   => $data['visibility'] ?? 'private',
        ':freq'  => $data['update_frequency'], ':id'    => $id
    ]);
}

function addProjectObjective($project_id, $text) {
    $db = Database::getInstance()->pdo();
    return $db->prepare("INSERT INTO project_objectives (project_id, objective_text) VALUES (?, ?)")->execute([$project_id, $text]);
}

function getProjectObjectives($project_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM project_objectives WHERE project_id = ?");
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

/**
 * التحقق من صحة التواريخ
 */
function validateDates($parentStart, $parentEnd, $childStart, $childEnd) {
    if (empty($childStart) || empty($childEnd)) return true;
    
    $pStart = strtotime($parentStart);
    $pEnd = strtotime($parentEnd);
    $cStart = strtotime($childStart);
    $cEnd = strtotime($childEnd);

    if ($cStart > $cEnd) return "Start date cannot be after End date.";
    if ($cStart < $pStart) return "Start date cannot be before Project/Milestone start date ($parentStart).";
    if ($cEnd > $pEnd) return "End date cannot be after Project/Milestone end date ($parentEnd).";
    
    return true; 
}
?>