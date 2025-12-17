<?php
require_once "../../core/init.php";

if (!in_array('view_project_updates_ceo', $_SESSION['permissions'])) {
    die("Access Denied");
}

$db = Database::getInstance()->pdo();

/* جلب التحديثات */
$stmt = $db->prepare("
    SELECT 
        pu.*,
        op.project_code,
        op.name AS project_name,
        u.full_name_en AS sender_name
    FROM project_updates pu
    JOIN operational_projects op ON pu.project_id = op.id
    JOIN users u ON pu.user_id = u.id
    ORDER BY pu.created_at DESC
");
$stmt->execute();
$updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CEO Project Updates</title>

<link rel="stylesheet" href="../../assets/css/style.css">
<link rel="stylesheet" href="../../assets/css/layout.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<style>
.page-title {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 20px;
}

.update-card {
  background: #fff;
  border-radius: 10px;
  padding: 15px 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,.08);
  margin-bottom: 15px;
  border-left: 6px solid #3498db;
}

.update-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.update-header h3 {
  margin: 0;
  font-size: 18px;
}

.status-badge {
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 12px;
}

.status-pending { background: #f1c40f; color: #000; }
.status-viewed  { background: #2ecc71; color: #fff; }

.update-body {
  margin-top: 10px;
  line-height: 1.7;
}

.update-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 12px;
  font-size: 13px;
  color: #666;
}

.mark-btn {
  background: #27ae60;
  color: #fff;
  border: none;
  padding: 6px 14px;
  border-radius: 5px;
  cursor: pointer;
}
</style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<main class="main-content">

  <div class="page-title">📊 Project Updates (CEO)</div>

  <?php foreach ($updates as $u): ?>

    <div class="update-card">
      <div class="update-header">
        <h3><?= htmlspecialchars($u['project_code']) ?> — <?= htmlspecialchars($u['project_name']) ?></h3>

        <span class="status-badge <?= $u['status'] === 'pending' ? 'status-pending' : 'status-viewed' ?>">
          <?= strtoupper($u['status']) ?>
        </span>
      </div>

      <div class="update-body">
        <?= nl2br(htmlspecialchars($u['description'])) ?>
      </div>

      <div class="update-footer">
        <div>
          👤 <?= htmlspecialchars($u['sender_name']) ?> |
          🕒 <?= $u['created_at'] ?> |
          📈 <?= $u['progress_percent'] ?? '—' ?>%
        </div>

        <?php if ($u['status'] === 'pending'): ?>
          <form method="post" action="mark_viewed.php">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="mark-btn">Mark as Viewed</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

  <?php endforeach; ?>

</main>

</body>
</html>
