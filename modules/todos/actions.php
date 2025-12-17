<?php
// modules/todos/actions.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "todo_functions.php";

if (!Auth::check()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? 0;
$userId = $_SESSION['user_id'];

if ($action === 'toggle') {
    if (toggleTodoStatus($id, $userId)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
} 
elseif ($action === 'delete') {
    if (deleteTodo($id, $userId)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
elseif ($action === 'add') {
    $title = trim($_POST['title']);
    $desc  = trim($_POST['description']);
    $date  = $_POST['due_date'] ?: null;
    
    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Title required']);
        exit;
    }

    if (addPersonalTodo($userId, $title, $desc, $date)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>