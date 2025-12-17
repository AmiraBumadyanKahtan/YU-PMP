<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/init.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";

$db = new Database();
$userId = $_SESSION['user_id'];

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
    SELECT ai.id AS instance_id, ai.entity_id, ai.entity_type, ai.status AS instance_status,
           p.name AS pillar_name, p.pillar_number,
           af.stage_name,
           (SELECT decision FROM approval_actions WHERE instance_id = ai.id AND reviewer_id = ? ORDER BY created_at DESC LIMIT 1) AS my_decision,
           (SELECT comments FROM approval_actions WHERE instance_id = ai.id AND reviewer_id = ? ORDER BY created_at DESC LIMIT 1) AS my_comments,
           (SELECT created_at FROM approval_actions WHERE instance_id = ai.id AND reviewer_id = ? ORDER BY created_at DESC LIMIT 1) AS my_date
    FROM approval_instances ai
    JOIN pillars p ON p.id = ai.entity_id
    JOIN approval_flow_stages af ON af.id = ai.current_stage_id
    WHERE ai.entity_type = 'pillar'
";

$params = [$userId, $userId, $userId];

// Apply filters
if ($status !== '') {
    $sql .= " AND ai.status = ?";
    $params[] = $status;
}

if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR p.pillar_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY ai.id DESC";

$records = $db->fetchAll($sql, $params);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Previous Approvals</title>
    <style>
        body { background:#f3f6fb; margin:0; font-family:sans-serif; }
        .container { max-width:1100px; margin:30px auto; background:white; padding:20px; border-radius:10px; }
        h2 { margin-bottom:20px; }
        .filters { display:flex; gap:15px; margin-bottom:20px; }
        .filters input, .filters select {
            padding:10px;
            border:1px solid #ccc;
            border-radius:6px;
            width:200px;
        }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        table th, table td {
            padding:12px;
            border-bottom:1px solid #eee;
            text-align:left;
        }
        table th { background:#fafafa; }
        .status-badge {
            padding:6px 10px;
            border-radius:6px;
            font-size:12px;
            font-weight:bold;
            text-transform:capitalize;
        }
        .approved { background:#d4f7dc; color:#27ae60; }
        .rejected { background:#f9d6d5; color:#c0392b; }
        .returned { background:#fff3cd; color:#856404; }
        .in_progress { background:#dce9ff; color:#2c5eff; }
        .pending { background:#f8e7c1; color:#8e5800; }

        .btn-view {
            background:#3498db;
            color:white;
            padding:8px 12px;
            border-radius:6px;
            text-decoration:none;
            font-weight:bold;
        }
        .btn-view:hover {
            background:#217dbb;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Previous Approvals</h2>

    <!-- Filters -->
    <form method="GET">
        <div class="filters">
            <input type="text" name="search" placeholder="Search pillar..." value="<?= htmlspecialchars($search) ?>">

            <select name="status">
                <option value="">All Statuses</option>
                <option value="approved" <?= $status=='approved'?'selected':'' ?>>Approved</option>
                <option value="rejected" <?= $status=='rejected'?'selected':'' ?>>Rejected</option>
                <option value="returned" <?= $status=='returned'?'selected':'' ?>>Returned</option>
                <option value="in_progress" <?= $status=='in_progress'?'selected':'' ?>>In Progress</option>
                <option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
            </select>

            <button style="padding:10px 18px; border:none; background:#2c3e50; color:white; border-radius:6px; cursor:pointer;">
                Filter
            </button>
        </div>
    </form>

    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th>Pillar</th>
                <th>Stage</th>
                <th>Status</th>
                <th>My Decision</th>
                <th>Date</th>
                <th>View</th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="6">No previous approvals found.</td></tr>
        <?php else: ?>
            <?php foreach ($records as $row): ?>

                <?php
                $badgeClass = match($row['instance_status']) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    'returned' => 'returned',
                    'in_progress' => 'in_progress',
                    default => 'pending'
                };
                ?>

                <tr>
                    <td>
                        <strong><?= $row['pillar_number'] ?></strong> - <?= htmlspecialchars($row['pillar_name']) ?>
                    </td>
                    <td><?= $row['stage_name'] ?></td>

                    <td>
                        <span class="status-badge <?= $badgeClass ?>">
                            <?= $row['instance_status'] ?>
                        </span>
                    </td>

                    <td><?= $row['my_decision'] ?? '-' ?></td>
                    <td><?= $row['my_date'] ?? '-' ?></td>

                    <td>
                        <a class="btn-view" href="approval_view.php?instance_id=<?= $row['instance_id'] ?>">
                            View Request
                        </a>
                    </td>
                </tr>

            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>

    </table>
</div>

</body>
</html>
