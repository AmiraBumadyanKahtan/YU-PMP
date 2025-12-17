<?php
$userId = $_SESSION['user_id'];

// --- 1. Fetch Data ---
// A. My Initiatives
$myInitiatives = $db->fetchAll("SELECT i.*, p.name_en as pillar_name_en, p.name_ar as pillar_name_ar FROM initiatives i LEFT JOIN pillars p ON i.pillar_id = p.id WHERE i.owner_user_id = ? ORDER BY i.status ASC, i.due_date ASC", [$userId]);
$totalMyProjects = count($myInitiatives);
$activeProjects = 0;
foreach($myInitiatives as $init) if(in_array($init['status'], ['in_progress', 'on_track', 'at_risk'])) $activeProjects++;

// B. Upcoming Milestones
$upcomingMilestones = $db->fetchAll("SELECT m.*, i.name_en as initiative_name_en, i.name_ar as initiative_name_ar FROM milestones m JOIN initiatives i ON m.initiative_id = i.id WHERE i.owner_user_id = ? AND m.status != 'completed' AND m.due_date IS NOT NULL ORDER BY m.due_date ASC LIMIT 6", [$userId]);
$urgentTasksCount = 0;
foreach($upcomingMilestones as $m) if(strtotime($m['due_date']) < strtotime('+7 days')) $urgentTasksCount++;

// C. Risks
$myRisks = $db->fetchOne("SELECT COUNT(*) as c FROM risk_assessments r JOIN initiatives i ON r.initiative_id = i.id WHERE i.owner_user_id = ? AND r.status != 'resolved'", [$userId])['c'];
?>

<div class="dashboard-header mb-4">
    <div style="display: flex; justify-content: space-between; align-items: end; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem;">
                <?php echo $lang === 'ar' ? 'مرحباً، ' : 'Welcome back, '; ?> 
                <span style="color: var(--primary-orange);"><?php echo $_SESSION['user_name_' . $lang] ?? $_SESSION['username']; ?></span>
            </h1>
            <p class="text-muted">
                <?php echo $lang === 'ar' ? 'إليك ملخص أعمالك ومهامك لهذا اليوم' : 'Here is your work summary and tasks for today'; ?>
            </p>
        </div>
        <div>
            <button class="btn btn-primary" style="margin-bottom: 15px;" onclick="openProjectSelector('update_status')">
                <i class="fas fa-sliders-h"></i> <?php echo $lang === 'ar' ? 'تحديث حالة' : 'Update Status'; ?>
            </button>
        </div>
    </div>
</div>

<div class="kpi-grid mb-4">
    <div class="kpi-card kpi-blue">
        <div class="kpi-icon-wrapper"><i class="fas fa-briefcase"></i></div>
        <div class="kpi-value"><?php echo $activeProjects; ?></div>
        <div class="kpi-label"><?php echo $lang === 'ar' ? 'مشاريع نشطة' : 'Active Projects'; ?></div>
    </div>
    <div class="kpi-card kpi-red">
        <div class="kpi-icon-wrapper"><i class="fas fa-fire"></i></div>
        <div class="kpi-value"><?php echo $urgentTasksCount; ?></div>
        <div class="kpi-label"><?php echo $lang === 'ar' ? 'مهام عاجلة' : 'Urgent Tasks'; ?></div>
    </div>
    <div class="kpi-card kpi-orange">
        <div class="kpi-icon-wrapper"><i class="fas fa-shield-alt"></i></div>
        <div class="kpi-value"><?php echo $myRisks; ?></div>
        <div class="kpi-label"><?php echo $lang === 'ar' ? 'مخاطر مفتوحة' : 'Open Risks'; ?></div>
    </div>
    <div class="kpi-card kpi-green">
        <div class="kpi-icon-wrapper"><i class="fas fa-tachometer-alt"></i></div>
        <div class="kpi-value">92%</div>
        <div class="kpi-label"><?php echo $lang === 'ar' ? 'معدل الأداء' : 'Performance Score'; ?></div>
    </div>
</div>

<div class="dashboard-grid" style="grid-template-columns: 2fr 1fr; align-items: start;">

    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 class="section-title-decorated">
                <?php echo $lang === 'ar' ? 'مبادراتي ومشاريعي' : 'My Initiatives & Projects'; ?>
            </h3>
            <span class="badge" style="background: #eee; color: #666;"><?php echo $totalMyProjects; ?></span>
        </div>

        <?php if(empty($myInitiatives)): ?>
            <div class="card text-center p-5">
                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                <p class="text-muted"><?php echo $lang === 'ar' ? 'لا توجد مشاريع' : 'No projects assigned.'; ?></p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach($myInitiatives as $init): 
                    $utilization = $init['budget_allocated'] > 0 ? round(($init['budget_spent'] / $init['budget_allocated']) * 100) : 0;
                    $borderColor = ($init['status'] == 'at_risk') ? '#dc3545' : (($init['status'] == 'completed') ? '#28a745' : '#FF8C00');
                ?>
                <div class="project-card">
                    <div class="card-status-border" style="background-color: <?php echo $borderColor; ?>;"></div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                        <div>
                            <div style="font-size: 0.8rem; color: #999; margin-bottom: 5px; display: flex; align-items: center; gap: 5px;">
                                <i class="fas fa-columns"></i> <?php echo $init[$lang === 'ar' ? 'pillar_name_ar' : 'pillar_name_en']; ?>
                            </div>
                            <h3 style="font-size: 1.2rem; margin: 0; color: #2D3748; font-weight: 700;">
                                <?php echo $init[$lang === 'ar' ? 'name_ar' : 'name_en']; ?>
                            </h3>
                        </div>
                        <?php echo getStatusBadge($init['status']); ?>
                    </div>

                    <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem;">
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span class="project-label"><?php echo $lang === 'ar' ? 'الإنجاز' : 'Progress'; ?></span>
                                <strong style="color: var(--primary-orange);"><?php echo $init['progress_percentage']; ?>%</strong>
                            </div>
                            <div style="height: 10px; background: #f5f5f5; border-radius: 5px;">
                                <div style="width: <?php echo $init['progress_percentage']; ?>%; height: 100%; background: linear-gradient(90deg, #FF8C00, #F57C00); border-radius: 5px;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span class="project-label"><?php echo $lang === 'ar' ? 'الميزانية' : 'Budget'; ?></span>
                                <span style="font-size: 0.75rem; color: #666;"><?php echo formatCurrency($init['budget_spent']); ?> / <?php echo formatCurrency($init['budget_allocated']); ?></span>
                            </div>
                            <div style="height: 10px; background: #f5f5f5; border-radius: 5px;">
                                <div style="width: <?php echo $utilization; ?>%; height: 100%; background: linear-gradient(90deg, #28a745, #218838); border-radius: 5px;"></div>
                            </div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid #f0f0f0; padding-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 0.9rem; color: #666;">
                            <i class="far fa-calendar-alt"></i> 
                            <?php echo $lang === 'ar' ? 'الاستحقاق:' : 'Due Date:'; ?> 
                            <strong><?php echo formatDate($init['due_date']); ?></strong>
                        </div>
                        
                        <a href="initiative_detail.php?id=<?php echo $init['id']; ?>" class="btn btn-outline" 
                           style=" padding: 8px 20px;">
                            <?php echo $lang === 'ar' ? 'إدارة المشروع' : 'Manage Project'; ?> 
                            <i class="fas fa-arrow-<?php echo $lang === 'ar' ? 'left' : 'right'; ?>" style="margin-inline-start: 5px;"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="card mb-4" style="border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <h3 class="section-title-decorated mb-3">
                <?php echo $lang === 'ar' ? 'استحقاقات قادمة' : 'Upcoming Deadlines'; ?>
            </h3>
            
            <?php if(empty($upcomingMilestones)): ?>
                <div style="display: flex; align-items: center; gap: 10px; color: #28a745; padding: 1rem; background: #f0fff4; border-radius: 10px;">
                    <i class="fas fa-check-circle"></i> 
                    <span><?php echo $lang === 'ar' ? 'لا توجد استحقاقات قريبة' : 'No upcoming deadlines.'; ?></span>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <?php foreach($upcomingMilestones as $task): ?>
                    <div style="padding: 1rem; background: #fff; border: 1px solid #eee; border-radius: 12px;">
                        <div style="font-weight: 700; font-size: 0.9rem; margin-bottom: 4px; color: #333;"><?php echo $task[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></div>
                        <div style="font-size: 0.8rem; color: #888; margin-bottom: 8px;"><?php echo mb_substr($task[$lang === 'ar' ? 'initiative_name_ar' : 'initiative_name_en'], 0, 30) . '...'; ?></div>
                        <div style="font-size: 0.8rem; font-weight: 600; color: #E67E00;">
                            <i class="far fa-clock"></i> <?php echo formatDate($task['due_date']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <h3 class="section-title-decorated mb-3">
                <?php echo $lang === 'ar' ? 'إجراءات سريعة' : 'Quick Actions'; ?>
            </h3>
            
            <button class="btn-action-light" onclick="openProjectSelector('add_milestone')">
                <i class="fas fa-plus-circle text-primary"></i> 
                <?php echo $lang === 'ar' ? 'إضافة معلم جديد' : 'Add Milestone'; ?>
            </button>
            
            <button class="btn-action-light" onclick="openProjectSelector('upload_file')">
                <i class="fas fa-file-upload text-success"></i> 
                <?php echo $lang === 'ar' ? 'رفع تقرير إنجاز' : 'Upload Report'; ?>
            </button>
            
            <button class="btn-action-light" style="color: #dc3545;" onclick="openProjectSelector('report_risk')">
                <i class="fas fa-exclamation-triangle"></i> 
                <?php echo $lang === 'ar' ? 'الإبلاغ عن خطر' : 'Report Risk'; ?>
            </button>
        </div>
    </div>
</div>

<div id="modal-project-selector" class="custom-modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3><?php echo $lang === 'ar' ? 'اختيار المشروع' : 'Select Project'; ?></h3>
            <button class="close-modal" onclick="closeModal('modal-project-selector')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 15px; color: #666;">
                <?php echo $lang === 'ar' ? 'الرجاء اختيار المشروع الذي تريد تنفيذ الإجراء عليه:' : 'Please select the project you want to update:'; ?>
            </p>
            
            <input type="hidden" id="selectedActionType">
            
            <div class="form-group">
                <select class="form-select" id="projectSelectInput" style="padding: 12px;">
                    <?php foreach($myInitiatives as $init): ?>
                        <option value="<?php echo $init['id']; ?>">
                            <?php echo $init[$lang === 'ar' ? 'name_ar' : 'name_en']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button class="btn btn-primary" style="width: 100%; justify-content: center;" onclick="proceedWithAction()">
                <?php echo $lang === 'ar' ? 'متابعة' : 'Continue'; ?> <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
function openProjectSelector(actionType) {
    document.getElementById('selectedActionType').value = actionType;
    openModal('modal-project-selector');
}

function proceedWithAction() {
    const projectId = document.getElementById('projectSelectInput').value;
    const action = document.getElementById('selectedActionType').value;
    
    if(projectId) {
        // Redirect to details page with a trigger parameter
        window.location.href = `initiative_detail.php?id=${projectId}&trigger=${action}`;
    }
}
</script>