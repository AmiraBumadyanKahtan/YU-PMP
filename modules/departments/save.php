<?php
// modules/departments/save.php

require_once "../../core/config.php"; 
require_once "../../core/auth.php";
require_once "department_functions.php";

// ✅ التعديل: استخدام الصلاحية المفصلة الجديدة
if (!Auth::can('sys_dept_create')) {
    // نستخدم die هنا لأن المستخدم لا يجب أن يصل هنا إلا إذا تلاعب بالرابط
    die("Access denied: You do not have permission to create departments.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$name = trim($_POST['name'] ?? '');
$manager_id = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;
$branches = $_POST['branches'] ?? [];

// 1. التحقق من صحة البيانات
$errors = [];

if (empty($name)) {
    $errors[] = "Department name is required.";
}

if (empty($branches) || !is_array($branches)) {
    $errors[] = "You must select at least one branch.";
}

// 2. التحقق من تكرار الاسم
if (dept_name_exists($name)) {
    $errors[] = "A department with the name '{$name}' already exists!";
}

if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
    header("Location: create.php");
    exit;
}

// 3. الحفظ في قاعدة البيانات
$db = Database::getInstance()->pdo();

try {
    $db->beginTransaction();

    // إنشاء القسم
    $newId = dept_create($name, $manager_id);

    if ($newId) {
        // ربط الفروع
        updateDepartmentBranches($newId, $branches);
        
        $db->commit();
        
        $_SESSION['success'] = "Department '{$name}' created successfully!";
        header("Location: list.php");
        exit;
    } else {
        throw new Exception("Database error: Could not create record.");
    }

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "System Error: " . $e->getMessage();
    header("Location: create.php");
    exit;
}
?>