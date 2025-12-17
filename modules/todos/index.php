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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/content.css"> <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .todo-container { max-width: 800px; margin: 0 auto; }
        .todo-input-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 10px; align-items: flex-start; }
        .todo-input-card input, .todo-input-card textarea { border: 1px solid #ddd; padding: 10px; border-radius: 4px; font-family: inherit; }
        .todo-input-card input[type="text"] { flex: 2; }
        .todo-input-card input[type="date"] { flex: 0 0 150px; }
        .btn-add { background: #3498db; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; height: 40px; }
        
        .todo-list { list-style: none; padding: 0; }
        .todo-item { background: #fff; border-bottom: 1px solid #eee; padding: 15px; display: flex; align-items: center; gap: 15px; transition: background 0.2s; }
        .todo-item:first-child { border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .todo-item:last-child { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; border-bottom: none; }
        .todo-item:hover { background: #fcfcfc; }
        
        .todo-item.completed .todo-title { text-decoration: line-through; color: #999; }
        .todo-checkbox { width: 20px; height: 20px; cursor: pointer; accent-color: #27ae60; }
        
        .todo-content { flex: 1; }
        .todo-title { font-size: 1.1rem; font-weight: 500; display: block; }
        .todo-meta { font-size: 0.85rem; color: #888; margin-top: 4px; }
        .tag { padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; margin-right: 5px; }
        .tag-system { background: #e3f2fd; color: #1565c0; }
        .tag-personal { background: #fff3e0; color: #e65100; }
        .tag-date { background: #eee; color: #555; }
        .tag-overdue { background: #ffebee; color: #c62828; }

        .btn-del-todo { color: #ccc; cursor: pointer; background: none; border: none; font-size: 1.1rem; }
        .btn-del-todo:hover { color: #e74c3c; }
        
        /* Link styling */
        .view-link {
            font-size: 0.8rem; color: #3498db; text-decoration: none; margin-left: 10px;
            background: #f0f8ff; padding: 2px 8px; border-radius: 4px; border: 1px solid #d6eaf8;
        }
        .view-link:hover { background: #e3f2fd; text-decoration: none; }
    </style>
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-list-check"></i> My To-Do List</h1>
    </div>

    <div class="todo-container">
        
        <div class="todo-input-card">
            <div style="flex:1; display:flex; flex-direction:column; gap:10px;">
                <input type="text" id="new-title" placeholder="What needs to be done?" required>
                <textarea id="new-desc" rows="1" placeholder="Details (optional)"></textarea>
            </div>
            <input type="date" id="new-date">
            <button class="btn-add" onclick="addTodo()"><i class="fa-solid fa-plus"></i> Add</button>
        </div>

        <ul class="todo-list" id="todo-list-ul">
            <?php foreach ($todos as $t): ?>
                <?php 
                    $isOverdue = ($t['due_date'] && $t['due_date'] < date('Y-m-d') && !$t['is_completed']);
                    
                    // --- 🔥 إصلاح الروابط هنا ---
                    $entityLink = '';
                    $linkText = 'View';
                    
                    switch($t['related_entity_type']) {
                        case 'project':
                        case 'project_update_reminder': // تذكير التحديث يوجه للمشروع
                        case 'project_update': // تذكير التحديث يوجه للمشروع (تاب Updates)
                            $entityLink = BASE_URL . "modules/operational_projects/updates.php?id=" . $t['related_entity_id'];
                            $linkText = 'Submit Update';
                            if($t['related_entity_type'] == 'project') {
                                $entityLink = BASE_URL . "modules/operational_projects/view.php?id=" . $t['related_entity_id'];
                                $linkText = 'View Project';
                            }
                            break;
                            
                        case 'initiative':
                            $entityLink = BASE_URL . "modules/initiatives/view.php?id=" . $t['related_entity_id'];
                            break;
                            
                        case 'collaboration':
                            // إذا كان طلب تعاون، نذهب لصفحة الطلبات (للمدير) أو صفحة المشروع (للموظف)
                            // سنوجهه لصفحة الـ Inbox الخاصة بالتعاون بشكل عام مع تمرير الـ ID للتمييز
                            $entityLink = BASE_URL . "modules/collaborations/index.php?highlight=" . $t['related_entity_id'];
                            $linkText = 'View Request';
                            break;
                            
                        case 'ceo_review': // مراجعة الرئيس التنفيذي
                        case 'project_update_review':
                            $entityLink = BASE_URL . "modules/reports/ceo_review.php?id=" . $t['related_entity_id'];
                            $linkText = 'Review Update';
                            break;
                            
                        case 'task':
                             // المهام قد تكون في milestones.php، نحتاج لمعرفة مشروعها. 
                             // للتبسيط، سنحاول توجيهه للمشروع إذا كان لدينا ID المشروع مخزناً بطريقة ما، 
                             // أو نعتمد على أن المستخدم سيبحث عنها.
                             // الأفضل هنا: تخزين project_id إضافي في الـ Todo أو البحث عنه.
                             // حالياً: لا رابط مباشر للمهمة الفردية إلا إذا عدلنا الجدول.
                             break;
                    }
                ?>
                <li class="todo-item <?= $t['is_completed'] ? 'completed' : '' ?>" id="todo-<?= $t['id'] ?>">
                    
                    <input type="checkbox" class="todo-checkbox" 
                           onchange="toggleTodo(<?= $t['id'] ?>)" 
                           <?= $t['is_completed'] ? 'checked' : '' ?>>
                    
                    <div class="todo-content">
                        <span class="todo-title">
                            <?= htmlspecialchars($t['title']) ?>
                            
                            <?php if ($entityLink && !$t['is_completed']): ?>
                                <a href="<?= $entityLink ?>" class="view-link">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i> <?= $linkText ?>
                                </a>
                            <?php endif; ?>
                        </span>
                        
                        <div class="todo-meta">
                            <?php if ($t['is_system_generated']): ?>
                                <span class="tag tag-system">System</span>
                            <?php else: ?>
                                <span class="tag tag-personal">Personal</span>
                            <?php endif; ?>

                            <?php if ($t['due_date']): ?>
                                <span class="tag <?= $isOverdue ? 'tag-overdue' : 'tag-date' ?>">
                                    <i class="fa-regular fa-clock"></i> <?= date('M d', strtotime($t['due_date'])) ?>
                                </span>
                            <?php endif; ?>

                            <?= htmlspecialchars($t['description']) ?>
                        </div>
                    </div>

                    <button class="btn-del-todo" onclick="deleteTodo(<?= $t['id'] ?>)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </li>
            <?php endforeach; ?>
            
            <?php if (empty($todos)): ?>
                <li style="text-align:center; padding:30px; color:#999;">No tasks found. Enjoy your day!</li>
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
    if(!confirm("Delete this task?")) return;
    
    document.getElementById('todo-' + id).remove(); 

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete&id=${id}`
    });
}

function addTodo() {
    const title = document.getElementById('new-title').value;
    const desc = document.getElementById('new-desc').value;
    const date = document.getElementById('new-date').value;

    if (!title) return alert("Task title is required");

    fetch('actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&title=${encodeURIComponent(title)}&description=${encodeURIComponent(desc)}&due_date=${date}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') location.reload();
        else alert("Error adding task");
    });
}
</script>

</body>
</html>