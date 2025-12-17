<?php
// modules/departments/update.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "department_functions.php"; // استدعاء الدوال الجاهزة

if (!Auth::can('manage_departments')) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

// تنظيف المدخلات
$id = intval($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$manager_id = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;

if ($id <= 0) die("Invalid ID.");
if ($name === "") die("Department name is required.");

// استخدام الدالة الجاهزة (تقوم بالتحديث وتسجيل النشاط)
dept_update($id, $name, $manager_id);

// ... بعد السطر: dept_update($id, $name, $manager_id); ...

// تحديث الفروع
$branches = $_POST['branches'] ?? []; // مصفوفة فارغة إذا لم يتم اختيار شيء
updateDepartmentBranches($id, $branches);

header("Location: list.php");

// التوجيه للقائمة
header("Location: list.php");
exit;
?>