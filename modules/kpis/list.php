<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access denied");

$can_modify = is_super_admin();

// Collect filters
$filters = [
    'name' => $_GET['name'] ?? '',
    'parent_type' => $_GET['parent_type'] ?? '',
    'parent_id' => $_GET['parent_id'] ?? '',
    'status_id' => $_GET['status_id'] ?? '',
    'owner_id' => $_GET['owner_id'] ?? '',
];

// Fetch data
$kpis = get_kpis_filtered($filters);
$statuses = get_kpi_statuses();
$owners   = get_kpi_owners();
$initiatives = get_initiatives_list();
$projects    = get_projects_list();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>KPI List</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/kpis.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <!-- Header -->
        <div class="page-header-flex">
            <h1 class="page-title"><i class="fa-solid fa-chart-line"></i> KPIs</h1>

            <?php if ($can_modify): ?>
                <a href="add.php" class="btn-primary">+ Add KPI</a>
            <?php endif; ?>
        </div>

        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">

            <input type="text" name="name" class="filter-input" 
                placeholder="Search KPI name..."
                value="<?= htmlspecialchars($filters['name']) ?>">

            <select name="parent_type" class="filter-select" id="typeFilter">
                <option value="">All Types</option>
                <option value="initiative" <?= $filters['parent_type']=="initiative"?"selected":"" ?>>Initiative</option>
                <option value="project" <?= $filters['parent_type']=="project"?"selected":"" ?>>Operational Project</option>
            </select>

            <!-- initiative projects toggled -->
            <select name="parent_id" class="filter-select" id="initiativeBox" style="display:none;">
                <option value="">All Initiatives</option>
                <?php foreach($initiatives as $i): ?>
                <option value="<?= $i['id'] ?>" <?= $filters['parent_id']==$i['id']?"selected":"" ?>><?= $i['name'] ?></option>
                <?php endforeach; ?>
            </select>

            <select name="parent_id" class="filter-select" id="projectBox" style="display:none;">
                <option value="">All Projects</option>
                <?php foreach($projects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $filters['parent_id']==$p['id']?"selected":"" ?>><?= $p['name'] ?></option>
                <?php endforeach; ?>
            </select>

            <select name="status_id" class="filter-select">
                <option value="">All Status</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filters['status_id']==$s['id']?"selected":"" ?>>
                        <?= $s['status_name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="owner_id" class="filter-select">
                <option value="">All Owners</option>
                <?php foreach ($owners as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $filters['owner_id']==$o['id']?"selected":"" ?>>
                        <?= $o['full_name_en'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-primary"><i class="fa fa-filter"></i> Apply</button>

            <a href="list.php" class="btn-reset">Reset</a>
        </form>

        <!-- TABLE -->
        <div class="kpi-table-wrapper">
            <table class="kpi-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>KPI Name</th>
                        <th>Parent</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>Owner</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($kpis)): ?>
                    <tr><td colspan="8">No KPIs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($kpis as $k): ?>
                        <tr>
                            <td><?= $k['id'] ?></td>
                            <td><?= $k['name'] ?></td>

                            <td><?= ucfirst($k['parent_type']) . " #" . $k['parent_id'] ?></td>

                            <td><?= $k['target_value'] . " " . $k['unit'] ?></td>

                            <td><span class="badge-status"><?= $k['status_label'] ?></span></td>

                            <td><?= $k['owner_name'] ?></td>

                            <td><?= $k['last_updated'] ?></td>

                            <td class="actions">
                                <a href="view.php?id=<?= $k['id'] ?>" class="btn-view"><i class="fa fa-eye"></i></a>

                                <?php if ($can_modify): ?>
                                    <a href="edit.php?id=<?= $k['id'] ?>" class="btn-edit"><i class="fa fa-pen"></i></a>
                                    <a href="delete.php?id=<?= $k['id'] ?>" class="btn-delete"
                                       onclick="return confirm('Delete KPI?');">
                                       <i class="fa fa-trash"></i>
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

<script>
document.addEventListener("DOMContentLoaded", () => {
    const t = document.getElementById("typeFilter");
    const i = document.getElementById("initiativeBox");
    const p = document.getElementById("projectBox");

    function toggle() {
        i.style.display = (t.value === "initiative") ? "block" : "none";
        p.style.display = (t.value === "project")     ? "block" : "none";
    }

    t.addEventListener("change", toggle);
    toggle();
});
</script>

</body>
</html>
