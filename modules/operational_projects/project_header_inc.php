<?php
// modules/operational_projects/project_header_inc.php
if (!isset($project) || empty($project)) return;

// --- 1. المنطق البرمجي ---
$db = Database::getInstance()->pdo();

// أرقام المهام
$h_tasksTotal = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND is_deleted=0")->fetchColumn();
$h_tasksDone = $db->query("SELECT COUNT(*) FROM project_tasks WHERE project_id=$id AND status_id=3 AND is_deleted=0")->fetchColumn();

// المخاطر
$h_risksCount = $db->query("SELECT COUNT(*) FROM risk_assessments WHERE parent_type='project' AND parent_id=$id AND status_id != 4")->fetchColumn();

// الوقت المتبقي
$h_daysLeft = 0;
if ($project['end_date']) {
    $h_end = new DateTime($project['end_date']);
    $h_now = new DateTime();
    if ($h_end > $h_now) { $h_daysLeft = $h_now->diff($h_end)->days; }
}

// الميزانية
$h_spentVal = $project['spent_budget'] ?? 0;
$h_budgetVal = $project['approved_budget'] ?? $project['budget_max'];
$h_moneyPercent = ($h_budgetVal > 0) ? round(($h_spentVal / $h_budgetVal) * 100) : 0;
$h_isOverBudget = ($h_spentVal > $h_budgetVal);

// نسبة الإنجاز والحالة
$h_progPercent = $project['progress_percentage'] ?? 0;
$h_statusName = $project['status_name'] ?? 'Unknown';
$h_statusColor = $project['status_color'] ?? '#95a5a6';

// --- [تعديل جديد]: التحقق هل المشروع قابل للتعديل؟ ---
// نستخدم الدالة التي أضفناها أو نكتب الشرط مباشرة هنا
$lockedStatuses = [2, 4, 8, 7]; // 2=Pending, 4=Rejected (أضف 8 للـ Completed إذا رغبت)
$isProjectEditable = !in_array($project['status_id'], $lockedStatuses);

// --- 2. التحقق من الصلاحيات ---
$canManagePerms = userCanInProject($id, 'manage_project_permissions');
$canViewCollab = userCanInProject($id, 'view_project_collaborations');
$canViewUpdates = userCanInProject($id, 'view_project_updates');
$canViewBudget = userCanInProject($id, 'view_project_budget');
?>

<style>
    .project-header-card { background: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 25px; border-top: 6px solid #ff8c00; overflow: hidden; }
    .ph-top-section { padding: 30px; display: flex; justify-content: space-between; border-bottom: 1px solid #f0f0f0; }
    
    .ph-badges-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .ph-code-badge { background-color: #fff3e0; color: #d35400; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
    .ph-status-badge { color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
    
    /* بادج القفل */
    .ph-locked-badge { background-color: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; box-shadow: 0 2px 5px rgba(231, 76, 60, 0.3); }

    .ph-title { margin: 0 0 10px 0; font-size: 2.2rem; color: #2c3e50; font-weight: 700; }
    .ph-meta-row { display: flex; gap: 25px; color: #7f8c8d; font-size: 0.95rem; align-items: center; }
    
    .ph-stats-bar { display: flex; padding: 20px 30px; background-color: #fdfdfd; gap: 40px; align-items: center; justify-content: space-between; }
    .stat-item { display: flex; align-items: center; gap: 15px; flex: 1; }
    .stat-icon-box { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; box-shadow: 0 2px 5px rgba(0,0,0,0.05); flex-shrink: 0; }
    .icon-orange { background: #fff3e0; color: #ff8c00; } .icon-blue { background: #e3f2fd; color: #3498db; } .icon-purple { background: #f3e5f5; color: #9b59b6; } .icon-red { background: #ffebee; color: #e74c3c; }
    
    .stat-info { display: flex; flex-direction: column; justify-content: center; }
    .stat-label { font-size: 13.5px; color: #888; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .stat-value { font-size: 13px; font-weight: 800; color: #2c3e50; }
    .stat-sub { font-size: 0.75rem; color: #aaa; font-weight: normal; margin-left: 3px; }
    
    .stat-donut { position: relative; width: 50px; height: 50px; border-radius: 50%; background: conic-gradient(#27ae60 <?= $h_progPercent ?>%, #ecf0f1 0); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-donut::after { content: ""; position: absolute; width: 40px; height: 40px; border-radius: 50%; background: #fff; }
    .donut-text { position: absolute; z-index: 1; font-size: 0.8rem; font-weight: bold; color: #27ae60; }
    
    .budget-progress { width: 100px; height: 4px; background: #eee; border-radius: 2px; margin-top: 5px; overflow: hidden; }
    .budget-bar { height: 100%; background: #ff8c00; width: <?= min($h_moneyPercent, 100) ?>%; }
    .over-budget .budget-bar { background: #e74c3c; } .over-text { color: #e74c3c; }

    .tabs-container { margin-bottom: 25px; border-bottom: 2px solid #eee; display: flex; gap: 5px; overflow-x: auto; }
    .tab-link { padding: 12px 25px; text-decoration: none; color: #666; font-weight: 600; border-bottom: 3px solid transparent; transition: 0.2s; white-space: nowrap; }
    .tab-link:hover { background: #f9f9f9; color: #ff8c00; }
    .tab-link.active { color: #ff8c00; border-bottom-color: #ff8c00; background: #fff; }
    .tab-link.locked { color: #ccc; cursor: not-allowed; display: flex; align-items: center; gap: 5px; }
    .tab-link.perm-tab { color: #e74c3c; } 
    .tab-link.perm-tab:hover, .tab-link.perm-tab.active { border-bottom-color: #e74c3c; }
</style>

<div class="project-header-card">
    <div class="ph-top-section">
        <div class="ph-left">
            <div class="ph-badges-row">
                <span class="ph-code-badge"><?= $project['project_code'] ?></span>
                <span class="ph-status-badge" style="background-color: <?= $h_statusColor ?>;">
                    <?= htmlspecialchars($h_statusName) ?>
                </span>
                
                <?php if (!$isProjectEditable): ?>
                    <span class="ph-locked-badge" title="Project is locked for editing">
                        <i class="fa-solid fa-lock"></i> Read Only
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="ph-title"><?= htmlspecialchars($project['name']) ?></h1>
            <div class="ph-meta-row">
                <div class="ph-meta-item"><i class="fa-solid fa-user"></i> <span><?= htmlspecialchars($project['manager_name'] ?? 'Not Assigned') ?></span></div>
                <div class="ph-meta-item"><i class="fa-solid fa-building"></i> <span><?= htmlspecialchars($project['department_name']) ?></span></div>
                <div class="ph-meta-item"><i class="fa-regular fa-calendar"></i> <span><?= $project['start_date'] ?> / <?= $project['end_date'] ?></span></div>
            </div>
        </div>
    </div>

    <div class="ph-stats-bar">
        <div class="stat-item">
            <div class="stat-donut"><span class="donut-text"><?= $h_progPercent ?>%</span></div>
            <div class="stat-info">
                <div class="stat-label">Overall Progress</div>
                <div class="stat-value" style="font-size: 13px; color: <?= $h_statusColor ?>">
                    <?= htmlspecialchars($h_statusName) ?>
                </div>
            </div>
        </div>

        <div class="stat-item">
            <div class="stat-icon-box icon-orange"><i class="fa-solid fa-wallet"></i></div>
            <div class="stat-info">
                <div class="stat-label">Budget Usage</div>
                <?php if ($canViewBudget): ?>
                    <div class="stat-value <?= $h_isOverBudget ? 'over-text' : '' ?>">
                        <?= number_format($h_spentVal) ?> <span class="stat-sub">/ <?= number_format($h_budgetVal) ?></span>
                    </div>
                    <div class="budget-progress <?= $h_isOverBudget ? 'over-budget' : '' ?>"><div class="budget-bar"></div></div>
                <?php else: ?>
                    <div class="stat-value" style="font-size:13px; color:#999;"><i>Hidden</i></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-item">
            <div class="stat-icon-box icon-blue"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-info"><div class="stat-label">Time Left</div><div class="stat-value"><?= $h_daysLeft ?> Days</div></div>
        </div>
        <div class="stat-item">
            <div class="stat-icon-box icon-purple"><i class="fa-solid fa-list-check"></i></div>
            <div class="stat-info"><div class="stat-label">Tasks Done</div><div class="stat-value"><?= $h_tasksDone ?> / <?= $h_tasksTotal ?></div></div>
        </div>
        <div class="stat-item">
            <div class="stat-icon-box icon-red"><i class="fa-solid fa-shield-halved"></i></div>
            <div class="stat-info"><div class="stat-label">Active Risks</div><div class="stat-value"><?= $h_risksCount ?></div></div>
        </div>
    </div>
</div>

<div class="tabs-container">
    <a href="view.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'view.php' ? 'active' : '' ?>">Overview</a>
    
    <?php 
    // السماح بالوصول للتابات (عرضها) حتى لو كانت للقراءة فقط، طالما المشروع موجود
    // لكن نخفيها إذا كان "مسودة" (Draft) للمستخدمين غير المصرح لهم
    // أو إذا كان "مرفوض" (Rejected) قد نفضل فقط عرض الـ Overview لفهم السبب
    
    // هنا سنسمح بعرض التابات دائماً (لأننا سنحمي الأزرار داخل كل صفحة)، إلا في حالة Draft لغير المدير
    $canSeeTabs = true; 
    if ($project['status_id'] == 1 && !$canManagePerms) $canSeeTabs = false; 

    if ($canSeeTabs): 
    ?>
        <a href="team.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'team.php' ? 'active' : '' ?>">Team</a>
        
        <?php if ($canViewCollab): ?>
            <a href="collaborations.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'collaborations.php' ? 'active' : '' ?>">Collaborations</a>
        <?php endif; ?>

        <a href="milestones.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'milestones.php' ? 'active' : '' ?>">Milestones</a>
        <a href="kpis.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'kpis.php' ? 'active' : '' ?>">KPIs</a>
        <a href="risks.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'risks.php' ? 'active' : '' ?>">Risks</a>
        <a href="docs.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'docs.php' ? 'active' : '' ?>">Documents</a>
        <a href="resources.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'resources.php' ? 'active' : '' ?>">Resources</a>

        <?php if ($canViewUpdates): ?>
            <a href="updates_reminder.php?id=<?= $id ?>" class="tab-link <?= basename($_SERVER['PHP_SELF']) == 'updates_reminder.php' ? 'active' : '' ?>">Updates</a>
        <?php endif; ?>

        <?php if ($canManagePerms): ?>
            <a href="permissions.php?id=<?= $id ?>" class="tab-link perm-tab <?= basename($_SERVER['PHP_SELF']) == 'permissions.php' ? 'active' : '' ?>"><i class="fa-solid fa-lock"></i> Permissions</a>
        <?php endif; ?>

    <?php else: ?>
        <span class="tab-link locked">Team</span>
        <span class="tab-link locked">Collaborations</span>
        <span class="tab-link locked">Milestones</span>
        <span class="tab-link locked">KPIs</span>
        <span class="tab-link locked">Risks</span>
        <span class="tab-link locked">Documents</span>
        <span class="tab-link locked">Resources</span>
    <?php endif; ?>
</div>