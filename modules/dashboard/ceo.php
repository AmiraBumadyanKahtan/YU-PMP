<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'includes/components/stats_card.php';

// Initialize database
$db = new Database();
$lang = getCurrentLang();

// Check login
if (!isset($_SESSION['user_id'])) redirect('login.php');

// ---------------------------------------------------
// 1. FETCH KEY PERFORMANCE INDICATORS (KPIs)
// ---------------------------------------------------

// أ. إجمالي المشاريع والمبادرات
$countInitiatives = $db->fetchOne("SELECT COUNT(*) as c FROM initiatives")['c'];
$countProjects = $db->fetchOne("SELECT COUNT(*) as c FROM projects")['c'];
$totalWork = $countInitiatives + $countProjects;

// ب. المشاريع النشطة (On Track + In Progress)
$activeInit = $db->fetchOne("SELECT COUNT(*) as c FROM initiatives WHERE status IN ('in_progress', 'on_track')")['c'];
$activeProj = $db->fetchOne("SELECT COUNT(*) as c FROM projects WHERE status IN ('in_progress')")['c'];
$totalActive = $activeInit + $activeProj;

// ج. المشاريع المتعثرة/في خطر
$riskInit = $db->fetchOne("SELECT COUNT(*) as c FROM initiatives WHERE status IN ('at_risk', 'needs_work')")['c'];
// (نفترض أن المشاريع التشغيلية ليس لديها حالة at_risk حالياً، أو يمكن إضافتها لاحقاً)
$totalRisk = $riskInit;

// د. المكتملة
$completedInit = $db->fetchOne("SELECT COUNT(*) as c FROM initiatives WHERE status = 'completed'")['c'];
$completedProj = $db->fetchOne("SELECT COUNT(*) as c FROM projects WHERE status = 'completed'")['c'];
$totalCompleted = $completedInit + $completedProj;

// هـ. إحصائيات الموظفين والأقسام
$totalEmployees = $db->fetchOne("SELECT COUNT(*) as c FROM users")['c'];
$totalDepartments = $db->fetchOne("SELECT COUNT(*) as c FROM departments")['c'];

// ---------------------------------------------------
// 2. BUDGET CALCULATIONS (Strategic Pillars Budget)
// ---------------------------------------------------

// نعتمد ميزانية الركائز كميزانية عامة للاستراتيجية
$budgetQuery = $db->fetchOne("SELECT SUM(budget_allocated) as allocated, SUM(budget_spent) as spent FROM pillars");
$totalAllocated = $budgetQuery['allocated'] ?? 0;
$totalSpent = $budgetQuery['spent'] ?? 0;
$totalRemaining = $totalAllocated - $totalSpent;

// حساب كفاءة الصرف (تجنب القسمة على صفر)
$costEfficiency = ($totalAllocated > 0) ? round((($totalAllocated - $totalSpent) / $totalAllocated) * 100) : 0;

// ---------------------------------------------------
// 3. CHARTS DATA PREPARATION
// ---------------------------------------------------

// Chart 1: Budget by Pillar
$pillarsData = $db->fetchAll("SELECT name_en, name_ar, budget_allocated, budget_spent FROM pillars");
$pillarLabels = [];
$pillarAllocatedData = [];
$pillarSpentData = [];

foreach ($pillarsData as $p) {
    $pillarLabels[] = $lang === 'ar' ? $p['name_ar'] : $p['name_en'];
    $pillarAllocatedData[] = $p['budget_allocated'];
    $pillarSpentData[] = $p['budget_spent'];
}

// Chart 2: Initiative Status Distribution
$statusData = $db->fetchAll("SELECT status, COUNT(*) as count FROM initiatives GROUP BY status");
$statusLabels = [];
$statusCounts = [];
$statusColors = [];

foreach ($statusData as $s) {
    // تحويل الحالة لنص مقروء
    $label = ucfirst(str_replace('_', ' ', $s['status']));
    if ($lang === 'ar') {
        // ترجمة بسيطة للحالات
        $trans = [
            'in_progress' => 'قيد التنفيذ', 'on_track' => 'على المسار', 
            'at_risk' => 'في خطر', 'completed' => 'مكتمل', 'not_started' => 'لم يبدأ'
        ];
        $label = $trans[$s['status']] ?? $label;
    }
    $statusLabels[] = $label;
    $statusCounts[] = $s['count'];
    
    // تعيين ألوان
    switch($s['status']) {
        case 'completed': $statusColors[] = '#27ae60'; break;
        case 'at_risk': $statusColors[] = '#e74c3c'; break;
        case 'on_track': $statusColors[] = '#2ecc71'; break;
        case 'in_progress': $statusColors[] = '#3498db'; break;
        default: $statusColors[] = '#95a5a6';
    }
}

// ---------------------------------------------------
// 4. RECENT ACTIVITY
// ---------------------------------------------------
$activities = $db->fetchAll("
    SELECT al.*, u.full_name_en, u.full_name_ar 
    FROM activity_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 6
");

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'لوحة الرئيس التنفيذي' : 'CEO Dashboard'; ?></title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body <?php echo $lang === 'ar' ? 'class="rtl"' : ''; ?>>
    
    <?php include 'includes/layout_header.php'; ?>
    <?php include 'includes/layout_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="content-wrapper">
            
            <div style="margin-bottom: 2rem;">
                <h1><?php echo $lang === 'ar' ? 'لوحة الرئيس التنفيذي' : 'CEO Dashboard'; ?></h1>
                <p style="color: #666; font-size: 1.05rem;">
                    <?php echo $lang === 'ar' ? 'نظرة شاملة على الأداء والإحصائيات الاستراتيجية' : 'Comprehensive view of strategic performance and statistics'; ?>
                </p>
            </div>

            <?php
            $topCards = [
                [
                    'title' => $lang === 'ar' ? 'إجمالي المشاريع والمبادرات' : 'Total Initiatives & Projects',
                    'number' => $totalWork,
                    'icon' => 'fa-layer-group',
                    'color' => 'blue',
                    'footer' => $lang === 'ar' ? 'مجموع الأعمال القائمة' : 'Total active portfolio',
                    'style' => 'colored'
                ],
                [
                    'title' => $lang === 'ar' ? 'نشطة حالياً' : 'Active Now',
                    'number' => $totalActive,
                    'icon' => 'fa-sync-alt',
                    'color' => 'green',
                    'footer' => $lang === 'ar' ? 'قيد التنفيذ أو على المسار' : 'In Progress or On Track',
                    'style' => 'colored'
                ],
                [
                    'title' => $lang === 'ar' ? 'معرض للخطر' : 'At Risk',
                    'number' => $totalRisk,
                    'icon' => 'fa-exclamation-triangle',
                    'color' => 'orange',
                    'footer' => $lang === 'ar' ? 'تحتاج انتباه فوري' : 'Needs immediate attention',
                    'style' => 'colored'
                ],
                [
                    'title' => $lang === 'ar' ? 'مكتملة' : 'Completed',
                    'number' => $totalCompleted,
                    'icon' => 'fa-flag-checkered',
                    'color' => 'teal',
                    'footer' => $lang === 'ar' ? 'تم إنجازها بنجاح' : 'Successfully finished',
                    'style' => 'colored'
                ]
            ];
            renderStatsGrid($topCards, 4);
            ?>

            <div style="margin-top: 3rem;">
                <h2><?php echo $lang === 'ar' ? 'نظرة عامة على الميزانية' : 'Budget Overview'; ?></h2>
                
                <?php
                $budgetCards = [
                    [
                        'title' => $lang === 'ar' ? 'الميزانية المخصصة' : 'Allocated Budget',
                        'number' => formatCurrency($totalAllocated),
                        'icon' => 'fa-wallet',
                        'color' => 'orange',
                        'footer' => $lang === 'ar' ? 'إجمالي ميزانية الركائز' : 'Total Pillars Budget',
                        'style' => 'white'
                    ],
                    [
                        'title' => $lang === 'ar' ? 'المصروف' : 'Spent',
                        'number' => formatCurrency($totalSpent),
                        'icon' => 'fa-money-bill-wave',
                        'color' => 'red',
                        'footer' => $lang === 'ar' ? 'إجمالي الصرف الفعلي' : 'Total Actual Spent',
                        'style' => 'white'
                    ],
                    [
                        'title' => $lang === 'ar' ? 'المتبقي' : 'Remaining',
                        'number' => formatCurrency($totalRemaining),
                        'icon' => 'fa-piggy-bank',
                        'color' => 'green',
                        'footer' => $lang === 'ar' ? 'متاح للصرف' : 'Available to spend',
                        'style' => 'white'
                    ],
                    [
                        'title' => $lang === 'ar' ? 'كفاءة الميزانية' : 'Budget Efficiency',
                        'number' => $costEfficiency . '%',
                        'icon' => 'fa-chart-pie',
                        'color' => 'blue',
                        'footer' => $lang === 'ar' ? 'نسبة الوفرة' : 'Saving Rate',
                        'style' => 'white'
                    ]
                ];
                renderStatsGrid($budgetCards, 4);
                ?>
            </div>

            <div style="margin-top: 3rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                
                <div class="card">
                    <h3><?php echo $lang === 'ar' ? 'الميزانية حسب الركيزة' : 'Budget by Pillar'; ?></h3>
                    <canvas id="budgetChart" style="max-height: 300px;"></canvas>
                </div>

                <div class="card">
                    <h3><?php echo $lang === 'ar' ? 'حالة المبادرات' : 'Initiative Status'; ?></h3>
                    <div style="height: 250px; display: flex; justify-content: center;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

            </div>

            <div style="margin-top: 3rem;">
                <div class="card">
                    <h3><?php echo $lang === 'ar' ? 'النشاط الأخير' : 'Recent Activity'; ?></h3>
                    
                    <div style="margin-top: 1.5rem;">
                        <?php if(empty($activities)): ?>
                            <p class="text-muted text-center p-3"><?php echo $lang === 'ar' ? 'لا توجد نشاطات حديثة.' : 'No recent activities.'; ?></p>
                        <?php else: ?>
                            <?php foreach ($activities as $activity): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid #f0f0f0; display: flex; gap: 1rem; align-items: center;">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($activity[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']); ?>&background=random&color=fff&size=40" 
                                     style="width: 40px; height: 40px; border-radius: 50%;" alt="">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #333;">
                                        <?php echo $activity[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?>
                                    </div>
                                    <div style="color: #666; font-size: 0.9rem; margin: 0.2rem 0;">
                                        <?php echo $activity[$lang === 'ar' ? 'activity_description_ar' : 'activity_description_en']; ?>
                                    </div>
                                    <div style="color: #999; font-size: 0.8rem;">
                                        <?php echo formatDate($activity['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="assets/js/main.js"></script>
    
    <script>
        // 1. Budget Bar Chart
        const budgetCtx = document.getElementById('budgetChart').getContext('2d');
        new Chart(budgetCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($pillarLabels); ?>,
                datasets: [{
                    label: '<?php echo $lang === 'ar' ? 'المخصص' : 'Allocated'; ?>',
                    data: <?php echo json_encode($pillarAllocatedData); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.8)',
                    borderRadius: 4
                }, {
                    label: '<?php echo $lang === 'ar' ? 'المصروف' : 'Spent'; ?>',
                    data: <?php echo json_encode($pillarSpentData); ?>,
                    backgroundColor: 'rgba(231, 76, 60, 0.8)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f0f0f0' } }, 
                    x: { grid: { display: false } } 
                }
            }
        });

        // 2. Status Pie Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($statusLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($statusCounts); ?>,
                    backgroundColor: <?php echo json_encode($statusColors); ?>,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12 } }
                }
            }
        });
    </script>
</body>
</html>