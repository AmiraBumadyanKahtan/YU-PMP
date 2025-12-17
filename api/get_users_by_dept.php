<?php
// api/get_users_by_dept.php

// منع أي مخرجات HTML قد تأتي من ملفات أخرى بالخطأ
ob_start();

header('Content-Type: application/json');

// تصحيح المسار للوصول إلى core/config.php و core/Database.php
// بما أن هذا الملف داخل مجلد 'api'، فنحتاج للخروج خطوة واحدة للخلف (../)
$configPath = __DIR__ . '/../core/config.php';
$dbPath = __DIR__ . '/../core/Database.php';

if (!file_exists($configPath) || !file_exists($dbPath)) {
    // تنظيف المخزن المؤقت وإرجاع خطأ JSON
    ob_end_clean();
    echo json_encode(['error' => 'Core files not found']);
    exit;
}

require_once $configPath;
require_once $dbPath;

// تنظيف أي مخرجات سابقة (مثل مسافات فارغة أو HTML من ملفات التضمين)
ob_end_clean();

$deptId = isset($_GET['dept_id']) ? intval($_GET['dept_id']) : 0;

if ($deptId > 0) {
    try {
        $db = Database::getInstance()->pdo();
        // جلب المستخدمين النشطين في هذا القسم
        $stmt = $db->prepare("SELECT id, full_name_en FROM users WHERE department_id = ? AND is_active = 1 ORDER BY full_name_en");
        $stmt->execute([$deptId]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($users);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
?>