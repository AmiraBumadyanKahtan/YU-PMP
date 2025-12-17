<?php
// modules/pillars/functions.php

// استدعاء ملفات الخدمات
require_once "services/PillarService.php";
require_once "services/StrategicObjectiveService.php";
require_once "services/PillarTeamService.php";
require_once "services/PillarDocumentService.php";
require_once "services/PillarInitiativeService.php";

// Wrappers (لتشغيل الكود القديم كما هو)

function getPillars($search = '', $status_id = '') {
    return PillarService::getAll($search, $status_id);
}

function getPillarById($id) {
    return PillarService::getById($id);
}

function createPillar($data) {
    return PillarService::create($data);
}

function updatePillar($id, $data) {
    return PillarService::update($id, $data);
}

function deletePillar($id) {
    return PillarService::delete($id);
}

function getPillarStatuses() {
    return PillarService::getStatuses();
}

function submitPillarForApproval($pillar_id, $user_id) {
    return PillarService::submitForApproval($pillar_id, $user_id);
}

function getPillarWorkflowTracker($pillar_id) {
    return PillarService::getWorkflowTracker($pillar_id);
}

// modules/pillars/functions.php

function updatePillarStatusAutomatic($pillar_id) {
    $db = Database::getInstance()->pdo();
    
    // 1. 🟢 حساب وتحديث نسبة الإنجاز (Progress Calculation)
    // نجمع نسب إنجاز المبادرات التابعة لهذه الركيزة ونقسمها على عددها
    $stmt = $db->prepare("
        SELECT AVG(progress_percentage) 
        FROM initiatives 
        WHERE pillar_id = ? AND (is_deleted = 0 OR is_deleted IS NULL)
    ");
    $stmt->execute([$pillar_id]);
    $avgProgress = $stmt->fetchColumn();
    
    // إذا لم توجد مبادرات، النسبة 0. وإلا نقرب الرقم لأقرب عدد صحيح
    $finalProgress = $avgProgress ? round($avgProgress) : 0;
    
    // تحديث نسبة الركيزة في قاعدة البيانات
    $db->prepare("UPDATE pillars SET progress_percentage = ? WHERE id = ?")
       ->execute([$finalProgress, $pillar_id]);


    // 2. 🟠 منطق تحديث الحالة (Status Logic) - (كما هو سابقاً)
    $pillar = $db->query("SELECT * FROM pillars WHERE id=$pillar_id")->fetch();
    $initiatives = $db->query("SELECT status_id, start_date FROM initiatives WHERE pillar_id=$pillar_id AND is_deleted=0")->fetchAll();
    
    $currentStatus = $pillar['status_id'];
    $today = date('Y-m-d');
    
    // Approved (11) -> Pending (2) إذا أضيفت مبادرات
    if ($currentStatus == 11 && count($initiatives) > 0) {
        $db->prepare("UPDATE pillars SET status_id = 2 WHERE id=?")->execute([$pillar_id]);
        $currentStatus = 2; 
    }
    
    // Pending (2) أو Approved (11) -> In Progress (3)
    if ($currentStatus == 2 || $currentStatus == 11) {
        $shouldStart = false;
        // إذا حل تاريخ البدء
        if ($pillar['start_date'] <= $today) $shouldStart = true;
        
        // أو إذا بدأت أي مبادرة فعلياً
        foreach ($initiatives as $init) {
            // نفترض أن الحالة 9 هي In Progress للمبادرات، أو أن تاريخ بدئها حل
            if ($init['status_id'] == 9 || ($init['start_date'] <= $today && $init['start_date'] != null)) {
                $shouldStart = true;
                break;
            }
        }
        
        if ($shouldStart) {
            $db->prepare("UPDATE pillars SET status_id = 3 WHERE id=?")->execute([$pillar_id]);
        }
    }
}

// Objectives (Modified to Auto-Generate Code)
function addStrategicObjective($pillar_id, $text) { // تم إزالة $code
    return StrategicObjectiveService::add($pillar_id, $text);
}

function deleteStrategicObjective($obj_id) {
    return StrategicObjectiveService::delete($obj_id);
}

function getPillarObjectives($pillar_id) {
    return StrategicObjectiveService::getAllByPillar($pillar_id);
}

// Team
function addPillarMember($pillar_id, $user_id, $role_id) {
    return PillarTeamService::addMember($pillar_id, $user_id, $role_id);
}

function removePillarMember($id) {
    return PillarTeamService::removeMember($id);
}

function getPillarTeam($pillar_id) {
    return PillarTeamService::getTeam($pillar_id);
}

function getPillarRoles() {
    return PillarTeamService::getRoles();
}

// Docs
function getPillarDocuments($pillar_id) {
    return PillarDocumentService::getAll($pillar_id);
}

function uploadPillarDocument($data, $file) {
    // نحتاج user_id هنا، لذا نأخذه من الجلسة
    return PillarDocumentService::upload($data, $file, $_SESSION['user_id']);
}

function deleteDocument($doc_id) {
    return PillarDocumentService::delete($doc_id);
}

// Initiatives
function getPillarInitiatives($pillar_id) {
    return PillarInitiativeService::getAll($pillar_id);
}

// أضف هذه الدالة في functions.php
function getPillarsStats() {
    $db = Database::getInstance()->pdo();
    
    // عدد الركائز
    $totalPillars = $db->query("SELECT COUNT(*) FROM pillars WHERE is_deleted=0")->fetchColumn();
    
    // عدد الأعضاء الفريدين
    $totalMembers = $db->query("SELECT COUNT(DISTINCT user_id) FROM pillar_team")->fetchColumn();
    
    // عدد المبادرات المرتبطة بالركائز
    $totalInitiatives = $db->query("
        SELECT COUNT(*) FROM initiatives i 
        JOIN pillars p ON p.id = i.pillar_id 
        WHERE i.is_deleted=0 AND p.is_deleted=0
    ")->fetchColumn();

    // متوسط الإنجاز العام
    $avgProgress = $db->query("SELECT AVG(progress_percentage) FROM pillars WHERE is_deleted=0")->fetchColumn();

    return [
        'pillars' => $totalPillars,
        'members' => $totalMembers,
        'initiatives' => $totalInitiatives,
        'avg_progress' => round($avgProgress, 1)
    ];
}
?>