<?php
// modules/pillars/functions.php

// استدعاء ملفات الخدمات
require_once "services/PillarService.php";
require_once "services/StrategicObjectiveService.php";
require_once "services/PillarTeamService.php";
require_once "services/PillarDocumentService.php";
require_once "services/PillarInitiativeService.php";

// Wrappers (لتشغيل الكود القديم كما هو)
// Wrappers (لتشغيل الكود القديم كما هو)

function getPillars($search = '', $status_id = '') { return PillarService::getAll($search, $status_id); }
function getPillarById($id) { return PillarService::getById($id); }
function createPillar($data) { return PillarService::create($data); }
function updatePillar($id, $data) { return PillarService::update($id, $data); }
function deletePillar($id) { return PillarService::delete($id); }
function getPillarStatuses() { return PillarService::getStatuses(); }
function submitPillarForApproval($pillar_id, $user_id) { return PillarService::submitForApproval($pillar_id, $user_id); }
function getPillarWorkflowTracker($pillar_id) { return PillarService::getWorkflowTracker($pillar_id); }
function getPillarObjectives($pillar_id) { return StrategicObjectiveService::getAllByPillar($pillar_id); }
function addStrategicObjective($pillar_id, $text) { return StrategicObjectiveService::add($pillar_id, $text); }
function deleteStrategicObjective($obj_id) { return StrategicObjectiveService::delete($obj_id); }
function getPillarTeam($pillar_id) { return PillarTeamService::getTeam($pillar_id); }
function addPillarMember($pillar_id, $user_id, $role_id) { return PillarTeamService::addMember($pillar_id, $user_id, $role_id); }
function removePillarMember($id) { return PillarTeamService::removeMember($id); }
function getPillarRoles() { return PillarTeamService::getRoles(); }
function getPillarDocuments($pillar_id) { return PillarDocumentService::getAll($pillar_id); }
function uploadPillarDocument($data, $file) { return PillarDocumentService::upload($data, $file, $_SESSION['user_id']); }
function deleteDocument($doc_id) { return PillarDocumentService::delete($doc_id); }
function getPillarInitiatives($pillar_id) { return PillarInitiativeService::getAll($pillar_id); }
function getPillarsStats() {
    $db = Database::getInstance()->pdo();
    $totalPillars = $db->query("SELECT COUNT(*) FROM pillars WHERE is_deleted=0")->fetchColumn();
    $totalMembers = $db->query("SELECT COUNT(DISTINCT user_id) FROM pillar_team")->fetchColumn();
    $totalInitiatives = $db->query("SELECT COUNT(*) FROM initiatives i JOIN pillars p ON p.id = i.pillar_id WHERE i.is_deleted=0 AND p.is_deleted=0")->fetchColumn();
    $avgProgress = $db->query("SELECT AVG(progress_percentage) FROM pillars WHERE is_deleted=0")->fetchColumn();
    return ['pillars' => $totalPillars, 'members' => $totalMembers, 'initiatives' => $totalInitiatives, 'avg_progress' => round($avgProgress, 1)];
}

// ====================================================================================
// ✅ الدالة الذكية المحدثة لتحديث حالة ونسبة الركيزة
// ====================================================================================
function updatePillarStatusAutomatic($pillar_id) {
    $db = Database::getInstance()->pdo();
    $today = date('Y-m-d');

    // 1. جلب بيانات الركيزة والمبادرات والمخاطر
    $pillar = $db->query("SELECT * FROM pillars WHERE id=$pillar_id")->fetch(PDO::FETCH_ASSOC);
    if (!$pillar) return;

    // المبادرات (نسب الإنجاز والحالة)
    $initiatives = $db->query("
        SELECT status_id, progress_percentage, start_date, due_date 
        FROM initiatives 
        WHERE pillar_id=$pillar_id AND (is_deleted=0 OR is_deleted IS NULL)
    ")->fetchAll(PDO::FETCH_ASSOC);

    // المخاطر النشطة (غير محلولة)
    $activeRisksCount = $db->query("
        SELECT COUNT(*) FROM risk_assessments 
        WHERE parent_type='pillar' AND parent_id=$pillar_id 
        AND status_id NOT IN (3, 4) -- Assuming 3=Resolved, 4=Closed
    ")->fetchColumn();

    // 2. 🟢 حساب نسبة الإنجاز (متوسط نسب المبادرات)
    $totalProgress = 0;
    $countInits = count($initiatives);
    if ($countInits > 0) {
        foreach ($initiatives as $init) {
            $totalProgress += $init['progress_percentage'];
        }
        $finalProgress = round($totalProgress / $countInits);
    } else {
        $finalProgress = 0;
    }

    // تحديث النسبة في القاعدة
    $db->prepare("UPDATE pillars SET progress_percentage = ? WHERE id = ?")->execute([$finalProgress, $pillar_id]);

    // 3. 🟠 منطق تحديد الحالة (Status Logic)
    $currentStatus = $pillar['status_id'];
    $newStatus = $currentStatus;

    // ملاحظة: نفترض الـ IDs التالية بناءً على جدول pillar_statuses:
    // 2=Pending, 3=In Progress, 4=On Track, 5=At Risk, 8=Delayed, 11=Approved, 7=Completed
    // (يجب التأكد من الـ IDs في قاعدة البيانات لديك، سأستخدم القيم الافتراضية الشائعة)
    
    // لا نغير الحالة إذا كانت Draft (12) أو Pending Review (9) أو Rejected (13) أو Completed (7)
    // نغير فقط إذا كانت Approved (11) أو الحالات النشطة الأخرى
    
    // إذا كانت الحالة "Approved" (11) أو أي حالة نشطة (On Track, In Progress, At Risk, Delayed)
    if (in_array($currentStatus, [11, 3, 4, 5, 8])) {

        // أ) القاعدة الأساسية: Approved
        $newStatus = 11; // Approved

        // ب) هل حان وقت البدء؟ -> On Track (4)
        if ($pillar['start_date'] <= $today) {
            $newStatus = 4; // On Track
        }

        // ج) هل تم إضافة مبادرة وبدأ العمل؟ -> In Progress (3)
        if ($countInits > 0) {
            $newStatus = 3; // In Progress
        }

        // د) هل هناك مخاطر نشطة؟ -> At Risk (5)
        if ($activeRisksCount > 0) {
            $newStatus = 5; // At Risk
        }

        // هـ) هل انتهى الوقت ولم يكتمل العمل؟ -> Delayed (8)
        // (إذا التاريخ الحالي تجاوز تاريخ النهاية والنسبة أقل من 100%)
        if ($pillar['end_date'] < $today && $finalProgress < 100) {
            $newStatus = 8; // Delayed
        }

        // و) هل اكتمل العمل تماماً؟ -> Completed (7)
        if ($finalProgress == 100 && $countInits > 0) {
            $newStatus = 7; // Completed
        }
        
        // تحديث الحالة إذا تغيرت
        if ($newStatus != $currentStatus) {
            $db->prepare("UPDATE pillars SET status_id = ? WHERE id = ?")->execute([$newStatus, $pillar_id]);
        }
    }
}
?>