<?php
// core/config.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إعدادات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_PORT', '3308'); // تأكد أن المنفذ صحيح لديك، الافتراضي عادة 3306
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pms_yu_system'); // اسم قاعدة البيانات الجديد
define('DB_CHARSET', 'utf8mb4');

// المسار الأساسي للنظام
define('BASE_URL', '/strategic-project-system/');

// دالة مساعدة للوصول السريع للاتصال
if (!function_exists('db')) {
    function db() {
        return Database::getInstance()->pdo();
    }
}
?>