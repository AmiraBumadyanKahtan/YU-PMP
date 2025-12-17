<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once "functions.php";

if (!is_super_admin()) {
    die("Access denied");
}

// Validate ID
if (empty($_POST['id'])) {
    die("Invalid request");
}

$id = intval($_POST['id']);

$db = Database::getInstance()->pdo();

// ===== 1) Fetch OLD KPI Before Update =====
$stmtOld = $db->prepare("SELECT * FROM kpis WHERE id = ?");
$stmtOld->execute([$id]);
$oldData = $stmtOld->fetch(PDO::FETCH_ASSOC);

if (!$oldData) {
    die("KPI not found");
}


// ===== 2) Validate required fields =====
$required = ['name','target_value','frequency','status_id','owner_id','parent_type'];

foreach ($required as $r) {
    if (empty($_POST[$r])) {
        die("Missing required field: " . $r);
    }
}

// ===== 3) Collect input fields =====
$name            = trim($_POST['name']);
$description     = trim($_POST['description'] ?? '');
$kpi_type        = $_POST['kpi_type'];
$unit            = $_POST['unit'] ?? '';
$target_value    = $_POST['target_value'];
$baseline_value  = $_POST['baseline_value'] ?? null;
$frequency       = $_POST['frequency'];
$data_source     = $_POST['data_source'] ?? '';
$status_id       = $_POST['status_id'];
$owner_id        = $_POST['owner_id'];

$parent_type     = $_POST['parent_type'];
$initiative_id   = $_POST['initiative_id'] ?? null;
$project_id      = $_POST['project_id'] ?? null;

// Validate parent
if ($parent_type === "initiative" && empty($initiative_id)) {
    die("Please select an initiative.");
}
if ($parent_type === "project" && empty($project_id)) {
    die("Please select a project.");
}

$parent_id = ($parent_type === "initiative") ? $initiative_id : $project_id;


// ===== 4) Execute UPDATE =====
$stmt = $db->prepare("
    UPDATE kpis SET
        name = :name,
        description = :desc,
        kpi_type = :kpi_type,
        unit = :unit,
        target_value = :target,
        baseline_value = :baseline,
        frequency = :freq,
        data_source = :source,
        status_id = :status,
        owner_id = :owner,
        parent_type = :ptype,
        parent_id = :pid,
        updated_by = :updated_by,
        updated_at = NOW()
    WHERE id = :id
");

$stmt->execute([
    ':id'         => $id,
    ':name'       => $name,
    ':desc'       => $description,
    ':kpi_type'   => $kpi_type,
    ':unit'       => $unit,
    ':target'     => $target_value,
    ':baseline'   => $baseline_value,
    ':freq'       => $frequency,
    ':source'     => $data_source,
    ':status'     => $status_id,
    ':owner'      => $owner_id,
    ':ptype'      => $parent_type,
    ':pid'        => $parent_id,
    ':updated_by' => $_SESSION['user_id']
]);


// ===== 5) Fetch NEW DATA =====
$stmtNew = $db->prepare("SELECT * FROM kpis WHERE id = ?");
$stmtNew->execute([$id]);
$newData = $stmtNew->fetch(PDO::FETCH_ASSOC);


// ===== 6) Compare Old vs New =====
$changesOld = [];
$changesNew = [];

foreach ($newData as $key => $newValue) {
    $oldValue = $oldData[$key];

    // Ignore untouched system fields
    if (in_array($key, ['created_at', 'updated_at', 'last_updated'])) continue;

    if ($oldValue != $newValue) {
        $changesOld[$key] = $oldValue;
        $changesNew[$key] = $newValue;
    }
}


// ===== 7) Insert LOG if changes exist =====
if (!empty($changesOld)) {
    $stmtLog = $db->prepare("
        INSERT INTO activity_log 
        (user_id, action, entity_type, entity_id, old_value, new_value, ip_address)
        VALUES (?, 'updated', 'kpi', ?, ?, ?, ?)
    ");

    $stmtLog->execute([
        $_SESSION['user_id'],
        $id,
        json_encode($changesOld, JSON_UNESCAPED_UNICODE),
        json_encode($changesNew, JSON_UNESCAPED_UNICODE),
        $_SERVER['REMOTE_ADDR']
    ]);
}

header("Location: list.php?updated=1");
exit;
?>
