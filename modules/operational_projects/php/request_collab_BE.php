<?php
// modules/operational_projects/request_collab.php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "project_functions.php";

// التحقق من وجود ID
$id = $_GET['id'] ?? 0;
if ($id <= 0) die("Invalid Project ID");

// جلب بيانات المشروع
$project = getProjectById($id);
if (!$project) die("Project not found");

// التحقق من الصلاحية
$canRequest = (Auth::can('manage_project_team') || $project['manager_id'] == $_SESSION['user_id']);
if (!$canRequest) {
    die("Access Denied. You are not the manager of this project.");
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
        $deptQuery = $db->prepare("SELECT manager_id FROM departments WHERE id = ?");
        $deptQuery->execute([$target_dept_id]);
        $targetManagerId = $deptQuery->fetchColumn();

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

                // 3. إرسال تنبيه (Todo)
                require_once "../../modules/todos/todo_functions.php";
                
                addSystemTodo(
                    $targetManagerId, 
                    "Resource Request: " . substr($project['name'], 0, 30), 
                    "Project '{$project['name']}' is requesting a resource from your department.", 
                    "collaboration", 
                    $collabId
                );

                header("Location: collaborations.php?id=$id&msg=requested");
                exit;
            } else {
                $error = "Failed to send request. Database error.";
            }
        }
    }
}

// جلب الأقسام المتاحة
$projectDeptId = $project['department_id'];
$departments = $db->query("SELECT * FROM departments WHERE id != $projectDeptId AND is_deleted = 0 ORDER BY name ASC")->fetchAll();
?>