<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

// Load filters
$search = $_GET['search'] ?? '';
$status_id = $_GET['status_id'] ?? '';
$lead_id = $_GET['lead_id'] ?? '';

// Fetch statuses
$statuses = $db->query("SELECT id, name FROM pillar_statuses ORDER BY sort_order")->fetchAll();
$leaders = $db->query("SELECT id, full_name_en FROM users ORDER BY full_name_en")->fetchAll();

// Build query
$sql = "
    SELECT p.*, s.name AS status_name, u.full_name_en AS lead_name
    FROM pillars p
    LEFT JOIN pillar_statuses s ON s.id = p.status_id
    LEFT JOIN users u ON u.id = p.lead_user_id
    WHERE 1
";

$params = [];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE :s OR p.description LIKE :s)";
    $params[":s"] = "%" . $search . "%";
}

if (!empty($status_id)) {
    $sql .= " AND p.status_id = :st";
    $params[":st"] = $status_id;
}

if (!empty($lead_id)) {
    $sql .= " AND p.lead_user_id = :lead";
    $params[":lead"] = $lead_id;
}

$sql .= " ORDER BY p.pillar_number ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pillars = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pillars</title>

<link rel="stylesheet" href="../../assets/css/layout.css">
<link rel="stylesheet" href="css/pillars_list.css">
<link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-layer-group"></i> Strategic Pillars
        </h1>

        <a href="create.php" class="btn-primary action-btn">
            + Add Pillar
        </a>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-bar">

        <input 
            type="text" 
            name="search" 
            class="filter-input"
            placeholder="Search pillar..."
            value="<?= htmlspecialchars($search) ?>"
        >

        <select name="status_id" class="filter-select">
            <option value="">All Status</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $status_id == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="lead_id" class="filter-select">
            <option value="">All Leads</option>
            <?php foreach ($leaders as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $lead_id == $l['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($l['full_name_en']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-primary">
            <i class="fa-solid fa-filter"></i> Apply
        </button>

        <a href="list.php" class="btn-reset">Reset</a>
    </form>

    <div class="table-wrapper">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Pillar</th>
                    <th>Name</th>
                    <th>Lead</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th width="150">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (count($pillars) === 0): ?>
                    <tr>
                        <td colspan="7" class="no-data">No pillars found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pillars as $p): ?>
                        <tr>
                            <td><?= $p['pillar_number'] ?></td>

                            <td>
                                <span class="pillar-color" style="background: <?= $p['color'] ?>"></span>
                                <?= htmlspecialchars($p['pillar_number']) ?>
                            </td>

                            <td><?= htmlspecialchars($p['name']) ?></td>

                            <td><?= $p['lead_name'] ?: "-" ?></td>

                            <td>
                                <span class="badge-status">
                                    <?= htmlspecialchars($p['status_name']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="progress-bar">
                                    <span style="width: <?= $p['progress_percentage'] ?>%"></span>
                                </div>
                            </td>

                            <td class="actions">
                                <a href="view.php?id=<?= $p['id'] ?>" class="action-btn btn-view">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="edit.php?id=<?= $p['id'] ?>" class="action-btn btn-edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="delete.php?id=<?= $p['id'] ?>" class="action-btn btn-delete"
                                   onclick="return confirm('Delete this pillar?');">
                                    <i class="fa-solid fa-trash"></i>
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

</body>
</html>
