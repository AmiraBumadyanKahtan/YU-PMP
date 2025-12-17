<?php
// modules/operational_projects/project_functions.php

// تضمين الملفات الفرعية بالترتيب الصحيح
require_once __DIR__ . '/project_core.php';
require_once __DIR__ . '/project_milestones.php';
require_once __DIR__ . '/project_team.php';
require_once __DIR__ . '/project_approvals.php';
require_once __DIR__ . '/project_kpis.php';
require_once __DIR__ . '/project_risks.php';
require_once __DIR__ . '/project_doc.php';
require_once __DIR__ . '/project_update_reminders.php';

// modules/operational_projects/project_functions.php

// ==========================================
// New Access Control Functions
// ==========================================

// دالة لفحص الصلاحية داخل سياق المشروع (Context-Aware Permission Check)
function userCanInProject($project_id, $permission_key, $user_id = null) {
    if (!$user_id) $user_id = $_SESSION['user_id'];
    $role_key = $_SESSION['role_key'] ?? '';
    
    $db = Database::getInstance()->pdo();

    // 1. صلاحيات "سوبر" (دائماً نعم)
    if (in_array($role_key, ['super_admin', 'ceo', 'strategy_office'])) return true;

    // جلب بيانات المشروع
    $stmt = $db->prepare("SELECT manager_id, department_id FROM operational_projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $proj = $stmt->fetch();

    if (!$proj) return false;

    // 2. مدير المشروع (Project Manager)
    if ($proj['manager_id'] == $user_id) return true;

    // 3. رئيس القسم (Department Head)
    $deptStmt = $db->prepare("SELECT manager_id FROM departments WHERE id = ?");
    $deptStmt->execute([$proj['department_id']]);
    $deptManager = $deptStmt->fetchColumn();
    if ($deptManager == $user_id) return true;

    // جلب ID الصلاحية
    $permIdStmt = $db->prepare("SELECT id FROM permissions WHERE permission_key = ?");
    $permIdStmt->execute([$permission_key]);
    $permId = $permIdStmt->fetchColumn();
    if (!$permId) return false; // الصلاحية غير موجودة في النظام

    // 4. التحقق من الاستثناءات الخاصة (User Specific Override)
    // هذا الجدول له الأولوية القصوى (Override)
    $overrideStmt = $db->prepare("SELECT is_granted FROM project_user_permissions WHERE project_id=? AND user_id=? AND permission_id=?");
    $overrideStmt->execute([$project_id, $user_id, $permId]);
    $override = $overrideStmt->fetchColumn();

    if ($override !== false) {
        return ($override == 1); // 1 = منح صريح، 0 = منع صريح
    }

    // 5. التحقق من صلاحيات الدور داخل المشروع (Default Role Permission)
    $roleStmt = $db->prepare("
        SELECT 1
        FROM project_team pt
        JOIN project_role_permissions prp ON prp.role_id = pt.role_id
        WHERE pt.project_id = ? AND pt.user_id = ? AND prp.permission_id = ? AND pt.is_active = 1
    ");
    $roleStmt->execute([$project_id, $user_id, $permId]);
    
    return (bool) $roleStmt->fetchColumn();
}

// دالة لجلب قائمة الصلاحيات الخاصة بالمشاريع فقط (لعرضها في جدول الصلاحيات)
function getProjectPermissionsList() {
    $db = Database::getInstance()->pdo();
    return $db->query("SELECT * FROM permissions WHERE module = 'projects' ORDER BY permission_key")->fetchAll();
}
?>