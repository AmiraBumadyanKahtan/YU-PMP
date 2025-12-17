<?php
// 1. Fetch All Pillars Data
$pillars = getAllPillars($db);

// 2. Calculate Global Strategy Stats
$totalInitiatives = $db->fetchOne("SELECT COUNT(*) as c FROM initiatives")['c'];
$totalObjectives = $db->fetchOne("SELECT COUNT(*) as c FROM strategic_objectives")['c'];
$avgProgress = $db->fetchOne("SELECT AVG(progress_percentage) as avg FROM pillars")['avg'];

// 3. Prepare Data for Overview Chart
$pillarNames = [];
$pillarProgress = [];
$pillarBudget = [];
foreach ($pillars as $p) {
    $pillarNames[] = $lang === 'ar' ? $p['name_ar'] : $p['name_en'];
    $pillarProgress[] = $p['progress_percentage'];
    $pillarBudget[] = $p['budget_allocated'];
}
?>

<div class="dashboard-header mb-4">
    <div>
        <h1><?php echo $lang === 'ar' ? 'مكتب إدارة الاستراتيجية' : 'Strategy Management Office'; ?></h1>
        <p class="text-muted"><?php echo $lang === 'ar' ? 'متابعة الأداء الاستراتيجي وتفاصيل الركائز' : 'Strategic performance tracking and pillar details'; ?></p>
    </div>
</div>

<div class="tabs-container">
    
    <div class="tabs-header">
        <button class="tab-btn active" onclick="openTab(event, 'Overview')">
            <i class="fas fa-th-large"></i> 
            <?php echo $lang === 'ar' ? 'نظرة عامة' : 'Overview'; ?>
        </button>
        
        <?php foreach($pillars as $p): ?>
        <button class="tab-btn" onclick="openTab(event, 'Pillar_<?php echo $p['id']; ?>')">
            <i class="fas <?php echo $p['icon'] ?? 'fa-building'; ?>"></i>
            <?php echo $lang === 'ar' ? 'الركيزة ' . $p['pillar_number'] : 'Pillar ' . $p['pillar_number']; ?>
        </button>
        <?php endforeach; ?>
    </div>

    <div id="Overview" class="tab-content active">
        
        <div class="dashboard-grid mb-4">
            
            <div class="stats-card-modern" style="background: linear-gradient(135deg, #FF8C00 0%, #E67E00 100%); color: white;">
                <div>
                    <h3 style="color: rgba(255,255,255,0.9);"><?php echo $lang === 'ar' ? 'عدد الركائز' : 'Total Pillars'; ?></h3>
                    <div class="value"><?php echo count($pillars); ?></div>
                </div>
                <i class="fas fa-columns icon-bg" style="color: white; opacity: 0.2;"></i>
            </div>
            
            <div class="stats-card-modern" style="background: linear-gradient(135deg, #2D3748 0%, #1A202C 100%); color: white;">
                <div>
                    <h3 style="color: rgba(255,255,255,0.9);"><?php echo $lang === 'ar' ? 'الأهداف الاستراتيجية' : 'Strategic Objectives'; ?></h3>
                    <div class="value"><?php echo $totalObjectives; ?></div>
                </div>
                <i class="fas fa-bullseye icon-bg" style="color: white; opacity: 0.1;"></i>
            </div>

            <div class="stats-card-modern" style="background: linear-gradient(135deg, #D69E2E 0%, #B7791F 100%); color: white;">
                <div>
                    <h3 style="color: rgba(255,255,255,0.95);"><?php echo $lang === 'ar' ? 'المبادرات الكلية' : 'Total Initiatives'; ?></h3>
                    <div class="value"><?php echo $totalInitiatives; ?></div>
                </div>
                <i class="fas fa-tasks icon-bg" style="color: white; opacity: 0.2;"></i>
            </div>

            <div class="stats-card-modern" style="background: white; color: #333; border-top: 4px solid #FF8C00; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                <div>
                    <h3 style="color: #666;"><?php echo $lang === 'ar' ? 'متوسط الإنجاز' : 'Avg Progress'; ?></h3>
                    <div class="value" style="color: #FF8C00;"><?php echo round($avgProgress); ?>%</div>
                </div>
                <i class="fas fa-chart-line icon-bg" style="color: #FF8C00; opacity: 0.1;"></i>
            </div>
        </div>

        <div class="card">
            <h3><?php echo $lang === 'ar' ? 'مقارنة أداء الركائز' : 'Pillars Performance Comparison'; ?></h3>
            <div style="height: 350px;">
                <canvas id="strategyOverviewChart"></canvas>
            </div>
        </div>
    </div>

    <?php foreach($pillars as $p): 
        // Get specific data for this pillar
        $initiatives = getPillarInitiatives($p['id'], $db);
        $objectives = getStrategicObjectives($p['id'], $db);
        
        // Calculations
        $pTotal = count($initiatives);
        $pCompleted = 0;
        foreach($initiatives as $i) if($i['status'] === 'completed') $pCompleted++;
    ?>
    <div id="Pillar_<?php echo $p['id']; ?>" class="tab-content">
        
        <div class="card mb-4" >
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
                <div>
                    <h2 style="color: var(--primary-orange); margin-bottom: 0.5rem;">
                        <?php echo $p[$lang === 'ar' ? 'name_ar' : 'name_en']; ?>
                    </h2>
                    <p style="color: #666; max-width: 800px;">
                        <?php echo $p[$lang === 'ar' ? 'description_ar' : 'description_en']; ?>
                    </p>
                    
                    <div style="margin-top: 1rem; display: flex; gap: 1.5rem; font-size: 0.9rem;">
                        <span style="background: #f0f0f0; padding: 5px 10px; border-radius: 5px;">
                            <i class="fas fa-user-tie"></i> 
                            <?php echo $lang === 'ar' ? 'القائد:' : 'Lead:'; ?> 
                            <strong><?php echo $p[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></strong>
                        </span>
                        <span style="background: #f0f0f0; padding: 5px 10px; border-radius: 5px;">
                            <i class="fas fa-wallet"></i>
                            <?php echo $lang === 'ar' ? 'الميزانية:' : 'Budget:'; ?>
                            <strong><?php echo formatCurrency($p['budget_allocated']); ?></strong>
                        </span>
                        <span style="background: #fff3cd; color: #856404; padding: 5px 10px; border-radius: 5px;">
                            <i class="fas fa-flag"></i>
                            <?php echo $lang === 'ar' ? 'الحالة:' : 'Status:'; ?>
                            <strong><?php echo $p['status']; ?></strong>
                        </span>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <div style="position: relative; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: 8px solid #f0f0f0;">
                        <div style="font-weight: 800; font-size: 1.5rem; color: var(--primary-orange);">
                            <?php echo $p['progress_percentage']; ?>%
                        </div>
                        <svg style="position: absolute; top: -8px; left: -8px; width: 100px; height: 100px; transform: rotate(-90deg);">
                             <circle cx="50" cy="50" r="46" stroke="var(--primary-orange)" stroke-width="8" fill="none" 
                                     stroke-dasharray="289" 
                                     stroke-dashoffset="<?php echo 289 * (1 - $p['progress_percentage']/100); ?>"></circle>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <h3><i class="fas fa-bullseye" style="color: var(--primary-orange);"></i> <?php echo $lang === 'ar' ? 'الأهداف الاستراتيجية' : 'Strategic Objectives'; ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                <?php foreach($objectives as $obj): ?>
                <div style="padding: 1rem; background: #fcfcfc; border: 1px solid #eee; border-radius: 8px; display: flex; gap: 10px;">
                    <div style="background: var(--primary-orange); color: white; width: 25px; height: 25px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; flex-shrink: 0;">
                        <?php echo $obj['objective_number']; ?>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;"><?php echo $obj[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></div>
                        <div style="font-size: 0.85rem; color: #777; margin-top: 3px;"><?php echo $obj[$lang === 'ar' ? 'description_ar' : 'description_en']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3><i class="fas fa-tasks" style="color: var(--primary-orange);"></i> <?php echo $lang === 'ar' ? 'المبادرات والمشاريع' : 'Initiatives & Projects'; ?></h3>
                <span class="badge" style="background: #eee; color: #333;"><?php echo $pCompleted; ?> / <?php echo $pTotal; ?> <?php echo $lang === 'ar' ? 'مكتمل' : 'Done'; ?></span>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="strategy-table">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%"><?php echo $lang === 'ar' ? 'اسم المبادرة' : 'Initiative Name'; ?></th>
                            <th width="15%"><?php echo $lang === 'ar' ? 'المالك' : 'Owner'; ?></th>
                            <th width="15%"><?php echo $lang === 'ar' ? 'الميزانية (مصروف/مخصص)' : 'Budget (Spent/Alloc)'; ?></th>
                            <th width="10%"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                            <th width="20%"><?php echo $lang === 'ar' ? 'التقدم' : 'Progress'; ?></th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($initiatives)): ?>
                            <tr><td colspan="7" style="text-align: center; padding: 2rem; color: #999;"><?php echo $lang === 'ar' ? 'لا توجد مبادرات' : 'No initiatives found'; ?></td></tr>
                        <?php else: ?>
                            <?php foreach($initiatives as $init): ?>
                            <tr>
                                <td style="font-weight: bold; color: #888;"><?php echo $init['initiative_number']; ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #333;"><?php echo $init[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></div>
                                    <div style="font-size: 0.8rem; color: #999;">
                                        <i class="fas fa-bullseye"></i> <?php echo $init[$lang === 'ar' ? 'objective_name_ar' : 'objective_name_en']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($init['full_name_en']); ?>&size=24&rounded=true&background=random">
                                        <span style="font-size: 0.9rem;"><?php echo $init[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></span>
                                    </div>
                                </td>
                                <td style="font-size: 0.9rem;">
                                    <div style="color: var(--primary-orange); font-weight: 600;"><?php echo formatCurrency($init['budget_spent']); ?></div>
                                    <div style="color: #999; font-size: 0.8rem;"><?php echo formatCurrency($init['budget_allocated']); ?></div>
                                </td>
                                <td><?php echo getStatusBadge($init['status']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="flex-grow: 1; height: 8px; background: #eee; border-radius: 4px;">
                                            <div style="width: <?php echo $init['progress_percentage']; ?>%; height: 100%; background: linear-gradient(90deg, #FF8C00, #FFB347); border-radius: 4px;"></div>
                                        </div>
                                        <span style="font-size: 0.85rem; font-weight: bold;"><?php echo $init['progress_percentage']; ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <a href="initiative_detail.php?id=<?php echo $init['id']; ?>" class="btn-icon" title="View Details">
                                        <i class="fas fa-arrow-right" style="color: var(--primary-orange);"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
    const ctxStrategy = document.getElementById('strategyOverviewChart').getContext('2d');
    new Chart(ctxStrategy, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($pillarNames); ?>,
            datasets: [
                {
                    label: '<?php echo $lang === 'ar' ? 'نسبة الإنجاز %' : 'Progress %'; ?>',
                    data: <?php echo json_encode($pillarProgress); ?>,
                    backgroundColor: 'rgba(255, 140, 0, 0.7)',
                    borderColor: 'rgba(255, 140, 0, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: '<?php echo $lang === 'ar' ? 'الميزانية المخصصة (مليون)' : 'Budget (Millions)'; ?>',
                    data: <?php echo json_encode(array_map(function($v){ return $v/1000000; }, $pillarBudget)); ?>,
                    type: 'line',
                    borderColor: '#2D3748',
                    borderWidth: 2,
                    pointBackgroundColor: '#fff',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: { display: true, text: 'Progress %' }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Budget (M)' }
                }
            }
        }
    });
</script>