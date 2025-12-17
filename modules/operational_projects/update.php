<?php
require_once "../../core/init.php";
require_once "project_functions.php";
require_once "../../core/common_functions.php";

if (!Auth::can('edit_project')) {
    die("Access denied");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$project = getOperationalProject($id);
if (!$project) die("Project not found");

if (!isProjectEditable($project)) {
    die("This project cannot be edited in its current status.");
}

$db = db_p();

$project_code   = trim($_POST['project_code'] ?? '');
$name           = trim($_POST['name'] ?? '');
$department_id  = (int)($_POST['department_id'] ?? 0);
$manager_id     = (int)($_POST['manager_id'] ?? 0);
$initiative_id  = !empty($_POST['initiative_id']) ? (int)$_POST['initiative_id'] : null;
$priority       = $_POST['priority'] ?? 'medium';
$budget_min     = $_POST['budget_min'] !== '' ? (float)$_POST['budget_min'] : null;
$budget_max     = $_POST['budget_max'] !== '' ? (float)$_POST['budget_max'] : null;
//$approved_budget= $_POST['approved_budget'] !== '' ? (float)$_POST['approved_budget'] : $project['approved_budget'];
$start_date     = $_POST['start_date'] ?? null;
$end_date       = $_POST['end_date'] ?? null;
$update_frequency = $_POST['update_frequency'] ?? 'weekly';
$description    = $_POST['description'] ?? '';

$collab_department_id   = !empty($_POST['collab_department_id']) ? (int)$_POST['collab_department_id'] : null;
$collab_contact_user_id = !empty($_POST['collab_contact_user_id']) ? (int)$_POST['collab_contact_user_id'] : null;

$errors = [];

if ($project_code === '') $errors[] = "Project code is required.";
if ($name === '')         $errors[] = "Project name is required.";
if (!$department_id)      $errors[] = "Department is required.";
if (!$manager_id)         $errors[] = "Project manager is required.";

if ($errors) {
    die(implode("<br>", $errors));
}

$oldValue = json_encode($project);

$sql = "
    UPDATE operational_projects
    SET project_code = :project_code,
        name         = :name,
        description  = :description,
        department_id= :department_id,
        manager_id   = :manager_id,
        initiative_id= :initiative_id,
        budget_min   = :budget_min,
        budget_max   = :budget_max,
        start_date   = :start_date,
        end_date     = :end_date,
        priority     = :priority,
        update_frequency = :update_frequency,
        updated_at   = NOW()
    WHERE id = :id
";
$st = $db->prepare($sql);
$st->execute([
    ':project_code'     => $project_code,
    ':name'             => $name,
    ':description'      => $description,
    ':department_id'    => $department_id,
    ':manager_id'       => $manager_id,
    ':initiative_id'    => $initiative_id,
    ':budget_min'       => $budget_min,
    ':budget_max'       => $budget_max,
    //':approved_budget'  => $approved_budget,
    ':start_date'       => $start_date ?: null,
    ':end_date'         => $end_date ?: null,
    ':priority'         => $priority,
    ':update_frequency' => $update_frequency,
    ':id'               => $id,
]);

// تعاون
upsertProjectCollaboration($id, $collab_department_id, $collab_contact_user_id, $_SESSION['user_id']);

$newValue = getOperationalProject($id);

log_activity(
    $_SESSION['user_id'],
    'update',
    'project',
    $id,
    $oldValue,
    json_encode($newValue)
);

header("Location: view.php?id=" . $id);
exit;
