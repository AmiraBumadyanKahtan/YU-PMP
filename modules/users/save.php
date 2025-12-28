<?php
// modules/users/save.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "user_functions.php";

// ✅ التعديل: استخدام صلاحية الإنشاء المحددة
if (!Auth::can('sys_user_create')) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

$db = Database::getInstance()->pdo();

try {
    // 1. معالجة الصورة
    $avatarName = null;
    if (!empty($_FILES['avatar']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid image format. Allowed: " . implode(', ', $allowed));
        }

        $avatarName = "user_" . time() . "_" . rand(1000, 9999) . "." . $ext;
        $targetDir = __DIR__ . '/../../assets/uploads/avatars/';
        
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $targetDir . $avatarName)) {
            throw new Exception("Failed to upload avatar image.");
        }
    }

    // 2. تجهيز البيانات
    // توليد Username من الإيميل إذا لم يكن موجوداً، أو استخدام الاسم الأول
    $username = explode('@', $_POST['email'])[0];

    $userData = [
        'username'        => $username,
        'email'           => trim($_POST['email']),
        'password'        => $_POST['password'],
        'full_name_en'    => trim($_POST['full_name_en']),
        'full_name_ar'    => null, 
        'primary_role_id' => $_POST['role_id'],
        'department_id'   => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
        'phone'           => $_POST['phone'],
        'job_title'       => $_POST['job_title'],
        'avatar'          => $avatarName,
        'is_active'       => $_POST['is_active']
    ];

    // 3. الحفظ (Transaction)
    $db->beginTransaction();

    $result = createUser($userData); // هذه الدالة تقوم بالفحص والادخال

    if ($result['ok']) {
        $newUserId = $result['id'];

        // حفظ الفروع
        if (isset($_POST['branches']) && is_array($_POST['branches'])) {
            updateUserBranches($newUserId, $_POST['branches']);
        }

        $db->commit();
        
        $_SESSION['success'] = "User account created successfully!";
        header("Location: list.php");
        exit;

    } else {
        // إذا فشل إنشاء المستخدم (مثلاً تكرار)
        throw new Exception($result['error']);
    }

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // حذف الصورة المرفوعة إذا فشلت العملية لتوفير المساحة
    if ($avatarName && file_exists($targetDir . $avatarName)) {
        unlink($targetDir . $avatarName);
    }

    $_SESSION['error'] = $e->getMessage();
    header("Location: create.php"); // العودة للفورم مع الخطأ
    exit;
}
?>