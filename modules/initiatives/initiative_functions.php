<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/init.php";

/* ============================================================
   BASIC LOOKUPS
============================================================ */
function getAllPillars()
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->query("
        SELECT id, pillar_number, name 
        FROM pillars
        ORDER BY pillar_number ASC
    ");

    return $stmt->fetchAll();
}

function getAllOwners()
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->query("
        SELECT id, full_name_en
        FROM users
        ORDER BY full_name_en ASC
    ");

    return $stmt->fetchAll();
}

function getAllInitiativeStatuses()
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->query("
        SELECT id, name, color
        FROM initiative_statuses
        ORDER BY sort_order ASC, id ASC
    ");

    return $stmt->fetchAll();
}

function getAllStrategicObjectives()
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->query("
        SELECT so.id,
               so.objective_code,
               so.objective_text,
               so.pillar_id,
               p.name AS pillar_name,
               p.pillar_number
        FROM strategic_objectives so
        JOIN pillars p ON p.id = so.pillar_id
        ORDER BY p.pillar_number ASC, so.id ASC
    ");

    return $stmt->fetchAll();
}


/* ============================================================
   STATUS HELPERS
============================================================ */
function getInitiativeStatusIdByName(string $name)
{
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        SELECT id 
        FROM initiative_statuses 
        WHERE name = ? 
        LIMIT 1
    ");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();

    return $id ?: null;
}


/* ============================================================
   CODE GENERATION
   Example: INIT-5.01  (pillar 5, first initiative)
============================================================ */
function generateInitiativeCode(int $pillar_id): string
{
    $db = Database::getInstance()->pdo();

    // Get pillar_number
    $stmt = $db->prepare("SELECT pillar_number FROM pillars WHERE id = ?");
    $stmt->execute([$pillar_id]);
    $pillarNumber = $stmt->fetchColumn();

    if (!$pillarNumber) {
        $pillarNumber = 0;
    }

    // How many initiatives already under this pillar?
    $stmt = $db->prepare("SELECT COUNT(*) FROM initiatives WHERE pillar_id = ?");
    $stmt->execute([$pillar_id]);
    $count = (int)$stmt->fetchColumn();
    $next = $count + 1;

    return sprintf("INIT-%d.%02d", $pillarNumber, $next);
}


/* ============================================================
   CREATE INITIATIVE (BASIC INFO ONLY)
============================================================ */
function createInitiative(array $data)
{
    $db = Database::getInstance()->pdo();

    // Generate code
    $code = generateInitiativeCode((int)$data['pillar_id']);

    // Status = Draft (you must ensure a row 'Draft' exists in initiative_statuses)
    $statusId = getInitiativeStatusIdByName('Draft');
    if (!$statusId) {
        // fallback: NULL, or you can throw an error/log it
        $statusId = null;
    }

    $stmt = $db->prepare("
        INSERT INTO initiatives (
            initiative_code,
            name,
            description,
            impact,
            notes,
            pillar_id,
            strategic_objective_id,
            owner_user_id,
            budget_min,
            budget_max,
            approved_budget,
            spent_budget,
            start_date,
            due_date,
            status_id,
            priority,
            progress_percentage,
            order_index,
            update_frequency,
            update_time
        ) VALUES (
            :code,
            :name,
            :description,
            :impact,
            :notes,
            :pillar_id,
            :strategic_objective_id,
            :owner_user_id,
            :budget_min,
            :budget_max,
            :approved_budget,
            0,
            :start_date,
            :due_date,
            :status_id,
            :priority,
            0,
            0,
            :update_frequency,
            :update_time
        )
    ");

    $stmt->execute([
        ':code'                  => $code,
        ':name'                  => $data['name'],
        ':description'           => $data['description'] ?? null,
        ':impact'                => $data['impact'] ?? null,
        ':notes'                 => $data['notes'] ?? null,
        ':pillar_id'             => $data['pillar_id'],
        ':strategic_objective_id'=> $data['strategic_objective_id'] ?: null,
        ':owner_user_id'         => $data['owner_user_id'] ?: null,
        ':budget_min'            => $data['budget_min'] ?: null,
        ':budget_max'            => $data['budget_max'] ?: null,
        ':approved_budget'       => $data['approved_budget'] ?: null,
        ':start_date'            => $data['start_date'] ?: null,
        ':due_date'              => $data['due_date'] ?: null,
        ':status_id'             => $statusId,
        ':priority'              => $data['priority'] ?: 'medium',
        ':update_frequency'      => $data['update_frequency'] ?: 'weekly',
        ':update_time'           => $data['update_time'] ?: '09:00:00',
    ]);

    return $db->lastInsertId();
}


/* ============================================================
   FILTERED LIST (USED BY list.php)
============================================================ */
function getFilteredInitiatives($filters = [])
{
    $db = Database::getInstance()->pdo();

    $sql = "
        SELECT 
            i.*,
            p.name AS pillar_name,
            u.full_name_en AS owner_name,
            s.name AS status_name
        FROM initiatives i
        LEFT JOIN pillars p ON p.id = i.pillar_id
        LEFT JOIN users u ON u.id = i.owner_user_id
        LEFT JOIN initiative_statuses s ON s.id = i.status_id
        WHERE 1
    ";

    $params = [];

    // Search
    if (!empty($filters['search'])) {
        $sql .= " AND (i.name LIKE :s OR i.initiative_code LIKE :s)";
        $params[':s'] = "%" . $filters['search'] . "%";
    }

    // Pillar
    if (!empty($filters['pillar_id'])) {
        $sql .= " AND i.pillar_id = :p";
        $params[':p'] = $filters['pillar_id'];
    }

    // Owner
    if (!empty($filters['owner_id'])) {
        $sql .= " AND i.owner_user_id = :o";
        $params[':o'] = $filters['owner_id'];
    }

    // Status
    if (!empty($filters['status_id'])) {
        $sql .= " AND i.status_id = :st";
        $params[':st'] = $filters['status_id'];
    }

    // Priority
    if (!empty($filters['priority'])) {
        $sql .= " AND i.priority = :prio";
        $params[':prio'] = $filters['priority'];
    }

    // Date range
    if (!empty($filters['start_from'])) {
        $sql .= " AND i.start_date >= :ds";
        $params[':ds'] = $filters['start_from'];
    }

    if (!empty($filters['end_to'])) {
        $sql .= " AND i.due_date <= :de";
        $params[':de'] = $filters['end_to'];
    }

    $sql .= " ORDER BY i.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
