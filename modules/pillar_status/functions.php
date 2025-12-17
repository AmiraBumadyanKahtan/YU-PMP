<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/strategic-project-system/core/init.php';

function getAllPillarStatuses() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM pillar_statuses ORDER BY sort_order ASC, id ASC")->fetchAll();
}

function getPillarStatusById($id) {
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("SELECT * FROM pillar_statuses WHERE id = ?");
    $stmt->execute([$id]);

    return $stmt->fetch();
}

function createPillarStatus($name, $color, $sort_order) {
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        INSERT INTO pillar_statuses (name, color, sort_order)
        VALUES (?, ?, ?)
    ");

    return $stmt->execute([$name, $color, $sort_order]);
}

function updatePillarStatus($id, $name, $color, $sort_order) {
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("
        UPDATE pillar_statuses
        SET name = ?, color = ?, sort_order = ?
        WHERE id = ?
    ");

    return $stmt->execute([$name, $color, $sort_order, $id]);
}

function deletePillarStatus($id) {
    $db = Database::getInstance()->pdo();

    $stmt = $db->prepare("DELETE FROM pillar_statuses WHERE id = ?");
    return $stmt->execute([$id]);
}

function getPillarStatuses() {
    global $db;
    return $db->fetchAll("SELECT * FROM pillar_statuses ORDER BY sort_order ASC");
}
