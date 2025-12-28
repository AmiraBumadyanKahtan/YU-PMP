<?php
// modules/departments/update.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "department_functions.php";

// ✅ التعديل: استخدام صلاحية التعديل المحددة
if (!Auth::can('sys_dept_edit')) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$manager_id = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;
$branches = $_POST['branches'] ?? [];

// 1. التحقق الأساسي
if ($id <= 0) {
    $_SESSION['error'] = "Invalid Department ID.";
    header("Location: list.php");
    exit;
}

// 2. التحقق من البيانات
$errors = [];

if (empty($name)) {
    $errors[] = "Department name is required.";
}

if (empty($branches) || !is_array($branches)) {
    $errors[] = "You must assign at least one branch.";
}

// التحقق من تكرار الاسم (باستثناء هذا القسم نفسه)
if (dept_name_exists($name, $id)) {
    $errors[] = "A department with the name '{$name}' already exists!";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: edit.php?id=" . $id);
    exit;
}

// 3. الحفظ (Transaction)
$db = Database::getInstance()->pdo();

try {
    $db->beginTransaction();

    // تحديث البيانات الأساسية
    dept_update($id, $name, $manager_id);

    // تحديث الفروع
    updateDepartmentBranches($id, $branches);

    $db->commit();
    
    $_SESSION['success'] = "Department updated successfully!";
    header("Location: list.php");
    exit;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "System Error: " . $e->getMessage();
    header("Location: edit.php?id=" . $id);
    exit;
}
?>