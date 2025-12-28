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
define('BASE_URL', 'http://localhost:8084/strategic-project-system/');

// دالة مساعدة للوصول السريع للاتصال
if (!function_exists('db')) {
    function db() {
        return Database::getInstance()->pdo();
    }
}
// =============================================
// إعدادات الإيميل (SMTP Settings)
// =============================================
/*define('SMTP_HOST', 'smtp.gmail.com');      // أو السيرفر الخاص بكم
define('SMTP_USER', 'amirakahtan@gmail.com'); // إيميل المرسل
define('SMTP_PASS', 'mkkz ohav aakv ljyf');  // كلمة المرور (App Password)
define('SMTP_PORT', 587);                   // المنفذ (587 لـ TLS)
define('SMTP_SECURE', 'tls');               // نوع التشفير

define('SMTP_FROM_EMAIL', 'no-reply@system.com'); // الإيميل الظاهر للمستلم
define('SMTP_FROM_NAME', 'PMS Notifications');    // الاسم الظاهر
*/

// =============================================
// إعدادات الإيميل (Outlook / Office 365)
// =============================================
define('SMTP_HOST', 'smtp.office365.com'); 
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// معلومات حسابك
define('SMTP_USER', 'pms@yu.edu.sa'); 

// كلمة المرور
define('SMTP_PASS', 'SPmng@20;25./~'); 

// الترويسة
define('SMTP_FROM_EMAIL', 'pms@yu.edu.sa');
define('SMTP_FROM_NAME', 'PMS System Notifications');
?>