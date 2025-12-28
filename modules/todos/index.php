<?php
// modules/todos/index.php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "todo_functions.php";

if (!Auth::check()) die("Access denied");

$todos = getUserTodos($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My To-Do List</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- General Theme --- */
        body { background-color: #f8f9fa; font-family: 'Varela Round', sans-serif; color: #2d3436; margin: 0; }
        .page-wrapper { padding: 2rem; max-width: 1000px; margin: 0 auto; }

        /* --- Header --- */
        .page-header-flex { 
            display: flex; justify-content: space-between; align-items: center; 
            margin-bottom: 30px; 
        }
        .page-title { font-size: 1.8rem; color: #2d3436; margin: 0; font-weight: 800; display: flex; align-items: center; gap: 12px; }
        .page-title i { color: #ff8c00; }
        .current-date { 
            background: #fff; padding: 10px 20px; border-radius: 30px; 
            font-size: 0.9rem; color: #636e72; font-weight: 600; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.03); border: 1px solid #f1f2f6;
        }

        /* --- Input Area (Professional & Structured) --- */
        .todo-input-card {
            background: #fff; padding: 20px 25px; border-radius: 20px; 
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.06); border: 1px solid #f1f2f6;
            display: flex; gap: 20px; align-items: center; margin-bottom: 40px;
            transition: transform 0.2s;
        }
        .todo-input-card:focus-within { border-color: #ffcc80; box-shadow: 0 15px 40px -5px rgba(0,0,0,0.1); }

        .input-wrapper { 
            flex: 1; display: flex; flex-direction: column; gap: 10px; 
        }
        
        /* New Styles for Inputs (Boxed Style) */
        .modern-input {
            width: 100%; 
            border: 1px solid #e2e8f0; 
            border-radius: 10px;
            font-size: 0.95rem; 
            color: #2d3436; 
            outline: none; 
            background: #f8fafc; 
            font-family: inherit; 
            padding: 12px 15px;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .modern-input:focus { 
            border-color: #ff8c00; 
            background: #fff; 
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1); 
        }
        .modern-input::placeholder { color: #b2bec3; font-weight: 500; }

        /* Specific Tweaks */
        #new-title { font-weight: 700; height: 45px; }
        #new-desc { font-size: 0.9rem; resize: none; height: 45px; }

        /* Date & Button Wrapper */
        .action-wrapper {
            display: flex; gap: 10px; align-items: center;
        }

        .date-input-wrapper input {
            border: 1px solid #e2e8f0; background: #fff; padding: 0 15px; border-radius: 10px;
            color: #636e72; font-family: inherit; font-size: 0.9rem; outline: none; cursor: pointer;
            transition: 0.2s; height: 100px; /* Match height of stacked inputs approx */
            display: flex; align-items: center; justify-content: center;
            width: 140px;
        }
        .date-input-wrapper input:focus, .date-input-wrapper input:hover { border-color: #ff8c00; }

        /* Big Add Button */
        .btn-add {
            background: linear-gradient(135deg, #ff8c00 0%, #e67e00 100%);
            color: #fff; border: none; width: 60px; height: 100px; border-radius: 12px;
            font-size: 1.5rem; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3); display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 140, 0, 0.4); background: linear-gradient(135deg, #e67e00 0%, #d35400 100%); }

        /* --- Todo List --- */
        .todo-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 15px; }
        
        .todo-item {
            background: #fff; border-radius: 12px; padding: 18px 25px;
            display: flex; align-items: center; gap: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid #f1f2f6;
            transition: all 0.2s; position: relative; overflow: hidden;
        }
        .todo-item:hover { transform: translateX(5px); border-color: #ffcc80; box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        /* Border Indicator */
        .system-task { border-left: 6px solid #3498db; }
        .personal-task { border-left: 6px solid #ff8c00; }

        /* Checkbox Custom */
        .checkbox-wrapper { display: flex; align-items: center; }
        .todo-checkbox {
            appearance: none; width: 24px; height: 24px; border: 2px solid #dfe6e9; border-radius: 6px;
            cursor: pointer; position: relative; transition: 0.2s; margin: 0;
        }
        .todo-checkbox:checked { background: #2ecc71; border-color: #2ecc71; }
        .todo-checkbox:checked::after {
            content: '\f00c'; font-family: "Font Awesome 6 Free"; font-weight: 900;
            color: #fff; font-size: 14px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        }

        /* Content Styling */
        .todo-content { flex: 1; display: flex; flex-direction: column; justify-content: center; gap: 6px; }
        .todo-title { font-size: 1.05rem; font-weight: 700; color: #2d3436; transition: 0.3s; }
        .todo-desc { font-size: 0.9rem; color: #95a5a6; line-height: 1.4; display: block; }

        /* Tags */
        .todo-meta-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .tag { font-size: 0.7rem; padding: 4px 10px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .tag-system { background: #e3f2fd; color: #1565c0; }
        .tag-personal { background: #fff3e0; color: #e67e22; }
        .tag-date { background: #f8f9fa; color: #636e72; border: 1px solid #eee; }
        .tag-overdue { background: #ffebee; color: #c0392b; border: 1px solid #ffcdd2; }

        /* Action Link */
        .action-btn-link {
            font-size: 0.75rem; color: #fff; text-decoration: none; font-weight: 600;
            background: #2d3436; padding: 5px 12px; border-radius: 20px; transition: 0.2s;
            display: inline-flex; align-items: center; gap: 6px; margin-left: auto;
        }
        .action-btn-link:hover { background: #ff8c00; }

        /* Completed State */
        .todo-item.completed { background: #f9f9f9; border-color: #eee; opacity: 0.7; }
        .todo-item.completed .todo-title { text-decoration: line-through; color: #b2bec3; }
        .todo-item.completed .todo-desc { color: #dfe6e9; }
        .todo-item.completed .tag, .todo-item.completed .action-btn-link { display: none; }

        /* Delete Button */
        .btn-del-todo {
            background: #fff0f0; border: none; color: #e74c3c; cursor: pointer;
            width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-size: 1rem; transition: 0.2s; margin-left: 10px;
        }
        .btn-del-todo:hover { background: #e74c3c; color: #fff; }

        /* Empty State */
        .empty-state { text-align: center; padding: 80px 20px; color: #b2bec3; background: #fff; border-radius: 20px; border: 2px dashed #eee; }
        .empty-state i { font-size: 4rem; color: #f1f2f6; margin-bottom: 20px; }
        .empty-state h3 { margin: 0 0 5px 0; color: #636e72; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-clipboard-check"></i> My To-Do List</h1>
        <div class="current-date">
            <i class="fa-regular fa-calendar-days" style="color:#ff8c00; margin-right:5px;"></i> <?= date('l, F d, Y') ?>
        </div>
    </div>

    <div class="todo-container">
        
        <div class="todo-input-card">
            <div class="input-wrapper">
                <input type="text" id="new-title" class="modern-input" placeholder="Type a new task title..." required autocomplete="off">
                <input type="text" id="new-desc" class="modern-input" placeholder="Add optional details/notes..." autocomplete="off">
            </div>
            
            <div class="action-wrapper">
                <div class="date-input-wrapper">
                    <input type="date" id="new-date" title="Set Due Date">
                </div>
                <button class="btn-add" onclick="addTodo()" title="Add Task">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>

        <ul class="todo-list" id="todo-list-ul">
            <?php if (empty($todos)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-mug-hot"></i>
                    <h3>All caught up!</h3>
                    <p>No pending tasks. Enjoy your day!</p>
                </div>
            <?php else: ?>
                <?php foreach ($todos as $t): ?>
                    <?php 
                        $isOverdue = ($t['due_date'] && $t['due_date'] < date('Y-m-d') && !$t['is_completed']);
                        $isSystem = $t['is_system_generated'];
                        $taskTypeClass = $isSystem ? 'system-task' : 'personal-task';
                        
                        // --- Link Logic ---
                        $entityLink = ''; $linkText = 'View'; $linkIcon = 'fa-arrow-right';
                        
                        if (!empty($t['related_entity_type']) && !empty($t['related_entity_id'])) {
                            switch($t['related_entity_type']) {
                                case 'project': case 'project_view':
                                    $entityLink = BASE_URL . "modules/operational_projects/view.php?id=" . $t['related_entity_id'];
                                    $linkText = 'Project'; $linkIcon = 'fa-folder-open'; break;
                                case 'project_update': case 'project_update_reminder':
                                    $entityLink = BASE_URL . "modules/operational_projects/updates_reminder.php?id=" . $t['related_entity_id'];
                                    $linkText = 'Update'; $linkIcon = 'fa-pen-to-square'; break;
                                case 'ceo_review':
                                    $db = Database::getInstance()->pdo();
                                    $pid = $db->query("SELECT id FROM project_updates WHERE id = {$t['related_entity_id']}")->fetchColumn();
                                    $entityLink = BASE_URL . "modules/reports/ceo_review.php?id=" . $pid;
                                    $linkText = 'Report'; $linkIcon = 'fa-chart-line'; break;
                                case 'project_approvals':
                                    $entityLink = BASE_URL . "modules/approvals/dashboard.php?id=" . $t['related_entity_id'];
                                    $linkText = 'Approve'; $linkIcon = 'fa-stamp'; break;
                                case 'task': case 'task_view':
                                    $db = Database::getInstance()->pdo();
                                    $pid = $db->query("SELECT project_id FROM project_tasks WHERE id = {$t['related_entity_id']}")->fetchColumn();
                                    $entityLink = BASE_URL . "modules/operational_projects/milestones.php?id=" . $pid; 
                                    $linkText = 'Task'; $linkIcon = 'fa-list-check'; break;
                                case 'collaboration_review':
                                    $entityLink = BASE_URL . "modules/collaborations/index.php";
                                    $linkText = 'Request'; $linkIcon = 'fa-handshake'; break;
                                case 'kpi': case 'kpi_view':
                                    $entityLink = BASE_URL . "modules/operational_projects/kpis.php?id=" . $t['related_entity_id'];
                                    $linkText = 'KPIs'; $linkIcon = 'fa-chart-pie'; break;
                                case 'kpi_view_direct':
                                    $db = Database::getInstance()->pdo();
                                    $pid = $db->query("SELECT parent_id FROM kpis WHERE id = {$t['related_entity_id']}")->fetchColumn();
                                    $entityLink = BASE_URL . "modules/operational_projects/kpis.php?id=" . $pid;
                                    $linkText = 'Reading'; $linkIcon = 'fa-pen'; break;
                            }
                        }
                    ?>
                    
                    <li class="todo-item <?= $taskTypeClass ?> <?= $t['is_completed'] ? 'completed' : '' ?>" id="todo-<?= $t['id'] ?>">
                        
                        <div class="checkbox-wrapper">
                            <input type="checkbox" class="todo-checkbox" onchange="toggleTodo(<?= $t['id'] ?>)" <?= $t['is_completed'] ? 'checked' : '' ?>>
                        </div>
                        
                        <div class="todo-content">
                            <span class="todo-title"><?= htmlspecialchars($t['title']) ?></span>
                            
                            <?php if(!empty($t['description'])): ?>
                                <span class="todo-desc"><?= htmlspecialchars($t['description']) ?></span>
                            <?php endif; ?>
                            
                            <div class="todo-meta-row">
                                <?php if ($t['is_system_generated']): ?>
                                    <span class="tag tag-system"><i class="fa-solid fa-robot"></i> System</span>
                                <?php else: ?>
                                    <span class="tag tag-personal"><i class="fa-solid fa-user"></i> Personal</span>
                                <?php endif; ?>

                                <?php if ($t['due_date']): ?>
                                    <span class="tag <?= $isOverdue ? 'tag-overdue' : 'tag-date' ?>">
                                        <i class="fa-regular fa-calendar"></i> <?= date('M d', strtotime($t['due_date'])) ?>
                                        <?php if($isOverdue): ?> (Late) <?php endif; ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($entityLink && !$t['is_completed']): ?>
                                    <a href="<?= $entityLink ?>" class="action-btn-link">
                                        <?= $linkText ?> <i class="fa-solid <?= $linkIcon ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button class="btn-del-todo" onclick="deleteTodo(<?= $t['id'] ?>)" title="Delete Task">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

    </div>

</div>
</div>

<script>
function toggleTodo(id) {
    const item = document.getElementById('todo-' + id);
    item.classList.toggle('completed'); 

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle&id=${id}`
    });
}

function deleteTodo(id) {
    Swal.fire({
        title: 'Delete Task?',
        text: "Are you sure?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#aaa',
        confirmButtonText: 'Yes, delete',
        padding: '1em'
    }).then((result) => {
        if (result.isConfirmed) {
            const item = document.getElementById('todo-' + id);
            item.style.transform = 'translateX(20px)';
            item.style.opacity = '0';
            
            setTimeout(() => item.remove(), 300);

            fetch('actions.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete&id=${id}`
            });
        }
    });
}

function addTodo() {
    const title = document.getElementById('new-title').value;
    const desc = document.getElementById('new-desc').value;
    const date = document.getElementById('new-date').value;

    if (!title) {
        Swal.fire({ icon: 'warning', title: 'Task needs a title!', timer: 1500, showConfirmButton: false });
        return;
    }

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&title=${encodeURIComponent(title)}&description=${encodeURIComponent(desc)}&due_date=${date}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Could not add task.' });
        }
    });
}
</script>

</body>
</html>