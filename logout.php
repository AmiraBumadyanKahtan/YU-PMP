<?php
// logout.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/auth.php';

// استخدام الدالة المركزية في كلاس Auth لضمان تنظيف الجلسة بالكامل
Auth::logout();

// ملاحظة: دالة Auth::logout() تحتوي بداخلها على التوجيه (header location)
// ولكن للتأكيد، إذا لم تعمل الدالة لسبب ما، نضع التوجيه اليدوي هنا كاحتياط
header('Location: login.php?logout=1');
exit;
?>