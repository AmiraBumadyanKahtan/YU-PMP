<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/init.php";

/*
|--------------------------------------------------------------------------
| ROLE HELPERS
|--------------------------------------------------------------------------
*/

function is_super_admin() {
    return ($_SESSION['role_key'] ?? '') === 'super_admin';
}

function is_ceo() {
    return ($_SESSION['role_key'] ?? '') === 'ceo';
}

function is_strategy_office() {
    return ($_SESSION['role_key'] ?? '') === 'strategy_office';
}

function is_department_manager() {
    return ($_SESSION['role_key'] ?? '') === 'department_manager';
}

function kpi_can_modify() {
    return is_super_admin();
}

function kpi_can_update_value($owner_id) {
    return ($_SESSION['user_id'] ?? 0) == $owner_id || is_super_admin();
}


/*
|--------------------------------------------------------------------------
| GET SINGLE KPI
|--------------------------------------------------------------------------
*/
function get_kpi($id) {
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        SELECT k.*, 
               s.status_name AS status_label,
               u.full_name_en AS owner_name
        FROM kpis k
        LEFT JOIN kpi_statuses s ON s.id = k.status_id
        LEFT JOIN users u ON u.id = k.owner_id
        WHERE k.id = ?
    ");

    $stmt->execute([$id]);
    return $stmt->fetch();
}


/*
|--------------------------------------------------------------------------
| GET FILTERED KPIs WITH ROLE LOGIC
|--------------------------------------------------------------------------
*/
function get_kpis_filtered($filters = []) {
    $db = Database::getInstance()->pdo();
    $params = [];
    $where = [];

    // Search filters
    if (!empty($filters['name'])) {
        $where[] = "k.name LIKE :name";
        $params[':name'] = "%" . $filters['name'] . "%";
    }

    if (!empty($filters['parent_type'])) {
        $where[] = "k.parent_type = :ptype";
        $params[':ptype'] = $filters['parent_type'];
    }

    if (!empty($filters['parent_id'])) {
        $where[] = "k.parent_id = :pid";
        $params[':pid'] = $filters['parent_id'];
    }

    if (!empty($filters['status_id'])) {
        $where[] = "k.status_id = :sid";
        $params[':sid'] = $filters['status_id'];
    }

    if (!empty($filters['owner_id'])) {
        $where[] = "k.owner_id = :oid";
        $params[':oid'] = $filters['owner_id'];
    }

    if (!empty($filters['last_updated_from'])) {
        $where[] = "k.last_updated >= :from";
        $params[':from'] = $filters['last_updated_from'];
    }

    if (!empty($filters['last_updated_to'])) {
        $where[] = "k.last_updated <= :to";
        $params[':to'] = $filters['last_updated_to'];
    }

    // ROLE FILTERS
    $role = $_SESSION["role_key"];
    $department_id = $_SESSION["user_department_id"] ?? null;

    $join = "";

    if (is_super_admin() || is_ceo()) {
        // No restrictions
    }
    elseif (is_strategy_office()) {
        $where[] = "k.parent_type = 'initiative'";
    }
    elseif (is_department_manager()) {

        $join = " JOIN operational_projects op 
                   ON op.id = k.parent_id 
                  AND k.parent_type = 'project'";

        $where[] = "op.department_id = :dept";
        $params[':dept'] = $department_id;
    }
    else {
        // No access
        return [];
    }

    // Build final SQL
    $sql = "
        SELECT 
            k.*,
            s.status_name AS status_label,
            u.full_name_en AS owner_name
        FROM kpis k
        LEFT JOIN kpi_statuses s ON s.id = k.status_id
        LEFT JOIN users u ON u.id = k.owner_id
        $join
    ";

    if (count($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY k.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| CREATE KPI
|--------------------------------------------------------------------------
*/
function create_kpi($data) {
    if (!kpi_can_modify()) return false;

    $db = Database::getInstance()->pdo();

    $sql = "
        INSERT INTO kpis
        (name, description, target_value, baseline_value, current_value,
        unit, kpi_type, frequency, data_source, status_id, owner_id, parent_type, parent_id)
        VALUES
        (:name, :desc, :target, :base, :current, :unit, :type, :freq, :source, :status, :owner, :pt, :pid)
    ";

    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
}


/*
|--------------------------------------------------------------------------
| UPDATE KPI
|--------------------------------------------------------------------------
*/
function update_kpi($id, $data) {
    if (!kpi_can_modify()) return false;

    $db = Database::getInstance()->pdo();

    $columns = [];
    foreach ($data as $key => $value) {
        $columns[] = "$key = :$key";
    }

    $sql = "UPDATE kpis SET " . implode(", ", $columns) . " WHERE id = :id";

    $data['id'] = $id;

    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
}


/*
|--------------------------------------------------------------------------
| DELETE KPI
|--------------------------------------------------------------------------
*/
function delete_kpi($id) {
    if (!kpi_can_modify()) return false;

    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("DELETE FROM kpis WHERE id = ?");
    return $stmt->execute([$id]);
}


/*
|--------------------------------------------------------------------------
| UPDATE KPI VALUE ONLY
|--------------------------------------------------------------------------
*/
function update_kpi_value($id, $value) {
    $db = Database::getInstance()->pdo();

    $kpi = get_kpi($id);

    if (!$kpi) return false;

    if (!kpi_can_update_value($kpi['owner_id'])) {
        return false;
    }

    $stmt = $db->prepare("
        UPDATE kpis 
        SET current_value = :v,
            last_updated = NOW()
        WHERE id = :id
    ");

    return $stmt->execute([
        ':v' => $value,
        ':id' => $id
    ]);
}


/*
|--------------------------------------------------------------------------
| STATIC LISTS
|--------------------------------------------------------------------------
*/
function get_kpi_statuses() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM kpi_statuses ORDER BY id ASC")->fetchAll();
}

function get_kpi_owners() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT id, full_name_en FROM users ORDER BY full_name_en")->fetchAll();
}

function get_initiatives_list() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT id, name FROM initiatives ORDER BY name")->fetchAll();
}

function get_projects_list() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT id, name FROM operational_projects ORDER BY name")->fetchAll();
}
