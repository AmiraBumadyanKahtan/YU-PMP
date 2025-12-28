<?php
// modules/operational_projects/php/request_collab_BE.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php"; // يضمن notification_helper.php تلقائياً

// التحقق من وجود ID
$id = $_GET['id'] ?? 0;
if ($id <= 0) die("Invalid Project ID");

// جلب بيانات المشروع
$project = getProjectById($id);
if (!$project) die("Project not found");

// ============================================================
// 1. التحقق من حالة المشروع (هل هو قابل للتعديل؟)
// ============================================================
// الحالات المقفلة: 2 (Pending Review), 4 (Rejected), 8 (Completed), 7 (On Hold)
$lockedStatuses = [1, 2, 4, 8, 7]; 
$isProjectEditable = !in_array($project['status_id'], $lockedStatuses);

if (!$isProjectEditable) {
    die("Access Denied: This project is locked (Pending, Completed, or On Hold) and cannot accept new requests.");
}

// ============================================================
// 2. التحقق من الصلاحية باستخدام الدالة الموحدة
// ============================================================
// نستخدم صلاحية 'proj_manage_team' لأن طلب الموارد هو جزء من إدارة الفريق
// هذه الدالة تعيد true تلقائياً للسوبر أدمن، الـ CEO، ومدير المشروع
if (!userCanInProject($id, 'proj_manage_team')) {
    die("Access Denied: You do not have permission to request resources for this project.");
}

$db = Database::getInstance()->pdo();

// معالجة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dept_id = $_POST['department_id'];
    $reason = trim($_POST['reason']);
    
    if (empty($target_dept_id) || empty($reason)) {
        $error = "Please fill in all fields.";
    } else {
        // 1. معرفة مدير القسم المستهدف
        $deptQuery = $db->prepare("SELECT manager_id, name FROM departments WHERE id = ?");
        $deptQuery->execute([$target_dept_id]);
        $targetDept = $deptQuery->fetch(PDO::FETCH_ASSOC);
        
        $targetManagerId = $targetDept['manager_id'] ?? null;

        if (!$targetManagerId) {
            $error = "The selected department does not have a manager assigned to review your request.";
        } else {
            // 2. إنشاء السجل
            $stmt = $db->prepare("
                INSERT INTO collaborations 
                (parent_type, parent_id, department_id, reason, requested_by, status_id, created_at) 
                VALUES ('project', ?, ?, ?, ?, 1, NOW())
            ");
            
            if ($stmt->execute([$id, $target_dept_id, $reason, $_SESSION['user_id']])) {
                $collabId = $db->lastInsertId();

                // 3. إرسال تنبيه لمدير القسم المستهدف
                if (function_exists('sendProjectNotification')) {
                    sendProjectNotification(
                        $targetManagerId, 
                        "Resource Request: " . substr($project['name'], 0, 30), 
                        "Project '{$project['name']}' is requesting a resource from your department ({$targetDept['name']}).\nReason: " . substr($reason, 0, 100) . "...", 
                        "collaboration_review", // سيتم توجيهه لصفحة المراجعة
                        $collabId
                    );
                }

                header("Location: collaborations.php?id=$id&msg=requested");
                exit;
            } else {
                $error = "Failed to send request. Database error.";
            }
        }
    }
}

// جلب الأقسام المتاحة (استثناء قسم المشروع الحالي)
$projectDeptId = $project['department_id'];
$departments = $db->query("SELECT * FROM departments WHERE id != $projectDeptId AND is_deleted = 0 ORDER BY name ASC")->fetchAll();
?>