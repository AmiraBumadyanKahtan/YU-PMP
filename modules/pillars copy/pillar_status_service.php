<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/strategic-project-system/core/init.php';

class PillarStatusService
{
    public static function getStatusIdByName(string $name): ?int
    {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("SELECT id FROM pillar_statuses WHERE name = ?");
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public static function getStatusNameById(int $id): ?string
    {
        $db = Database::getInstance()->pdo();
        $stmt = $db->prepare("SELECT name FROM pillar_statuses WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: null;
    }

    public static function updatePillarStatusFromInitiatives(int $pillarId): void
    {
        $db = Database::getInstance()->pdo();

        // جلب بيانات الركيزة
        $stmt = $db->prepare("SELECT * FROM pillars WHERE id = ?");
        $stmt->execute([$pillarId]);
        $pillar = $stmt->fetch();

        if (!$pillar) return;

        // حالات مرحلة الموافقات = لا يتم تغييرها تلقائيًا
        // حالات مرحلة الموافقات = لا يتم تغييرها تلقائيًا
        // ملاحظة: بعد موافقة الـ CEO بنستخدم حالة "Approved" كمرحلة انتقالية
        // ثم نسمح للدالة إنها تغيّر الحالة إلى Pending / In Progress ... إلخ
        $manualStates = [
            'Pending Review',
            'Waiting CEO Approval',
            'Rejected'
        ];


        $currentStatus = self::getStatusNameById($pillar['status_id']);
        if (in_array($currentStatus, $manualStates)) {
            return;
        }

        $today = date('Y-m-d');

        // جلب المبادرات المرتبطة
        $stmt = $db->prepare("
            SELECT i.*, LOWER(s.name) AS status_name
            FROM initiatives i
            LEFT JOIN initiative_statuses s ON i.status_id = s.id
            WHERE i.pillar_id = ?
        ");
        $stmt->execute([$pillarId]);
        $initiatives = $stmt->fetchAll();

        $total      = count($initiatives);
        $completed  = 0;
        $atRisk     = 0;
        $delayed    = 0;
        $inProgress = 0;
        $notStarted = 0;

        foreach ($initiatives as $row) {
            $status = $row['status_name'] ?? '';

            if ($status === 'completed' || (int)$row['progress_percentage'] >= 100) {
                $completed++;
            } elseif (in_array($status, ['delayed', 'off_track'])) {
                $delayed++;
            } elseif (in_array($status, ['at_risk', 'risk'])) {
                $atRisk++;
            } elseif (in_array($status, ['in_progress', 'on_track'])) {
                $inProgress++;
            } else {
                $notStarted++;
            }
        }

        // تحديد الحالة
        if ($total > 0 && $completed === $total) {
            $newStatus = 'Completed';
        }
        elseif ($delayed > 0 || (!empty($pillar['end_date']) && $today > $pillar['end_date'] && $completed < $total)) {
            $newStatus = 'Delayed';
        }
        elseif ($atRisk > 0) {
            $newStatus = 'At Risk';
        }
        elseif ($inProgress > 0) {
            $newStatus = 'On Track';
        }
        else {
            if (!empty($pillar['start_date']) && $today >= $pillar['start_date']) {
                $newStatus = 'In Progress';
            } else {
                $newStatus = 'Pending';
            }
        }

        $statusId = self::getStatusIdByName($newStatus);
        if ($statusId) {
            $update = $db->prepare("UPDATE pillars SET status_id = ? WHERE id = ?");
            $update->execute([$statusId, $pillarId]);
        }
    }
}
