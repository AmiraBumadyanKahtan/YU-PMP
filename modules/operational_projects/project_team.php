<?php
// modules/operational_projects/project_team.php

function getProjectTeam($project_id) {
    $db = Database::getInstance()->pdo();
    $sql = "SELECT pt.*, u.full_name_en, u.email, u.avatar, r.name AS role_name 
            FROM project_team pt
            JOIN users u ON u.id = pt.user_id
            JOIN project_roles r ON r.id = pt.role_id
            WHERE pt.project_id = ? AND pt.is_active = 1
            ORDER BY r.id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$project_id]);
    return $stmt->fetchAll();
}

function getAvailableUsersForProject($project_id, $department_id) {
    $db = Database::getInstance()->pdo();
    $sql = "SELECT id, full_name_en FROM users WHERE department_id = $department_id AND is_active = 1";
    $sql .= " UNION SELECT u.id, u.full_name_en FROM collaborations c JOIN users u ON u.id = c.assigned_user_id WHERE c.parent_type = 'project' AND c.parent_id = $project_id AND c.status_id = 2 AND c.assigned_user_id IS NOT NULL";
    $finalSql = "SELECT * FROM ($sql) AS potential WHERE id NOT IN (SELECT user_id FROM project_team WHERE project_id = $project_id) ORDER BY full_name_en";
    return $db->query($finalSql)->fetchAll();
}

function addTeamMember($project_id, $user_id, $role_id) {
    $db = Database::getInstance()->pdo();
    $check = $db->prepare("SELECT id FROM project_team WHERE project_id = ? AND user_id = ?");
    $check->execute([$project_id, $user_id]);
    if ($check->fetch()) return ['ok' => false, 'error' => 'User already in team'];

    $stmt = $db->prepare("INSERT INTO project_team (project_id, user_id, role_id, is_active, created_at) VALUES (?, ?, ?, 1, NOW())");
    if ($stmt->execute([$project_id, $user_id, $role_id])) {
        require_once __DIR__ . '/../../modules/todos/todo_functions.php';
        $pName = $db->query("SELECT name FROM operational_projects WHERE id = $project_id")->fetchColumn();
        addSystemTodo($user_id, "Welcome to Project: $pName", "You have been added to the team.", "project", $project_id);
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Database error'];
}

function removeTeamMember($project_id, $user_id) {
    $db = Database::getInstance()->pdo();
    return $db->prepare("DELETE FROM project_team WHERE project_id = ? AND user_id = ?")->execute([$project_id, $user_id]);
}
?>