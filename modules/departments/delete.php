<?php
// modules/departments/delete.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "department_functions.php";

// ✅ التعديل: استخدام الصلاحية المفصلة الجديدة للحذف
if (!Auth::can('sys_dept_delete')) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

$result = dept_delete($id);

if ($result === true) {
    echo json_encode(['status' => 'success']);
} elseif ($result === 'has_users') {
    echo json_encode(['status' => 'blocked', 'message' => 'Cannot delete: This department has active employees assigned to it.']);
} elseif ($result === 'has_projects') {
    echo json_encode(['status' => 'blocked', 'message' => 'Cannot delete: This department manages active operational projects.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}
exit;
?>