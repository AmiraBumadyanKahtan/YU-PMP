<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once "functions.php";

if (!is_super_admin()) die("Access denied");

// Required fields
$required = ['name','target_value','frequency','status_id','owner_id','parent_type'];

foreach ($required as $r) {
    if (empty($_POST[$r])) {
        die("Missing required field: " . $r);
    }
}

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

// Parent validation
if ($parent_type === "initiative" && empty($initiative_id)) {
    die("Please select an initiative.");
}

if ($parent_type === "project" && empty($project_id)) {
    die("Please select a project.");
}

// Normalize parent
$parent_id = ($parent_type === "initiative") ? $initiative_id : $project_id;

// Insert logic
$db = Database::getInstance()->pdo();

$stmt = $db->prepare("
    INSERT INTO kpis (
        name, description, kpi_type, unit, target_value, baseline_value,
        frequency, data_source, status_id, owner_id,
        parent_type, parent_id, created_by, created_at
    ) VALUES (
        :name, :desc, :kpi_type, :unit, :target, :baseline,
        :freq, :source, :status, :owner,
        :ptype, :pid, :created_by, NOW()
    )
");

$stmt->execute([
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
    ':created_by' => $_SESSION['user_id']
]);

header("Location: list.php?added=1");
exit;
