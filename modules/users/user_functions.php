<?php
// modules/users/user_functions.php

// استخدام __DIR__ يضمن أن المسار صحيح دائماً
require_once __DIR__ . '/../../core/Database.php';

/**
 * جلب المستخدمين مع الفلاتر
 */
function getFilteredUsers($filters = []) {
    $db = Database::getInstance()->pdo();

    $sql = "
        SELECT u.*, r.role_name, d.name AS department_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.primary_role_id
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE (u.is_deleted = 0 OR u.is_deleted IS NULL)
    ";

    $params = [];

    // البحث
    if (!empty($filters['search'])) {
        $sql .= " AND (u.full_name_en LIKE :s OR u.full_name_ar LIKE :s OR u.email LIKE :s)";
        $params[':s'] = "%" . $filters['search'] . "%";
    }

    // الفلاتر
    if (!empty($filters['role_id'])) {
        $sql .= " AND u.primary_role_id = :role_id";
        $params[':role_id'] = $filters['role_id'];
    }
    if (!empty($filters['department_id'])) {
        $sql .= " AND u.department_id = :dept";
        $params[':dept'] = $filters['department_id'];
    }
    if (isset($filters['status']) && $filters['status'] !== '') {
        $sql .= " AND u.is_active = :st";
        $params[':st'] = $filters['status'];
    }

    $sql .= " ORDER BY u.id ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * الحذف الناعم (Soft Delete)
 */
function softDeleteUser($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        UPDATE users 
        SET is_deleted = 1, deleted_at = NOW() 
        WHERE id = ?
    ");
    return $stmt->execute([$id]);
}

/**
 * التحقق من إمكانية الحذف
 * يمنع الحذف إذا كان المستخدم مرتبطاً بمشاريع أو مبادرات
 */
function canDeleteUser($user_id) {
    $db = Database::getInstance()->pdo();

    $checks = [
        "initiatives" => "SELECT COUNT(*) FROM initiatives WHERE owner_user_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)",
        "projects"    => "SELECT COUNT(*) FROM operational_projects WHERE manager_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)",
        "team"        => "SELECT COUNT(*) FROM initiative_team WHERE user_id = ? AND is_active = 1",
        "collab"      => "SELECT COUNT(*) FROM collaborations WHERE requested_by = ? OR assigned_user_id = ? OR reviewed_by = ?"
    ];

    foreach ($checks as $key => $sql) {
        $stmt = $db->prepare($sql);
        if ($key === 'collab') {
            $stmt->execute([$user_id, $user_id, $user_id]);
        } else {
            $stmt->execute([$user_id]);
        }
        
        if ($stmt->fetchColumn() > 0) {
            return false; // لا يمكن الحذف لوجود بيانات مرتبطة
        }
    }
    return true; // يمكن الحذف
}

/**
 * جلب بيانات مستخدم واحد
 */
function getUserById($id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("
        SELECT u.*, r.role_name, d.name AS department_name
        FROM users u
        LEFT JOIN roles r ON r.id = u.primary_role_id
        LEFT JOIN departments d ON d.id = u.department_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * إحصائيات المستخدم
 */
function getUserStats($user_id) {
    $db = Database::getInstance()->pdo();
    
    // دوال مساعدة لجلب العدد
    $getCount = function($sql, $params) use ($db) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    };

    return [
        "initiatives" => $getCount("SELECT COUNT(*) FROM initiatives WHERE owner_user_id = ? AND (is_deleted=0 OR is_deleted IS NULL)", [$user_id]),
        "projects"    => $getCount("SELECT COUNT(*) FROM operational_projects WHERE manager_id = ? AND (is_deleted=0 OR is_deleted IS NULL)", [$user_id]),
        "collaborations" => $getCount("SELECT COUNT(*) FROM collaborations WHERE requested_by = ? OR assigned_user_id = ? OR reviewed_by = ?", [$user_id, $user_id, $user_id]),
        "team_memberships" => $getCount("SELECT COUNT(*) FROM initiative_team WHERE user_id = ?", [$user_id]),
        "last_login" => $getCount("SELECT last_login FROM users WHERE id = ?", [$user_id])
    ];
}

/**
 * دوال الإنشاء والتعديل
 */
function createUser(array $data) {
    $db = Database::getInstance()->pdo();
    
    // Check duplicates
    $check = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $check->execute([$data['username'], $data['email']]);
    if ($check->fetchColumn() > 0) return ['ok' => false, 'error' => 'Username or Email already exists'];

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name_en, full_name_ar, primary_role_id, department_id, phone, job_title, avatar, is_active, created_at, updated_at, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 0)");
    
    $ok = $stmt->execute([
        $data['username'], $data['email'], $hash, 
        $data['full_name_en'] ?? null, $data['full_name_ar'] ?? null, 
        $data['primary_role_id'] ?? null, $data['department_id'] ?? null, 
        $data['phone'] ?? null, $data['job_title'] ?? null, 
        $data['avatar'] ?? null, (int)($data['is_active'] ?? 1)
    ]);

    return $ok ? ['ok' => true, 'id' => $db->lastInsertId()] : ['ok' => false, 'error' => 'Insert Failed'];
}

function updateUser(int $id, array $data) {
    $db = Database::getInstance()->pdo();
    
    // Check duplicates (excluding self)
    if (!empty($data['username'])) {
        $c = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $c->execute([$data['username'], $id]);
        if ($c->fetchColumn() > 0) return ['ok' => false, 'error' => 'Username exists'];
    }
    if (!empty($data['email'])) {
        $c = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $c->execute([$data['email'], $id]);
        if ($c->fetchColumn() > 0) return ['ok' => false, 'error' => 'Email exists'];
    }

    $sql = "UPDATE users SET 
            username = COALESCE(?, username), email = COALESCE(?, email), 
            full_name_en = COALESCE(?, full_name_en), full_name_ar = COALESCE(?, full_name_ar),
            primary_role_id = COALESCE(?, primary_role_id), department_id = COALESCE(?, department_id),
            phone = COALESCE(?, phone), job_title = COALESCE(?, job_title),
            avatar = COALESCE(?, avatar), is_active = COALESCE(?, is_active),
            updated_at = NOW() WHERE id = ?";
            
    $stmt = $db->prepare($sql);
    $ok = $stmt->execute([
        $data['username'] ?? null, $data['email'] ?? null,
        $data['full_name_en'] ?? null, $data['full_name_ar'] ?? null,
        $data['primary_role_id'] ?? null, $data['department_id'] ?? null,
        $data['phone'] ?? null, $data['job_title'] ?? null,
        $data['avatar'] ?? null, isset($data['is_active']) ? (int)$data['is_active'] : null,
        $id
    ]);

    return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'Update Failed'];
}
/**
 * جلب جميع الفروع النشطة
 */
function getAllBranches() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY branch_name ASC")->fetchAll();
}

/**
 * جلب فروع المستخدم
 */
function getUserBranches($user_id) {
    $db = Database::getInstance()->pdo();
    $stmt = $db->prepare("SELECT branch_id FROM user_branches WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * تحديث فروع المستخدم
 */
function updateUserBranches($user_id, $branch_ids) {
    $db = Database::getInstance()->pdo();
    
    $del = $db->prepare("DELETE FROM user_branches WHERE user_id = ?");
    $del->execute([$user_id]);

    if (!empty($branch_ids) && is_array($branch_ids)) {
        $ins = $db->prepare("INSERT INTO user_branches (user_id, branch_id) VALUES (?, ?)");
        foreach ($branch_ids as $bid) {
            $ins->execute([$user_id, (int)$bid]);
        }
    }
}
?>