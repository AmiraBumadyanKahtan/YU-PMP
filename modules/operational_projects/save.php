<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

if (!Auth::can('create_project')) die("Access Denied");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newId = createProject($_POST);

    if ($newId) {
        // يمكننا إضافة تذكير تلقائي للمدير هنا (كما تحدثنا سابقاً)
        header("Location: view.php?id=" . $newId);
        exit;
    } else {
        die("Error creating project.");
    }
}
?>