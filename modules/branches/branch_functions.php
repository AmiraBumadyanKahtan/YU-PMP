<?php
// modules/branches/branch_functions.php

require_once __DIR__ . '/../../core/Database.php';

function getBranches() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM branches ORDER BY id ASC")->fetchAll();
}

function getBranchById($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT * FROM branches WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createBranch($data) {
    $db = Database::getInstance()->pdo();
    
    // Check duplicate code
    $check = $db->prepare("SELECT COUNT(*) FROM branches WHERE branch_code = ?");
    $check->execute([$data['code']]);
    if ($check->fetchColumn() > 0) return ['ok' => false, 'error' => 'Branch Code already exists'];

    $stmt = $db->prepare("INSERT INTO branches (branch_code, branch_name, city, is_active) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$data['code'], $data['name'], $data['city'], $data['is_active']])) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Insert failed'];
}

function updateBranch($id, $data) {
    $db = Database::getInstance()->pdo();
    
    // Check duplicate code (excluding self)
    $check = $db->prepare("SELECT COUNT(*) FROM branches WHERE branch_code = ? AND id != ?");
    $check->execute([$data['code'], $id]);
    if ($check->fetchColumn() > 0) return ['ok' => false, 'error' => 'Branch Code already exists'];

    $stmt = $db->prepare("UPDATE branches SET branch_code=?, branch_name=?, city=?, is_active=? WHERE id=?");
    if ($stmt->execute([$data['code'], $data['name'], $data['city'], $data['is_active'], $id])) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Update failed'];
}

function deleteBranch($id) {
    $db = Database::getInstance()->pdo();
    
    // Check usage in department_branches or user_branches
    $c1 = $db->prepare("SELECT COUNT(*) FROM department_branches WHERE branch_id = ?");
    $c1->execute([$id]);
    if ($c1->fetchColumn() > 0) return ['ok' => false, 'error' => 'Cannot delete: Linked to Departments'];

    $c2 = $db->prepare("SELECT COUNT(*) FROM user_branches WHERE branch_id = ?");
    $c2->execute([$id]);
    if ($c2->fetchColumn() > 0) return ['ok' => false, 'error' => 'Cannot delete: Linked to Users'];

    $stmt = $db->prepare("DELETE FROM branches WHERE id = ?");
    if ($stmt->execute([$id])) return ['ok' => true];
    
    return ['ok' => false, 'error' => 'Delete failed'];
}
?>