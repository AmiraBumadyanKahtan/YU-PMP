<?php
// core/auth.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

class Auth
{
    public static function login(string $username, string $password): bool
    {
        $db = Database::getInstance()->pdo();

        // 1) جلب بيانات المستخدم بناءً على الاسم أو الإيميل
        // تم التأكد من أسماء الأعمدة: primary_role_id, is_active, is_deleted
        $stmt = $db->prepare("
            SELECT u.*, r.role_key, r.role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.primary_role_id
            WHERE (u.username = :username OR u.email = :username)
              AND u.is_active = 1
              AND (u.is_deleted = 0 OR u.is_deleted IS NULL)
            LIMIT 1
        ");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user) return false;

        // التحقق من كلمة المرور المشفرة
        if (!password_verify($password, $user['password'])) return false;

        // 2) جلب الصلاحيات من الدور الأساسي (Primary Role)
        $permissions = [];
        if (!empty($user['primary_role_id'])) {
            $permStmt = $db->prepare("
                SELECT p.permission_key
                FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.id
                WHERE rp.role_id = ?
            ");
            $permStmt->execute([$user['primary_role_id']]);
            $permissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // 3) جلب الصلاحيات من الأدوار الإضافية (User Roles)
        $extraRolePerm = $db->prepare("
            SELECT p.permission_key
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            JOIN user_roles ur ON ur.role_id = rp.role_id
            WHERE ur.user_id = ?
        ");
        $extraRolePerm->execute([$user['id']]);
        $extraPerms = $extraRolePerm->fetchAll(PDO::FETCH_COLUMN);

        // دمج الصلاحيات وحذف التكرار
        $permissions = array_unique(array_merge($permissions, $extraPerms));

        // 4) تخزين البيانات في الجلسة (Session)
        // ملاحظة: استخدمنا full_name_en لأنه العمود الموجود في جدول المستخدمين الجديد
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['full_name']    = $user['full_name_en']; 
        $_SESSION['role_id']      = $user['primary_role_id'];
        $_SESSION['role_key']     = $user['role_key'];
        $_SESSION['role_name']    = $user['role_name'];
        $_SESSION['department_id']= $user['department_id']; // مفيد للمشاريع
        $_SESSION['permissions']  = $permissions;

        // 5) تحديث وقت آخر تسجيل دخول
        $update = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update->execute([$user['id']]);

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function logout()
    {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    public static function role($roleKeys): bool
    {
        if (!self::check()) return false;
        
        // التحقق من مفتاح الدور (role_key) مثل: super_admin, ceo
        $currentRole = $_SESSION['role_key'] ?? '';

        return is_array($roleKeys)
            ? in_array($currentRole, $roleKeys)
            : $currentRole === $roleKeys;
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) return false;
        // التحقق من Permission Key
        return in_array($permission, $_SESSION['permissions'] ?? []);
    }

    public static function id()
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function user()
    {
        return $_SESSION; // إرجاع كافة بيانات الجلسة عند الحاجة
    }
}
?>