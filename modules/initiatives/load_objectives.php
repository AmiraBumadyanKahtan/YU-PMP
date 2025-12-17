<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";

if (!Auth::check()) {
    http_response_code(403);
    exit("Unauthorized");
}

if (!isset($_GET['pillar_id']) || empty($_GET['pillar_id'])) {
    echo json_encode([]);
    exit;
}

$pillar_id = intval($_GET['pillar_id']);

$stmt = db()->prepare("
    SELECT id, objective_text 
    FROM strategic_objectives
    WHERE pillar_id = ?
    ORDER BY id ASC
");
$stmt->execute([$pillar_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
