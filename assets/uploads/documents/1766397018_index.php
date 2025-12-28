<?php
// index.php
require_once __DIR__ . '/core/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/auth.php';

// التحقق من أن المستخدم مسجل دخول
if (!Auth::check()) {
    header("Location: login.php");
    exit;
}

$db = Database::getInstance()->pdo();
$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? $_SESSION['username'];

// ==========================================================
// 1. User Specific Stats (إحصائيات المستخدم العامة)
// ==========================================================
// عدد المشاريع التي المستخدم عضو نشط فيها
$myProjectsCount = $db->query("SELECT COUNT(DISTINCT project_id) FROM project_team WHERE user_id = $userId AND is_active = 1")->fetchColumn();
// عدد المهام المسندة للمستخدم والتي لم تكتمل بعد
$myOpenTasks = $db->query("SELECT COUNT(*) FROM project_tasks WHERE assigned_to = $userId AND status_id != 3 AND is_deleted = 0")->fetchColumn();

// عدد الموافقات المعلقة (إذا كان للمستخدم دور في سير الموافقات)
$myPendingApprovals = $db->query("
    SELECT COUNT(ai.id) FROM approval_instances ai
    JOIN approval_workflow_stages aws ON aws.id = ai.current_stage_id
    WHERE ai.status = 'in_progress'
    AND (
        (aws.assignee_type = 'system_role' AND aws.stage_role_id = {$_SESSION['role_id']})
        OR
        (aws.assignee_type = 'department_manager' AND EXISTS (SELECT 1 FROM departments d WHERE d.manager_id = $userId))
    )
")->fetchColumn();

// ==========================================================
// 2. My To-Do List (قائمة المهام الشخصية)
// ==========================================================
// نجلب المهام غير المكتملة
$myTodos = $db->query("
    SELECT * FROM user_todos 
    WHERE user_id = $userId AND is_completed = 0 
    ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 3. Upcoming Deadlines (المهام القادمة من المشاريع)
// ==========================================================
// المهام التي يجب تسليمها خلال 7 أيام القادمة
$upcomingTasks = $db->query("
    SELECT t.id as task_id, t.title, t.due_date, p.id as project_id, p.name as project_name, s.name as status_name
    FROM project_tasks t
    JOIN operational_projects p ON p.id = t.project_id
    JOIN task_statuses s ON s.id = t.status_id
    WHERE t.assigned_to = $userId AND t.status_id != 3 AND t.is_deleted = 0
    AND t.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY t.due_date ASC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 4. My Active Projects (قائمة المشاريع النشطة)
// ==========================================================
$myProjects = $db->query("
    SELECT p.id, p.name, p.project_code, p.progress_percentage, p.end_date, s.name as status_name, s.color as status_color
    FROM operational_projects p
    JOIN project_team pt ON pt.project_id = p.id
    JOIN operational_project_statuses s ON s.id = p.status_id
    WHERE pt.user_id = $userId AND pt.is_active = 1 AND p.is_deleted = 0 AND p.status_id IN (2, 6)
    ORDER BY p.end_date ASC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 5. My Resources (العهد والموارد)
// ==========================================================
$myResources = $db->query("
    SELECT wr.name, rt.type_name, wr.created_at
    FROM work_resources wr
    JOIN resource_types rt ON rt.id = wr.resource_type_id
    WHERE wr.assigned_to = $userId
    ORDER BY wr.created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 6. Latest Notifications (الإشعارات)
// ==========================================================
$myNotifications = $db->query("
    SELECT title, message, created_at, is_read 
    FROM notifications 
    WHERE user_id = $userId 
    ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ==========================================================
// 7. Task Statistics (للرسم البياني)
// ==========================================================
$myTaskStats = $db->query("
    SELECT 
        SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status_id = 3 THEN 1 ELSE 0 END) as completed
    FROM project_tasks WHERE assigned_to = $userId AND is_deleted = 0
")->fetch(PDO::FETCH_ASSOC);

// ... (الاستعلامات السابقة)

// --- 8. Fetch Announcements (للداشبورد) ---
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
        /* --- Premium User Theme (Varela Round) --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; color: #444; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* Header */
        .welcome-header { margin-bottom: 35px; }
        .welcome-title { font-size: 2rem; font-weight: 700; color: #2c3e50; margin: 0; }
        .welcome-title span { color: #ff8c00; }
        .welcome-sub { color: #7f8c8d; font-size: 1rem; margin-top: 5px; }

        /* KPI Cards Grid */
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 35px; }
        .kpi-card { 
            background: #fff; padding: 25px; border-radius: 16px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f9f9f9; 
            position: relative; overflow: hidden; display: flex; align-items: center; justify-content: space-between;
            transition: transform 0.2s;
        }
        .kpi-card:hover { transform: translateY(-5px); }
        .kpi-content div { font-size: 0.9rem; color: #95a5a6; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .kpi-content h2 { margin: 0; font-size: 2.2rem; color: #2c3e50; }
        .kpi-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .bg-blue { background: #e3f2fd; color: #3498db; }
        .bg-orange { background: #fff3e0; color: #ff8c00; }
        .bg-red { background: #ffebee; color: #e74c3c; }
        .bg-green { background: #e8f5e9; color: #2ecc71; }

        /* Main Dashboard Grid */
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 80px; }
        @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }

        /* Standard Card Container */
        .content-card { background: #fff; border-radius: 16px; border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; height: 100%; display: flex; flex-direction: column; }
        .card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f9f9f9; }
        .card-title { margin: 0; font-size: 1.1rem; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 10px; }

        /* To-Do List Item */
        .todo-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px dashed #eee; transition: all 0.3s ease; }
        .todo-item:last-child { border: none; }
        .todo-check { margin-top: 3px; cursor: pointer; accent-color: #ff8c00; width: 18px; height: 18px; }
        .todo-link { text-decoration: none; color: inherit; flex: 1; display: block; }
        .todo-link:hover h4 { color: #ff8c00; }
        .todo-text h4 { margin: 0 0 4px 0; font-size: 0.95rem; color: #333; transition: color 0.2s; }
        .todo-text p { margin: 0; font-size: 0.8rem; color: #888; }
        .todo-date { margin-left: auto; font-size: 0.75rem; color: #aaa; white-space: nowrap; }

        /* Notification Item */
        .notif-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f9f9f9; }
        .notif-item:last-child { border: none; }
        .notif-icon { width: 32px; height: 32px; background: #f0f2f5; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #555; flex-shrink: 0; }
        .notif-content h5 { margin: 0 0 3px 0; font-size: 0.9rem; color: #2c3e50; }
        .notif-content p { margin: 0; font-size: 0.8rem; color: #7f8c8d; }
        .notif-time { font-size: 0.7rem; color: #b2bec3; }

        /* Table */
        .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
        .modern-table th { background: #fff8f0; padding: 12px 15px; font-size: 0.8rem; color: #d35400; font-weight: 700; border: none; text-transform: uppercase; }
        .modern-table th:first-child { border-radius: 8px 0 0 8px; } .modern-table th:last-child { border-radius: 0 8px 8px 0; }
        .modern-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; color: #555; font-size: 0.9rem; vertical-align: middle; }
        .modern-table tr:last-child td { border-bottom: none; }
        
        /* Components */
        .prog-track { width: 100%; height: 6px; background: #edf2f7; border-radius: 3px; display: inline-block; margin-top: 5px; }
        .prog-fill { height: 100%; border-radius: 3px; }
        .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 700; color: #fff; }
        .empty-state { text-align: center; padding: 30px; color: #b2bec3; font-style: italic; }

        /* Task Item (Deadline) */
        .task-item { display: flex; gap: 15px; padding: 15px; border-radius: 10px; border: 1px solid #f1f2f6; margin-bottom: 10px; background: #fff; transition: 0.2s; text-decoration: none; color: inherit; }
        .task-item:hover { border-color: #ffcc80; transform: translateX(3px); }
        .task-date-box { background: #fff8e1; color: #ff8f00; border-radius: 8px; padding: 5px 12px; text-align: center; display: flex; flex-direction: column; justify-content: center; min-width: 50px; }
        .task-day { font-size: 1.2rem; font-weight: 700; line-height: 1; }
        .task-month { font-size: 0.75rem; text-transform: uppercase; font-weight: 600; }
        .task-info h4 { margin: 0 0 4px 0; font-size: 0.95rem; color: #2c3e50; }
        .task-info p { margin: 0; font-size: 0.8rem; color: #95a5a6; }

        /* Resource List */
        .res-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed #f0f0f0; }
        .res-text { font-weight: 600; font-size: 0.9rem; color: #333; }
        .res-type { font-size: 0.75rem; color: #999; }
        .announcement-box { 
        background: #fff; border-radius: 16px; padding: 20px; margin-bottom: 30px; 
        border: 1px solid #f0f0f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
        background-image: linear-gradient(135deg, #ffffff 0%, #fffbf2 100%);
    }
    .ann-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .ann-header h3 { margin: 0; font-size: 1.1rem; color: #2c3e50; }
    
    .ann-row { 
        display: flex; gap: 15px; padding: 12px; background: #fff; border-radius: 10px; 
        border: 1px solid #eee; margin-bottom: 10px; align-items: flex-start;
        transition: transform 0.2s;
    }
    .ann-row:hover { transform: translateX(5px); border-color: #ffcc80; }
    
    .ann-badge { 
        padding: 5px 10px; border-radius: 6px; color: #fff; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; 
        min-width: 60px; text-align: center; margin-top: 2px;
    }
    .badge-info { background: #3498db; }
    .badge-success { background: #2ecc71; }
    .badge-warning { background: #f39c12; }
    .badge-danger { background: #e74c3c; }

    .ann-content h4 { margin: 0 0 3px 0; font-size: 0.95rem; color: #333; }
    .ann-content p { margin: 0; font-size: 0.85rem; color: #666; line-height: 1.4; }
    .ann-time { font-size: 0.7rem; color: #aaa; margin-top: 5px; display: block; }
    </style>
</head>

<body>

    <?php include "layout/header.php"; ?>
    <?php include "layout/sidebar.php"; ?>

    <div class="main-content">
    <div class="page-wrapper">

        <div class="welcome-header">
            <h1 class="welcome-title">Welcome back, <span><?= htmlspecialchars($userName) ?></span>!</h1>
            <p class="welcome-sub">Here is your personal workspace snapshot for today.</p>
        </div>

        <?php if(!empty($announcements)): ?>
        <div class="announcement-box">
            <div class="ann-header">
                <i class="fa-solid fa-bullhorn" style="color: #ff8c00; font-size: 1.2rem;"></i>
                <h3>Announcement Board</h3>
            </div>
            
            <?php foreach($announcements as $ann): ?>
            <div class="ann-row">
                <div class="ann-badge badge-<?= $ann['type'] ?>"><?= $ann['type'] ?></div>
                <div class="ann-content">
                    <h4><?= htmlspecialchars($ann['title']) ?></h4>
                    <p><?= nl2br(htmlspecialchars($ann['message'])) ?></p>
                    <span class="ann-time"><i class="fa-regular fa-clock"></i> <?= date('M d, h:i A', strtotime($ann['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-content">
                    <div>Active Projects</div>
                    <h2><?= $myProjectsCount ?></h2>
                </div>
                <div class="kpi-icon bg-blue"><i class="fa-solid fa-briefcase"></i></div>
            </div>

            <div class="kpi-card">
                <div class="kpi-content">
                    <div>My Open Tasks</div>
                    <h2><?= $myOpenTasks ?></h2>
                </div>
                <div class="kpi-icon bg-orange"><i class="fa-solid fa-list-check"></i></div>
            </div>

            <?php if ($myPendingApprovals > 0): ?>
            <div class="kpi-card">
                <div class="kpi-content">
                    <div>Approvals Needed</div>
                    <h2 style="color:#e74c3c;"><?= $myPendingApprovals ?></h2>
                </div>
                <div class="kpi-icon bg-red"><i class="fa-solid fa-stamp"></i></div>
            </div>
            <?php else: ?>
            <div class="kpi-card">
                <div class="kpi-content">
                    <div>Approvals</div>
                    <h2 style="color:#2ecc71;"><i class="fa-solid fa-check"></i></h2>
                </div>
                <div class="kpi-icon bg-green"><i class="fa-solid fa-thumbs-up"></i></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            
            <div class="content-card">
                <div class="card-head">
                    <div class="card-title"><i class="fa-solid fa-diagram-project" style="color:#3498db;"></i> My Active Projects</div>
                </div>
                <?php if (empty($myProjects)): ?>
                    <div class="empty-state">You are not assigned to any active projects yet.</div>
                <?php else: ?>
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th width="25%">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myProjects as $p): ?>
                            <tr>
                                <td>
                                    <span class="proj-name"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="proj-code"><?= $p['project_code'] ?></span>
                                </td>
                                <td><?= date('M d, Y', strtotime($p['end_date'])) ?></td>
                                <td><span class="badge-status" style="background-color: <?= $p['status_color'] ?: '#999' ?>;"><?= $p['status_name'] ?></span></td>
                                <td>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="prog-track"><div class="prog-fill" style="width:<?= $p['progress_percentage'] ?>%; background:<?= $p['progress_percentage']==100?'#2ecc71':'#3498db' ?>"></div></div>
                                        <span style="font-size:0.8rem; font-weight:700;"><?= $p['progress_percentage'] ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <div class="card-head">
                    <div class="card-title"><i class="fa-solid fa-clipboard-check" style="color:#e67e22;"></i> My To-Do List</div>
                </div>
                <?php if(empty($myTodos)): ?>
                    <div class="empty-state">🎉 No pending personal tasks.</div>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;" id="todoList">
                        <?php foreach($myTodos as $todo): 
                            // Determine Link based on related entity type
                            $link = '#';
                            if($todo['related_entity_type'] == 'project') {
                                $link = "modules/operational_projects/view.php?id=" . $todo['related_entity_id'];
                            } elseif ($todo['related_entity_type'] == 'task') {
                                // Assuming we have a way to view task, or link to project
                                $link = "modules/operational_projects/view.php?id=" . $todo['related_entity_id']; // Placeholder
                            } elseif ($todo['related_entity_type'] == 'approval') {
                                $link = "modules/approvals/view.php?id=" . $todo['related_entity_id'];
                            }
                        ?>
                        <div class="todo-item" id="todo-<?= $todo['id'] ?>">
                            <input type="checkbox" class="todo-check" onclick="completeTodo(<?= $todo['id'] ?>)" title="Mark as completed">
                            <a href="<?= $link ?>" class="todo-link">
                                <div class="todo-text">
                                    <h4><?= htmlspecialchars($todo['title']) ?></h4>
                                    <p><?= htmlspecialchars($todo['description'] ?: 'No details') ?></p>
                                </div>
                            </a>
                            <div class="todo-date"><?= $todo['due_date'] ? date('M d', strtotime($todo['due_date'])) : '' ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr 1fr;">
            
            <div class="content-card">
                <div class="card-head">
                    <div class="card-title"><i class="fa-solid fa-clock" style="color:#e74c3c;"></i> Upcoming Deadlines</div>
                </div>
                <?php if(empty($upcomingTasks)): ?>
                    <div class="empty-state"><i class="fa-solid fa-mug-hot" style="font-size:2rem; margin-bottom:10px; display:block;"></i>No urgent deadlines in 7 days.</div>
                <?php else: ?>
                    <?php foreach($upcomingTasks as $t): 
                        $day = date('d', strtotime($t['due_date']));
                        $month = date('M', strtotime($t['due_date']));
                        $isUrgent = (strtotime($t['due_date']) <= strtotime('+2 days'));
                    ?>
                    <a href="modules/operational_projects/view.php?id=<?= $t['project_id'] ?>" class="task-item" style="<?= $isUrgent ? 'border-left: 4px solid #e74c3c;' : '' ?>">
                        <div class="task-date-box" style="<?= $isUrgent ? 'color:#e74c3c; background:#ffebee;' : '' ?>">
                            <span class="task-day"><?= $day ?></span>
                            <span class="task-month"><?= $month ?></span>
                        </div>
                        <div class="task-info">
                            <h4><?= htmlspecialchars($t['title']) ?></h4>
                            <p><i class="fa-solid fa-folder-open" style="font-size:0.7rem;"></i> <?= htmlspecialchars($t['project_name']) ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <div class="card-head">
                    <div class="card-title"><i class="fa-solid fa-laptop" style="color:#9b59b6;"></i> Assigned Resources</div>
                </div>
                <?php if(empty($myResources)): ?>
                    <div class="empty-state">No resources assigned.</div>
                <?php else: ?>
                    <?php foreach($myResources as $res): ?>
                        <div class="res-item">
                            <div style="display:flex; gap:10px; align-items:center;">
                                <div class="res-icon"><i class="fa-solid fa-box-open"></i></div>
                                <div>
                                    <div class="res-text"><?= htmlspecialchars($res['name']) ?></div>
                                    <div class="res-type"><?= htmlspecialchars($res['type_name']) ?></div>
                                </div>
                            </div>
                            <div style="font-size:0.75rem; color:#aaa;"><?= date('Y-m-d', strtotime($res['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <div class="card-head">
                    <div class="card-title"><i class="fa-solid fa-bell" style="color:#f1c40f;"></i> Recent Notifications</div>
                </div>
                <?php if(empty($myNotifications)): ?>
                    <div class="empty-state">No new notifications.</div>
                <?php else: ?>
                    <?php foreach($myNotifications as $notif): ?>
                    <div class="notif-item">
                        <div class="notif-icon"><i class="fa-solid fa-circle-info"></i></div>
                        <div class="notif-content">
                            <h5><?= htmlspecialchars($notif['title']) ?></h5>
                            <p><?= htmlspecialchars(substr($notif['message'], 0, 50)) ?>...</p>
                            <div class="notif-time"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </div>
    </div>

<script>
    // --- To-Do Completion Logic ---
    function completeTodo(id) {
        const item = document.getElementById('todo-' + id);
        
        // Visual Feedback Immediate
        item.style.opacity = '0.5';
        item.style.textDecoration = 'line-through';
        
        // AJAX Request
        fetch('ajax_todo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                setTimeout(() => {
                    item.remove(); // Remove from UI after 0.5s
                    // Optional: Update counter if needed
                }, 500);
            } else {
                alert('Error updating task');
                item.style.opacity = '1';
                item.style.textDecoration = 'none';
            }
        });
    }

    // --- Chart ---
    const ctx = document.getElementById('myTaskChart'); 
    if(ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Completed'],
                datasets: [{
                    data: [<?= $myTaskStats['pending'] ?: 0 ?>, <?= $myTaskStats['in_progress'] ?: 0 ?>, <?= $myTaskStats['completed'] ?: 0 ?>],
                    backgroundColor: ['#e2e8f0', '#3498db', '#2ecc71'], borderWidth: 0, hoverOffset: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'right' } } }
        });
    }
</script>

</body>
</html>