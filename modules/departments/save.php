<?php
// modules/departments/save.php

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
$name = trim($_POST['name'] ?? '');
$manager_id = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;

if ($name === "") {
    die("Department name is required.");
}

// استخدام الدالة الجاهزة (تقوم بالحفظ وتسجيل النشاط)
dept_create($name, $manager_id);

// ... بعد السطر: $newId = dept_create($name, $manager_id); ...

// حفظ الفروع المختارة
if (isset($_POST['branches'])) {
    updateDepartmentBranches($newId, $_POST['branches']); // الدالة موجودة الآن في department_functions.php
}

// التوجيه للقائمة
header("Location: list.php");

// التوجيه للقائمة
header("Location: list.php");
exit;
?>