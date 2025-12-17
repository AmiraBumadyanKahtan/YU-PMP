<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();

    $initId = !empty($_POST['initiative_id']) ? $_POST['initiative_id'] : null;
    $projId = !empty($_POST['project_id']) ? $_POST['project_id'] : null;
    
    $data = [
        'initiative_id' => $initId,
        'project_id' => $projId, // هام جداً
        'risk_title_en' => $_POST['risk_title'],
        'risk_title_ar' => $_POST['risk_title'],
        'risk_description_en' => $_POST['risk_description'],
        'risk_description_ar' => $_POST['risk_description'],
        'mitigation_plan_en' => $_POST['mitigation_plan'],
        'mitigation_plan_ar' => $_POST['mitigation_plan'],
        'probability' => $_POST['probability'],
        'impact' => $_POST['impact'],
        'status' => 'identified',
        'identified_date' => date('Y-m-d')
    ];
    
    $db->insert('risk_assessments', $data);

    if ($projId) {
        header("Location: ../project_detail.php?id=" . $projId . "&msg=risk_reported");
    } else {
        header("Location: ../initiative_detail.php?id=" . $initId . "&msg=risk_reported");
    }
}
?>