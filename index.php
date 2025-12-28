<?php
// index.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/auth.php';

if (!Auth::check()) { header("Location: login.php"); exit; }

$db = Database::getInstance()->pdo();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? $_SESSION['username'];

// ==========================================================
// 1. الإحصائيات العامة (نفس السابق)
// ==========================================================
$myProjectsCount = $db->query("SELECT COUNT(DISTINCT project_id) FROM project_team WHERE user_id = $userId AND is_active = 1")->fetchColumn();
$myOpenTasks = $db->query("SELECT COUNT(*) FROM project_tasks WHERE assigned_to = $userId AND status_id != 3 AND is_deleted = 0")->fetchColumn();

// نسبة الإنجاز
$stats = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as in_progress FROM project_tasks WHERE assigned_to = $userId AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
$completionRate = ($stats['total'] > 0) ? round(($stats['completed'] / $stats['total']) * 100) : 0;

// ==========================================================
// 2. منطق التقويم الشامل (The Mega Calendar Query)
// ==========================================================
$currentMonth = date('m');
$currentYear = date('Y');
$daysInMonth = date('t');

$calendarEvents = [];

// دالة مساعدة لإضافة الأحداث للمصفوفة
function addEvents($dbResult, $type, $color, $icon, &$events) {
    foreach($dbResult as $row) {
        $day = (int)date('j', strtotime($row['event_date']));
        $events[$day][] = [
            'type' => $type,
            'title' => $row['title'],
            'desc' => $row['subtitle'] ?? '',
            'id' => $row['id'],
            'color' => $color,
            'icon' => $icon,
            'url' => $row['url']
        ];
    }
}

// 1. المهام (Tasks) - اللون الأزرق
$tasks = $db->query("
    SELECT t.id, t.title, p.name as subtitle, t.due_date as event_date, CONCAT('modules/operational_projects/view.php?id=', t.project_id) as url
    FROM project_tasks t
    JOIN operational_projects p ON p.id = t.project_id
    WHERE t.assigned_to = $userId AND t.is_deleted = 0 AND t.status_id != 3
    AND MONTH(t.due_date) = '$currentMonth' AND YEAR(t.due_date) = '$currentYear'
")->fetchAll(PDO::FETCH_ASSOC);
addEvents($tasks, 'Task', '#3498db', 'fa-list-check', $calendarEvents);

// 2. المشاريع (Projects End Date) - للمدراء - اللون الأحمر
$projects = $db->query("
    SELECT id, name as title, project_code as subtitle, end_date as event_date, CONCAT('modules/operational_projects/view.php?id=', id) as url
    FROM operational_projects 
    WHERE manager_id = $userId AND is_deleted = 0 AND status_id NOT IN (8, 4)
    AND MONTH(end_date) = '$currentMonth' AND YEAR(end_date) = '$currentYear'
")->fetchAll(PDO::FETCH_ASSOC);
addEvents($projects, 'Project Deadline', '#e74c3c', 'fa-rocket', $calendarEvents);

// 3. المراحل (Milestones) - للمدراء - اللون الذهبي
$milestones = $db->query("
    SELECT m.id, m.name as title, p.name as subtitle, m.due_date as event_date, CONCAT('modules/operational_projects/milestones.php?id=', m.project_id) as url
    FROM project_milestones m
    JOIN operational_projects p ON p.id = m.project_id
    WHERE p.manager_id = $userId AND m.is_deleted = 0 AND m.status_id != 3
    AND MONTH(m.due_date) = '$currentMonth' AND YEAR(m.due_date) = '$currentYear'
")->fetchAll(PDO::FETCH_ASSOC);
addEvents($milestones, 'Milestone', '#f1c40f', 'fa-flag', $calendarEvents);

// 4. المبادرات (Initiatives) - للملاك - اللون البنفسجي
$initiatives = $db->query("
    SELECT id, name as title, initiative_code as subtitle, due_date as event_date, 'modules/initiatives/list.php' as url
    FROM initiatives 
    WHERE owner_user_id = $userId AND is_deleted = 0
    AND MONTH(due_date) = '$currentMonth' AND YEAR(due_date) = '$currentYear'
")->fetchAll(PDO::FETCH_ASSOC);
addEvents($initiatives, 'Initiative', '#9b59b6', 'fa-chess', $calendarEvents);

// 5. تحديثات المشروع المطلوبة (Updates) - اللون البرتقالي
// نجلبها من جدول التذكيرات
$updates = $db->query("
    SELECT r.id, p.name as title, 'Submit Progress Update' as subtitle, r.next_reminder_date as event_date, CONCAT('modules/operational_projects/updates_reminder.php?id=', r.project_id) as url
    FROM project_update_reminders r
    JOIN operational_projects p ON p.id = r.project_id
    WHERE r.manager_id = $userId AND r.is_active = 1
    AND MONTH(r.next_reminder_date) = '$currentMonth' AND YEAR(r.next_reminder_date) = '$currentYear'
")->fetchAll(PDO::FETCH_ASSOC);
addEvents($updates, 'Update Required', '#e67e22', 'fa-pen-to-square', $calendarEvents);

// 6. المخاطر (Risks) - للمدراء - اللون الأسود/الرمادي
// (المخاطر عادة ليس لها تاريخ استحقاق محدد كـ Deadline، لكن سنستخدم تاريخ التسجيل أو تاريخ المراجعة إذا وجد، هنا سنفترض تاريخ التعريف كمثال أو نكتفي بما سبق)

// ==========================================================
// 3. المشاريع النشطة (للقائمة الجانبية)
// ==========================================================
$myProjects = $db->query("SELECT p.id, p.name, p.project_code, p.progress_percentage, p.end_date, s.name as status_name, s.color as status_color FROM operational_projects p JOIN project_team pt ON pt.project_id = p.id JOIN operational_project_statuses s ON s.id = p.status_id WHERE pt.user_id = $userId AND pt.is_active = 1 AND p.is_deleted = 0 AND p.status_id IN (2, 6) ORDER BY p.end_date ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 4. الإشعارات والإعلانات
// ==========================================================
$myNotifications = $db->query("SELECT title, message, created_at, is_read FROM notifications WHERE user_id = $userId ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$announcements = $db->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard</title>
    <link rel="icon" type="image/png" href="assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="assets/css/layout.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; color: #444; margin: 0; }
        .page-wrapper { padding: 2rem; margin: 0 auto; }

        /* Welcome & Stats */
        .welcome-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .welcome-title { font-size: 1.8rem; font-weight: 700; color: #2c3e50; margin: 0; }
        .welcome-title span { color: #ff8c00; }
        .date-badge { background: #fff; padding: 8px 15px; border-radius: 20px; border: 1px solid #eee; color: #777; font-size: 0.9rem; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }

        /* Announcements */
        .announcement-box { 
            background: linear-gradient(135deg, #fff 0%, #fffbf2 100%); 
            padding: 20px; border-radius: 12px; border: 1px solid #fae3c4; 
            margin-bottom: 30px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .ann-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; color: #d35400; font-weight: bold; font-size: 1.1rem; }
        .ann-item { 
            padding: 12px; border-left: 4px solid #ff8c00; background: #fff; 
            margin-bottom: 8px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.01);
            display: flex; justify-content: space-between; align-items: center;
        }
        .ann-item:last-child { margin-bottom: 0; }
        .ann-text h4 { margin: 0 0 3px 0; font-size: 0.95rem; color: #333; }
        .ann-text p { margin: 0; font-size: 0.85rem; color: #666; }
        .ann-date { font-size: 0.75rem; color: #aaa; white-space: nowrap; }

        /* KPI Grid */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .kpi-card { 
            background: #fff; padding: 20px; border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; 
            display: flex; align-items: center; justify-content: space-between;
        }
        .kpi-data h3 { margin: 0; font-size: 2rem; color: #2c3e50; }
        .kpi-data p { margin: 5px 0 0; color: #95a5a6; font-size: 0.85rem; text-transform: uppercase; font-weight: bold; }
        .kpi-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .ic-blue { background: #e3f2fd; color: #3498db; }
        .ic-orange { background: #fff3e0; color: #ff8c00; }
        .ic-green { background: #e8f5e9; color: #2ecc71; }
        .ic-purple { background: #f3e5f5; color: #9b59b6; }

        /* Main Grid */
        .dashboard-main { display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px; }
        @media (max-width: 1000px) { .dashboard-main { grid-template-columns: 1fr; } }

        .content-card { background: #fff; border-radius: 16px; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
        .card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #f9f9f9; }
        .card-title { font-weight: 700; color: #2c3e50; font-size: 1.05rem; }

        /* Calendar Styles */
        .cal-header { display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold; color: #333; }
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; }
        .cal-day-name { font-size: 0.75rem; color: #aaa; padding: 5px; font-weight: 600; text-align: center; }
        .cal-day { 
            background: #fff; border: 1px solid #f1f2f6; border-radius: 12px; 
            min-height: 80px; /* جعل الخلية أطول قليلاً */
            display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start;
            padding: 8px; font-size: 0.9rem; color: #555; position: relative; transition: 0.2s;
        }
        .cal-day:hover { background: #f9fbfd; border-color: #3498db; cursor: pointer; }
        .cal-day.today { background: #fff8e1; border-color: #ff8c00; color: #d35400; font-weight: bold; }
        .cal-day span { font-weight: bold; margin-bottom: 5px; }
        
        .dots-container { display: flex; gap: 3px; flex-wrap: wrap; }
        .event-dot { width: 8px; height: 8px; border-radius: 50%; }
        
        .empty-day { background: transparent; border: none; }

        /* Modal Task List */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
        .modal-content { 
            background-color: #fff; margin: 5% auto; padding: 0; border: 1px solid #888; 
            width: 500px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: fadeIn 0.3s;
        }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; border-radius: 12px 12px 0 0; }
        .close-btn { color: #aaa; font-size: 24px; font-weight: bold; cursor: pointer; }
        .modal-body { padding: 20px; max-height: 60vh; overflow-y: auto; }
        
        .modal-event-item { 
            display: flex; gap: 12px; padding: 12px; border-radius: 8px; margin-bottom: 10px; 
            background: #fff; border: 1px solid #eee; transition: 0.2s; align-items: center; text-decoration: none; color: inherit;
        }
        .modal-event-item:hover { transform: translateX(5px); border-color: #ccc; }
        .ev-icon { width: 35px; height: 35px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; flex-shrink: 0; }
        .ev-info h4 { margin: 0 0 3px 0; font-size: 0.95rem; color: #2c3e50; }
        .ev-info p { margin: 0; font-size: 0.8rem; color: #7f8c8d; }
        .ev-tag { margin-left: auto; font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; background: #f0f0f0; color: #666; font-weight: 600; text-transform: uppercase; }

        /* Tables */
        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .modern-table th { text-align: left; padding: 10px; color: #888; font-size: 0.8rem; border-bottom: 1px solid #eee; }
        .modern-table td { padding: 12px 10px; border-bottom: 1px solid #f9f9f9; font-size: 0.9rem; color: #555; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; color: #fff; }
        .chart-container { position: relative; height: 200px; width: 100%; display: flex; justify-content: center; }
    </style>
</head>

<body>

    <?php include "layout/header.php"; ?>
    <?php include "layout/sidebar.php"; ?>

    <div class="main-content">
    <div class="page-wrapper">

        <div class="welcome-header">
            <div>
                <h1 class="welcome-title">Hello, <span><?= htmlspecialchars($userName) ?></span> 👋</h1>
                <p class="welcome-sub" style="color:#888; margin:5px 0;">Here's your comprehensive overview for today.</p>
            </div>
            <div class="date-badge">
                <i class="fa-regular fa-calendar"></i> <?= date('l, d F Y') ?>
            </div>
        </div>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-data">
                    <h3><?= $myProjectsCount ?></h3>
                    <p>Active Projects</p>
                </div>
                <div class="kpi-icon ic-blue"><i class="fa-solid fa-briefcase"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-data">
                    <h3><?= $myOpenTasks ?></h3>
                    <p>Pending Tasks</p>
                </div>
                <div class="kpi-icon ic-orange"><i class="fa-solid fa-list-check"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-data">
                    <h3><?= $completionRate ?>%</h3>
                    <p>Completion Rate</p>
                </div>
                <div class="kpi-icon ic-green"><i class="fa-solid fa-chart-pie"></i></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-data">
                    <h3><?= count($myNotifications) ?></h3>
                    <p>New Alerts</p>
                </div>
                <div class="kpi-icon ic-purple"><i class="fa-regular fa-bell"></i></div>
            </div>
        </div>

        <?php if(!empty($announcements)): ?>
        <div class="announcement-box">
            <div class="ann-header">
                <i class="fa-solid fa-bullhorn"></i> Important Announcements
            </div>
            <?php foreach($announcements as $ann): ?>
            <div class="ann-item">
                <div class="ann-text">
                    <h4><?= htmlspecialchars($ann['title']) ?></h4>
                    <p><?= htmlspecialchars(substr($ann['message'], 0, 100)) ?>...</p>
                </div>
                <div class="ann-date">
                    <?= date('M d', strtotime($ann['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-main">
            
            <div class="left-col">
                <div class="content-card">
                    <div class="card-head">
                        <div class="card-title"><i class="fa-regular fa-calendar-check" style="color:#ff8c00;"></i> Comprehensive Calendar</div>
                        <span style="font-size:0.85rem; color:#888;"><?= date('F Y') ?></span>
                    </div>
                    
                    <div class="calendar-wrapper">
                        <div style="display:flex; gap:15px; margin-bottom:15px; font-size:0.75rem; flex-wrap:wrap;">
                            <span style="display:flex; align-items:center;"><span class="event-dot" style="background:#3498db; margin-right:5px;"></span> Task</span>
                            <span style="display:flex; align-items:center;"><span class="event-dot" style="background:#e74c3c; margin-right:5px;"></span> Project Deadline</span>
                            <span style="display:flex; align-items:center;"><span class="event-dot" style="background:#f1c40f; margin-right:5px;"></span> Milestone</span>
                            <span style="display:flex; align-items:center;"><span class="event-dot" style="background:#e67e22; margin-right:5px;"></span> Update Required</span>
                            <span style="display:flex; align-items:center;"><span class="event-dot" style="background:#9b59b6; margin-right:5px;"></span> Initiative</span>
                        </div>

                        <div class="cal-grid">
                            <div class="cal-day-name">Sun</div><div class="cal-day-name">Mon</div><div class="cal-day-name">Tue</div><div class="cal-day-name">Wed</div><div class="cal-day-name">Thu</div><div class="cal-day-name">Fri</div><div class="cal-day-name">Sat</div>
                            
                            <?php
                            $firstDayOfMonth = date('w', strtotime("$currentYear-$currentMonth-01"));
                            for($i = 0; $i < $firstDayOfMonth; $i++) { echo '<div class="empty-day"></div>'; }
                            
                            for($day = 1; $day <= $daysInMonth; $day++) {
                                $isToday = ($day == date('j') && $currentMonth == date('m'));
                                $dayEvents = $calendarEvents[$day] ?? [];
                                $hasEvent = !empty($dayEvents);
                                
                                $onclick = $hasEvent ? "onclick='showDayEvents($day)'" : "";
                                
                                echo "<div class='cal-day " . ($isToday ? 'today ' : '') . ($hasEvent ? 'has-task' : '') . "' $onclick>";
                                echo "<span>$day</span>";
                                
                                if ($hasEvent) {
                                    echo "<div class='dots-container'>";
                                    foreach($dayEvents as $ev) {
                                        echo "<span class='event-dot' style='background:{$ev['color']}' title='{$ev['type']}: {$ev['title']}'></span>";
                                    }
                                    echo "</div>";
                                }
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-col">
                
                <div class="content-card">
                    <div class="card-head">
                        <div class="card-title"><i class="fa-solid fa-chart-simple" style="color:#3498db;"></i> Task Completion</div>
                    </div>
                    <div style="display: flex; align-items: center; justify-content: space-around;">
                        <div class="chart-container" style="width: 140px; height: 140px;">
                            <canvas id="taskRatioChart"></canvas>
                        </div>
                        <div style="flex:1; padding-left: 15px;">
                            <div style="font-size:0.85rem; color:#666; margin-bottom: 5px;">
                                <i class="fa-solid fa-circle" style="color:#2ecc71; font-size:0.6rem;"></i> Done: <strong><?= $stats['completed'] ?? 0 ?></strong>
                            </div>
                            <div style="font-size:0.85rem; color:#666; margin-bottom: 5px;">
                                <i class="fa-solid fa-circle" style="color:#3498db; font-size:0.6rem;"></i> Active: <strong><?= $stats['in_progress'] ?? 0 ?></strong>
                            </div>
                            <div style="font-size:0.85rem; color:#666;">
                                <i class="fa-solid fa-circle" style="color:#e2e8f0; font-size:0.6rem;"></i> Pending: <strong><?= $stats['pending'] ?? 0 ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-head">
                        <div class="card-title"><i class="fa-solid fa-rocket" style="color:#e67e22;"></i> Projects</div>
                    </div>
                    <?php if (empty($myProjects)): ?>
                        <div style="text-align:center; padding:20px; color:#aaa;">No active projects.</div>
                    <?php else: ?>
                        <table class="modern-table">
                            <thead><tr><th>Project</th><th>Progress</th></tr></thead>
                            <tbody>
                                <?php foreach ($myProjects as $p): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:bold; color:#333; font-size:0.9rem;"><?= htmlspecialchars($p['name']) ?></div>
                                    </td>
                                    <td>
                                        <div style="width:80px; height:5px; background:#eee; border-radius:3px; display:inline-block;">
                                            <div style="width:<?= $p['progress_percentage'] ?>%; height:100%; background:<?= $p['progress_percentage']==100?'#2ecc71':'#3498db' ?>; border-radius:3px;"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>
    </div>

    <div id="calModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Schedule for <span id="modalDate" style="color:#ff8c00;"></span></h3>
                <span class="close-btn" onclick="closeCalModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalEventList">
                </div>
        </div>
    </div>

<script>
    // 1. Chart
    const ctx = document.getElementById('taskRatioChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending'],
                datasets: [{
                    data: [<?= $stats['completed'] ?? 0 ?>, <?= $stats['in_progress'] ?? 0 ?>, <?= $stats['pending'] ?? 0 ?>],
                    backgroundColor: ['#2ecc71', '#3498db', '#ecf0f1'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // 2. Calendar Interaction
    const eventsData = <?php echo json_encode($calendarEvents); ?>;
    const currentMonthName = "<?= date('F') ?>";

    function showDayEvents(day) {
        const modal = document.getElementById('calModal');
        const modalDate = document.getElementById('modalDate');
        const list = document.getElementById('modalEventList');
        
        modalDate.innerText = day + " " + currentMonthName;
        list.innerHTML = ""; 

        const events = eventsData[day];
        if (events && events.length > 0) {
            events.forEach(ev => {
                let item = `
                    <a href="${ev.url}" class="modal-event-item">
                        <div class="ev-icon" style="background-color: ${ev.color}">
                            <i class="fa-solid ${ev.icon}"></i>
                        </div>
                        <div class="ev-info">
                            <h4>${ev.title}</h4>
                            <p>${ev.desc}</p>
                        </div>
                        <div class="ev-tag" style="color:${ev.color}; background-color:${ev.color}15;">
                            ${ev.type}
                        </div>
                    </a>
                `;
                list.innerHTML += item;
            });
        } else {
            list.innerHTML = "<p style='text-align:center; color:#999;'>No events for this day.</p>";
        }

        modal.style.display = "block";
    }

    function closeCalModal() {
        document.getElementById('calModal').style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == document.getElementById('calModal')) {
            closeCalModal();
        }
    }
</script>

</body>
</html>