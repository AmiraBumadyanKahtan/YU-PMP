<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::role(['super_admin', 'strategy_office'])) {
    die("Access denied.");
}

$db = Database::getInstance();
$conn = $db->pdo();



try {

    $conn->beginTransaction();

    /* --------------------------------------------------------
        1. BASIC INFO
    -------------------------------------------------------- */
    $title       = $_POST['title'] ?? '';
    $code        = $_POST['code'] ?? NULL;
    $description = $_POST['description'] ?? '';
    $pillar_id   = $_POST['pillar_id'] ?? NULL;
    $status      = $_POST['status'] ?? 'not_started';

    $budget_min  = $_POST['budget_min'] ?? NULL;
    $budget_max  = $_POST['budget_max'] ?? NULL;

    $start_date  = $_POST['start_date'] ?? NULL;
    $end_date    = $_POST['end_date'] ?? NULL;

    $owner_id    = $_POST['owner_id'] ?? NULL;
    $impact      = $_POST['impact'] ?? NULL;
    $notes       = $_POST['notes'] ?? NULL;

    if (!$title || !$pillar_id || !$owner_id) {
        die("Missing required fields.");
    }

    /* --------------------------------------------------------
        2. INSERT INITIATIVE
    -------------------------------------------------------- */
    $stmt = $conn->prepare("
        INSERT INTO initiatives 
        (pillar_id, name, description_en, owner_user_id, status, start_date, due_date, budget_min, 	budget_max, initiative_code) 
        VALUES 
        (:pillar_id, :title, :description, :owner_id, :status, :start_date, :end_date, :budget_min, :budget_max, :code)
    ");

    $stmt->execute([
        ':pillar_id'   => $pillar_id,
        ':title'       => $title,
        ':description' => $description,
        ':owner_id'    => $owner_id,
        ':status'      => $status,
        ':start_date'  => $start_date,
        ':end_date'    => $end_date,
        ':budget_min'  => $budget_min,
        ':budget_max'  => $budget_max,
        ':code'        => $code
    ]);

    $initiative_id = $conn->lastInsertId();


    /* --------------------------------------------------------
        3. INSERT OBJECTIVES
    -------------------------------------------------------- */
    if (!empty($_POST['objective_ids'])) {
        $stmt = $conn->prepare("
            INSERT INTO initiative_objectives (initiative_id, strategic_objective_id)
            VALUES (:initiative_id, :obj_id)
        ");

        foreach ($_POST['objective_ids'] as $obj_id) {
            $stmt->execute([
                ':initiative_id' => $initiative_id,
                ':obj_id'        => $obj_id
            ]);
        }
    }

/* 4. INSERT RESOURCES */
if (!empty($_POST['resources'])) {
    $stmt = $conn->prepare("
        INSERT INTO work_resources 
        (parent_type, parent_id, type_id, qty, name)
        VALUES ('initiative', :initiative_id, :type, :qty, :name)
    ");

    foreach ($_POST['resources'] as $r) {
        if (empty($r['type']) || empty($r['qty']) || empty($r['name'])) continue;

        $stmt->execute([
            ':initiative_id' => $initiative_id,
            ':type'          => $r['type'],
            ':qty'           => $r['qty'],
            ':name'          => $r['name']
        ]);
    }
}

/* 5. INSERT TEAM */
if (!empty($_POST['team_members'])) {
    $stmt = $conn->prepare("
        INSERT INTO team_assignments 
        (user_id, initiative_id, role_in_team, is_active)
        VALUES (:user_id, :initiative_id, :role, 1)
    ");

    foreach ($_POST['team_members'] as $m) {
        if (empty($m['user_id']) || empty($m['role'])) continue;

        $stmt->execute([
            ':user_id'       => $m['user_id'],
            ':initiative_id' => $initiative_id,
            ':role'          => $m['role']
        ]);
    }
}


    /* --------------------------------------------------------
        6. LINK EXISTING KPIs
    -------------------------------------------------------- */
    if (!empty($_POST['kpi_ids'])) {
        $stmt = $conn->prepare("
            UPDATE kpis 
            SET parent_type = 'initiative', parent_id = :initiative_id 
            WHERE id = :kpi_id
        ");

        foreach ($_POST['kpi_ids'] as $kid) {
            $stmt->execute([
                ':initiative_id' => $initiative_id,
                ':kpi_id'        => $kid
            ]);
        }
    }

    /* --------------------------------------------------------
        7. CREATE NEW KPIs
    -------------------------------------------------------- */
    if (!empty($_POST['new_kpis'])) {

        $stmt = $conn->prepare("
            INSERT INTO kpis 
            (name, target_value,unit, kpi_type, frequency, baseline_value, current_value, data_source, owner_id, description, parent_type, parent_id) 
            VALUES 
            (:name, :target, :unit, :type, :frequency, :baseline, :current, :source, :owner, :description, 'initiative', :initiative_id)
        ");

        foreach ($_POST['new_kpis'] as $k) {
            $stmt->execute([
                ':name'          => $k['name'],
                ':target'        => $k['target'],
                ':unit'          => $k['unit'],
                ':type'          => $k['type'],
                ':frequency'     => $k['frequency'],
                ':baseline'       => $k['baseline'],
                ':current'       => $k['current'],
                ':source'       => $k['source'],
                ':owner'         => $k['owner'],
                ':description'   => $k['description'],
                ':initiative_id' => $initiative_id
            ]);
        }
    }

    /* --------------------------------------------------------
        8. RISKS
    -------------------------------------------------------- */
    if (!empty($_POST['risks'])) {

        $stmt = $conn->prepare("
            INSERT INTO risk_assessments 
            (risk_title_en, parent_type, parent_id) 
            VALUES (:title, 'initiative', :initiative_id)
        ");

        foreach ($_POST['risks'] as $risk_title) {
            $stmt->execute([
                ':title'         => $risk_title,
                ':initiative_id' => $initiative_id
            ]);
        }
    }

    /* --------------------------------------------------------
        9. COLLABORATION
    -------------------------------------------------------- */
    if (!empty($_POST['collaboration'])) {

        $stmt = $conn->prepare("
            INSERT INTO project_collaborations 
            (initiative_id, department_id, reason, status) 
            VALUES (:initiative_id, :dept, :reason, 'pending')
        ");

        foreach ($_POST['collaboration'] as $c) {
            $stmt->execute([
                ':initiative_id' => $initiative_id,
                ':dept'          => $c['dept'],
                ':reason'        => $c['reason']
            ]);
        }
    }

    /* --------------------------------------------------------
        10. SAVE NOTES
    -------------------------------------------------------- */
    if ($notes) {
        $stmt = $conn->prepare("
            INSERT INTO comments (initiative_id, user_id, comment_text)
            VALUES (:initiative_id, :user_id, :text)
        ");

        $stmt->execute([
            ':initiative_id' => $initiative_id,
            ':user_id'       => Auth::id(),
            ':text'          => $notes
        ]);
    }

    /* --------------------------------------------------------
        COMMIT
    -------------------------------------------------------- */
    $conn->commit();

    header("Location: list.php?success=1");
    exit;

} catch (Exception $e) {

    $conn->rollBack();
    die("Error: " . $e->getMessage());
}
