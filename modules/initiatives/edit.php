<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";

if (!Auth::role(['super_admin','strategy_office'])) {
    die("Access denied.");
}

require_once "functions.php";

$id = $_GET['id'] ?? null;
if(!$id) die("Invalid ID");

// Load initiative
$initiative = ini_getInitiative($id);
if(!$initiative) die("Not found");

// Load linked data
$pillars       = ini_getPillars();
$objectives    = ini_getObjectives();
$users         = ini_getUsers();
$kpis          = ini_getKpis();
$linkedObjectives = ini_getInitiativeObjectives($id);
$resources     = ini_getInitiativeResources($id);
$team          = ini_getInitiativeTeam($id);
$linkedKpis    = ini_getInitiativeLinkedKpis($id);
$risks         = ini_getInitiativeRisks($id);
$collabs       = ini_getInitiativeCollaborations($id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Initiative</title>

<link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
<link rel="stylesheet" href="../../assets/css/layout.css">
<link rel="stylesheet" href="../../assets/css/content.css">
<link rel="stylesheet" href="../../assets/css/initiative.css">

<style>
.tabs {
    display: flex;
    border-bottom: 2px solid #eee;
    margin-bottom: 20px;
}
.tab {
    padding: 12px 18px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-weight: 600;
    color: #444;
    transition: .2s;
}
.tab.active {
    border-color: #ff9800;
    color: #ff9800;
}
.tab-content { display: none; }
.tab-content.active { display: block; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="initiative-page">

    <div class="page-header-flex">
        <h1>Edit Initiative: <?= htmlspecialchars($initiative['name']) ?></h1>
        <a href="list.php" class="btn-secondary dark">Back</a>
    </div>

    <div class="initiative-card">

        <form action="update.php" method="post" id="initiativeForm">
            <input type="hidden" name="id" value="<?= $id ?>">

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" data-tab="tab1">Basic Info</div>
                <div class="tab" data-tab="tab2">Alignment</div>
                <div class="tab" data-tab="tab3">Resources</div>
                <div class="tab" data-tab="tab4">Team</div>
                <div class="tab" data-tab="tab5">KPIs</div>
                <div class="tab" data-tab="tab6">Risks</div>
                <div class="tab" data-tab="tab7">Collaboration</div>
            </div>

            <!-- TAB 1: BASIC INFO -->
            <div id="tab1" class="tab-content active">

                <div class="form-row">
                    <div class="form-field full">
                        <label>Title *</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($initiative['name']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Code</label>
                        <input type="text" name="code" value="<?= htmlspecialchars($initiative['initiative_code']) ?>">
                    </div>

                    <div class="form-field">
                        <label>Status</label>
                        <select name="status">
                            <option value="not_started" <?= $initiative['status']=="not_started"?"selected":"" ?>>Not Started</option>
                            <option value="in_progress" <?= $initiative['status']=="in_progress"?"selected":"" ?>>In Progress</option>
                            <option value="completed" <?= $initiative['status']=="completed"?"selected":"" ?>>Completed</option>
                            <option value="on_hold" <?= $initiative['status']=="on_hold"?"selected":"" ?>>On Hold</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description"><?= htmlspecialchars($initiative['description_en']) ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Pillar *</label>
                        <select name="pillar_id">
                            <?php foreach ($pillars as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id']==$initiative['pillar_id']?"selected":"" ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Owner *</label>
                        <select name="owner_id">
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $u['id']==$initiative['owner_user_id']?"selected":"" ?>>
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field full">
                        <label>Expected Impact</label>
                        <textarea name="impact"><?= htmlspecialchars($initiative['impact']) ?></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field full">
                        <label>Notes</label>
                        <textarea name="notes"><?= htmlspecialchars($initiative['notes']) ?></textarea>
                    </div>
                </div>

            </div>

            <!-- TAB 2: ALIGNMENT -->
            <div id="tab2" class="tab-content">

                <label>Strategic Objectives</label>

                <ul class="tag-list" id="selectedObjectives">
                    <?php foreach ($linkedObjectives as $o): ?>
                        <li class="tag-item">
                            <span><?= htmlspecialchars($o['name']) ?></span>
                            <button type="button" onclick="this.parentElement.remove()">×</button>
                            <input type="hidden" name="objective_ids[]" value="<?= $o['id'] ?>">
                        </li>
                    <?php endforeach; ?>
                </ul>

            </div>
<!-- TAB 3: RESOURCES -->
<div id="tab3" class="tab-content">

    <div id="resourceContainer">

        <?php foreach ($resources as $i => $r): ?>
            <div class="resource-row">

                <!-- NAME -->
                <input type="text"
                       name="resources[<?= $i ?>][name]"
                       value="<?= htmlspecialchars($r['name']) ?>"
                       placeholder="Item Name">

                <!-- TYPE -->
                <select name="resources[<?= $i ?>][type]">
                    <option value="material" <?= $r['type']=="material"?"selected":"" ?>>Material</option>
                    <option value="software" <?= $r['type']=="software"?"selected":"" ?>>Software</option>
                    <option value="human" <?= $r['type']=="human"?"selected":"" ?>>Human Resource</option>
                    <option value="other" <?= $r['type']=="other"?"selected":"" ?>>Other</option>
                </select>

                <!-- QUANTITY -->
                <input type="number"
                       name="resources[<?= $i ?>][qty]"
                       value="<?= htmlspecialchars($r['qty']) ?>"
                       min="1">

                <!-- DELETE -->
                <button type="button"
                        class="icon-btn danger"
                        onclick="this.parentElement.remove()">
                    <i class="fa-solid fa-trash"></i>
                </button>

            </div>
        <?php endforeach; ?>

    </div>

    <button type="button" onclick="addResourceRow()" class="btn-secondary">
        <i class="fa-solid fa-plus"></i> Add Resource
    </button>

</div>

<!-- TAB 4: TEAM -->
<div id="tab4" class="tab-content">

    <ul class="tag-list" id="teamList">
        <?php foreach ($team as $t): ?>
            <li class="tag-item">
                <span><?= htmlspecialchars($t['name']) ?> – <?= htmlspecialchars($t['role']) ?></span>
                <button type="button" onclick="this.parentElement.remove()">×</button>

                <input type="hidden" name="team_members[][user_id]" value="<?= $t['user_id'] ?>">
                <input type="hidden" name="team_members[][role]" value="<?= htmlspecialchars($t['role']) ?>">
            </li>
        <?php endforeach; ?>
    </ul>

</div>

<!-- TAB 5: KPIs -->
<div id="tab5" class="tab-content">

    <div id="selectedKpisContainer" class="kpi-mini-cards">
        <?php foreach ($linkedKpis as $k): ?>
            <div class="kpi-mini-card">
                <div class="kpi-mini-header">
                    <span><?= htmlspecialchars($k['name']) ?></span>
                </div>
                <input type="hidden" name="kpi_ids[]" value="<?= $k['id'] ?>">
            </div>
        <?php endforeach; ?>
    </div>

    <button type="button" class="btn-secondary" onclick="openKpiModal()">
        Link KPIs
    </button>

    <button type="button" class="btn-primary" onclick="openNewKpiModal()">
        Create KPI
    </button>

</div>

<!-- TAB 6: RISKS -->
<div id="tab6" class="tab-content">

    <ul class="tag-list" id="riskList">
        <?php foreach ($risks as $r): ?>
            <li class="tag-item">
                <span><?= htmlspecialchars($r['title']) ?></span>
                <button type="button" onclick="this.parentElement.remove()">×</button>

                <input type="hidden" name="risks[]" value="<?= htmlspecialchars($r['title']) ?>">
            </li>
        <?php endforeach; ?>
    </ul>

    <button type="button" class="btn-secondary" onclick="openRiskModal()">
        Add Risk
    </button>

</div>

<!-- TAB 7: COLLABORATION -->
<div id="tab7" class="tab-content">

    <ul class="tag-list" id="collaborationList">
        <?php foreach ($collabs as $c): ?>
            <li class="tag-item">
                <span><?= htmlspecialchars($c['department']) ?> – <?= htmlspecialchars($c['reason']) ?></span>
                <button type="button" onclick="this.parentElement.remove()">×</button>

                <input type="hidden" name="collaboration[][dept]" value="<?= $c['department_id'] ?>">
                <input type="hidden" name="collaboration[][reason]" value="<?= htmlspecialchars($c['reason']) ?>">
            </li>
        <?php endforeach; ?>
    </ul>

    <button type="button" class="btn-secondary" onclick="openCollabModal()">
        Add Collaboration
    </button>

</div>

<div class="form-actions">
    <a href="list.php" class="btn-secondary dark">Cancel</a>
    <button type="submit" class="btn-primary">Save Changes</button>
</div>

</form>
</div>
</div>

<?php include "modal_kpi_select.php"; ?>
<?php include "modal_kpi_new.php"; ?>
<?php include "modal_collab.php"; ?>
<?php include "modal_risk.php"; ?>

<script src="../../assets/js/initiative.js"></script>

<script>
// Tabs switching
document.querySelectorAll(".tab").forEach(tab => {
    tab.addEventListener("click", () => {

        document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
        document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active"));

        tab.classList.add("active");
        document.getElementById(tab.dataset.tab).classList.add("active");
    });
});
</script>

</body>
</html>
