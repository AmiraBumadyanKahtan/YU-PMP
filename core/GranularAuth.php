<?php
// core/GranularAuth.php
require_once __DIR__ . '/Database.php';

class GranularAuth {

    /**
     * التحقق من الصلاحيات (Pillar, Initiative, Project)
     */
    public static function can($entityType, $entityId, $permKey, $itemId = null, $itemTable = null) {
        
        // 1. Super Admin Bypass
        if (isset($_SESSION['role_key']) && $_SESSION['role_key'] === 'super_admin') {
            return true;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId == 0) return false;

        $db = Database::getInstance()->pdo();

        // 2. Department Manager Bypass (للمشاريع والمبادرات المرتبطة بقسم)
        // (ملاحظة: الركائز عادة لا تتبع قسماً، لكن المبادرات والمشاريع قد تتبع)
        if ($entityType !== 'pillar' && self::isDeptManager($db, $userId, $entityType, $entityId)) {
            return true;
        }

        // 3. Role-Based Check (داخل الفريق)
        // تحديد الجداول بناءً على نوع الكيان
        switch ($entityType) {
            case 'pillar':
                $teamTable = 'pillar_team';
                $rolePermTable = 'pillar_role_permissions';
                $fkCol = 'pillar_id';
                break;
            case 'initiative':
                $teamTable = 'initiative_team';
                $rolePermTable = 'initiative_role_permissions';
                $fkCol = 'initiative_id';
                break;
            case 'project':
                $teamTable = 'project_team';
                $rolePermTable = 'project_role_permissions';
                $fkCol = 'project_id';
                break;
            default:
                return false;
        }

        // الاستعلام: هل يملك المستخدم دوراً في هذا الكيان يمنحه الصلاحية؟
        // (تم إضافة شرط is_active للتأكد أن العضو فعال)
        $sql = "
            SELECT COUNT(*) 
            FROM $teamTable t
            JOIN $rolePermTable rp ON t.role_id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE t.$fkCol = ? 
              AND t.user_id = ? 
              AND p.permission_key = ?
        ";
        
        // بعض جداول التيم قد تحتوي على is_active والبعض لا، تأكد من قاعدة بياناتك
        // في الدامب الخاص بك: initiative_team و project_team لديهم is_active
        // pillar_team ليس لديه is_active في الدامب، لذا سنضيف الشرط ديناميكياً
        if ($entityType !== 'pillar') {
            $sql .= " AND t.is_active = 1";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute([$entityId, $userId, $permKey]);
        
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // 4. Ownership Check (لمن لا يملك صلاحية الدور، هل هو صاحب العنصر؟)
        if ($itemId && $itemTable) {
            return self::checkOwnership($db, $userId, $permKey, $itemId, $itemTable);
        }

        return false;
    }

    /**
     * فحص هل المستخدم هو مدير القسم المالك
     */
    private static function isDeptManager($db, $userId, $entityType, $entityId) {
        // المشاريع التشغيلية
        if ($entityType === 'project') {
            $sql = "SELECT COUNT(*) FROM operational_projects op 
                    JOIN departments d ON op.department_id = d.id 
                    WHERE op.id = ? AND d.manager_id = ? AND (d.is_deleted = 0 OR d.is_deleted IS NULL)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$entityId, $userId]);
            return $stmt->fetchColumn() > 0;
        }
        
        // المبادرات (إذا كانت مرتبطة بقسم في المستقبل، حالياً لا يوجد عمود department_id مباشر في initiatives في الدامب)
        // لكن لو أضفت department_id لجدول initiatives، أضف الكود هنا.
        return false;
    }

    /**
     * فحص الملكية (Owner/Assignee)
     */
    private static function checkOwnership($db, $userId, $permKey, $itemId, $itemTable) {
        
        // A. المهام (Tasks) - سواء للمشروع أو المبادرة
        if (in_array($itemTable, ['project_tasks', 'initiative_tasks'])) {
            // صلاحية تحديث الإنجاز فقط للمسؤول عن المهمة
            if (in_array($permKey, ['ptask_update_progress', 'itask_update_progress'])) {
                $stmt = $db->prepare("SELECT assigned_to FROM $itemTable WHERE id = ?");
                $stmt->execute([$itemId]);
                return ($stmt->fetchColumn() == $userId);
            }
        }

        // B. KPIs (مؤشرات الأداء)
        if (in_array($itemTable, ['kpis'])) { // الجدول موحد للكل
            if (in_array($permKey, ['pkpi_update_reading', 'ikpi_update_reading'])) {
                $stmt = $db->prepare("SELECT owner_id FROM kpis WHERE id = ?");
                $stmt->execute([$itemId]);
                return ($stmt->fetchColumn() == $userId);
            }
        }

        return false;
    }
}
?>