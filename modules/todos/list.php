<?php
require_once "../../core/init.php";

$userId = $_SESSION['user_id'];

$stmt = db()->prepare("
    SELECT *
    FROM user_todos
    WHERE user_id = ?
      AND is_completed = 0
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$todos = $stmt->fetchAll();
?>

<h2 class="section-title">My To-Do List</h2>

<div class="approval-grid">

<?php if (!$todos): ?>
    <div class="empty-box">No pending tasks.</div>
<?php endif; ?>

<?php foreach ($todos as $todo): ?>
    <div class="approval-card">
        <span class="badge pending">TODO</span>

        <h3><?= htmlspecialchars($todo['title']) ?></h3>
        <p><?= htmlspecialchars($todo['description']) ?></p>

        <?php if ($todo['related_entity_type'] === 'approval'): ?>
            <a class="btn btn-primary" 
               href="../approvals/view.php?id=<?= $todo['related_entity_id'] ?>">
               Open Approval
            </a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</div>
