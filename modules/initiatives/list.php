<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "initiative_functions.php";

if (!Auth::check()) die("Access denied");

// Collect filters
$filters = [
    'search'     => $_GET['search']     ?? '',
    'pillar_id'  => $_GET['pillar_id']  ?? '',
    'owner_id'   => $_GET['owner_id']   ?? '',
    'status_id'  => $_GET['status_id']  ?? '',
    'priority'   => $_GET['priority']   ?? '',
    'start_from' => $_GET['start_from'] ?? '',
    'end_to'     => $_GET['end_to']     ?? ''
];

// Fetch dropdown lists
$pillars  = getAllPillars();
$owners   = getAllOwners();
$statuses = getAllInitiativeStatuses();

// Fetch main table data
$initiatives = getFilteredInitiatives($filters);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Initiatives</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/initiatives.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <!-- HEADER -->
        <div class="page-header-flex">
            <h1 class="page-title"><i class="fa-solid fa-bullseye"></i> Initiatives</h1>

            <?php if (Auth::role(['super_admin','strategy_office'])): ?>
            <a href="create.php" class="btn-primary action-btn">
                + Add Initiative
            </a>
            <?php endif; ?>
        </div>


        <!-- FILTERS -->
        <form method="get" class="filter-bar">

            <!-- Search -->
            <input type="text" name="search" class="filter-input"
                   placeholder="Search..."
                   value="<?= htmlspecialchars($filters['search']) ?>">

            <!-- Pillar -->
            <select name="pillar_id" class="filter-select">
                <option value="">All Pillars</option>
                <?php foreach ($pillars as $p): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= $filters['pillar_id'] == $p['id'] ? "selected" : "" ?>>
                        <?= $p['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Owner -->
            <select name="owner_id" class="filter-select">
                <option value="">All Owners</option>
                <?php foreach ($owners as $o): ?>
                    <option value="<?= $o['id'] ?>"
                        <?= $filters['owner_id'] == $o['id'] ? "selected" : "" ?>>
                        <?= $o['full_name_en'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Status -->
            <select name="status_id" class="filter-select">
                <option value="">All Status</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= $filters['status_id'] == $s['id'] ? "selected" : "" ?>>
                        <?= $s['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Priority -->
            <select name="priority" class="filter-select">
                <option value="">All Priority</option>
                <option value="low"      <?= $filters['priority']==="low" ? "selected":"" ?>>Low</option>
                <option value="medium"   <?= $filters['priority']==="medium" ? "selected":"" ?>>Medium</option>
                <option value="high"     <?= $filters['priority']==="high" ? "selected":"" ?>>High</option>
                <option value="critical" <?= $filters['priority']==="critical" ? "selected":"" ?>>Critical</option>
            </select>

            <!-- Dates -->
            <input type="date" name="start_from" class="filter-date"
                   value="<?= $filters['start_from'] ?>">

            <input type="date" name="end_to" class="filter-date"
                   value="<?= $filters['end_to'] ?>">

            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-filter"></i> Apply
            </button>

            <a href="list.php" class="btn-reset">Reset</a>

        </form>


        <!-- TABLE -->
        <div class="resource-table-wrapper">
            <table class="resource-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Pillar</th>
                        <th>Owner</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Dates</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($initiatives) === 0): ?>
                    <tr><td colspan="9" class="no-data">No initiatives found.</td></tr>

                <?php else: ?>
                    <?php foreach ($initiatives as $i): ?>
                        <tr>
                            <td><?= $i['id'] ?></td>
                            <td><?= $i['initiative_code'] ?></td>
                            <td><?= htmlspecialchars($i['name']) ?></td>

                            <td><?= $i['pillar_name'] ?></td>
                            <td><?= $i['owner_name'] ?></td>

                            <td><span class="badge-active"><?= $i['status_name'] ?></span></td>

                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $i['progress_percentage'] ?>%"></div>
                                </div>
                            </td>

                            <td><?= $i['start_date'] ?> → <?= $i['due_date'] ?></td>

                            <td class="actions">
                                <a href="view.php?id=<?= $i['id'] ?>" class="action-btn btn-view">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <?php if (Auth::role(['super_admin','strategy_office'])): ?>
                                <a href="edit.php?id=<?= $i['id'] ?>" class="action-btn btn-edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="delete.php?id=<?= $i['id'] ?>" 
                                   class="action-btn btn-delete"
                                   onclick="return confirm('Delete this initiative?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                                <?php endif; ?>
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
