<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";

if (!Auth::role(['super_admin', 'strategy_office'])) {
    die("Access denied.");
}

require_once "../../modules/initiatives/functions.php";

$pillars        = ini_getPillars();
$objectives     = ini_getObjectives();
$users          = ini_getUsers();
//$kpis           = ini_getKpis();

$resource_types = db()->query("
    SELECT id, type_name AS name
    FROM resource_types
    WHERE is_active = 1
    ORDER BY type_name
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Initiative</title>

    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/content.css">
    <link rel="stylesheet" href="../../assets/css/initiative.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="initiative-page">

    <div class="page-header">
        <h1>Create Strategic Initiative</h1>
        <p class="subtext">Define the new initiative and link it to its strategic goals.</p>
    </div>

    <div class="initiative-card">

        <form action="save.php" method="post" id="initiativeForm">

            <!-- 1. BASIC INFO -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-flag"></i>
                    <span>1. Basic Information</span>
                </div>

                <div class="form-row">
                    <div class="form-field full">
                        <label>Initiative Title <span class="required">*</span></label>
                        <input type="text" name="title" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Initiative Code</label>
                        <input type="text" name="initiative_code">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field full">
                        <label>Description</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- 2. STRATEGIC ALIGNMENT -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-bullseye"></i>
                    <span>2. Strategic Alignment</span>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Pillar <span class="required">*</span></label>
                        <select name="pillar_id" required>
                            <option value="">Select...</option>
                            <?php foreach ($pillars as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- strategic objectives -->
                <div class="form-row">
                    <div class="form-field full">
                        <label>Strategic Objectives</label>

                        <div class="objective-input-row">
                            <select id="objective_select">
                                <option value="">Select Objective...</option>
                                <?php foreach ($objectives as $o): ?>
                                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <button type="button" class="btn-secondary" onclick="addObjective()">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>

                        <ul id="selectedObjectives" class="tag-list"></ul>
                    </div>
                </div>
            </div>

            <!-- 3. BUDGET AND TIMELINE -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-calendar-dollar"></i>
                    <span>3. Budget & Timeline</span>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Min Budget</label>
                        <input type="number" name="budget_min">
                    </div>

                    <div class="form-field">
                        <label>Max Budget</label>
                        <input type="number" name="budget_max">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Start Date</label>
                        <input type="date" name="start_date">
                    </div>

                    <div class="form-field">
                        <label>End Date</label>
                        <input type="date" name="end_date">
                    </div>
                </div>
            </div>

            <!-- 4. RESOURCES -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>4. Required Resources</span>
                </div>

                <div id="resourceContainer"></div>

                <button type="button" class="btn-secondary" onclick="addResourceRow()">
                    <i class="fa-solid fa-plus"></i> Add Resource
                </button>
            </div>

            <!-- 5. TEAM & OWNER -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-users"></i>
                    <span>5. Team & Ownership</span>
                </div>

                <!-- owner -->
                <div class="form-row">
                    <div class="form-field full">
                        <label>Initiative Owner <span class="required">*</span></label>
                        <select name="owner_user_id" required>
                            <option value="">Select...</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- team -->
                <div class="form-row">
                    <div class="form-field full">
                        <label>Team Members</label>

                        <div class="team-input-row">
                            <select id="team_user_select">
                                <option value="">Select user...</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <input type="text" id="team_role_input" placeholder="Role">

                            <button type="button" class="btn-secondary" onclick="addTeamMember()">
                                <i class="fa-solid fa-user-plus"></i> Add
                            </button>
                        </div>

                        <ul id="teamList" class="tag-list"></ul>
                    </div>
                </div>
            </div>

            <!-- 6. KPIs -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>6. KPIs & Expected Impact</span>
                </div>

                <button type="button" class="btn-secondary" onclick="openKpiModal()">
                    <i class="fa-solid fa-link"></i> Link KPIs
                </button>

                <button type="button" class="btn-primary" onclick="openNewKpiModal()">
                    <i class="fa-solid fa-plus"></i> New KPI
                </button>

                <div id="selectedKpisContainer" class="kpi-mini-cards"></div>

                <div class="form-row">
                    <div class="form-field full">
                        <label>Expected Impact</label>
                        <textarea name="impact" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- 7. COLLABORATION -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-handshake"></i>
                    <span>7. Cross-Department Collaboration</span>
                </div>

                <button type="button" class="btn-secondary" onclick="openCollabModal()">
                    <i class="fa-solid fa-plus"></i> Add Collaboration
                </button>

                <ul id="collaborationList" class="tag-list"></ul>
            </div>

            <!-- 8. RISKS -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>8. Risks</span>
                </div>

                <button type="button" class="btn-secondary" onclick="openRiskModal()">
                    <i class="fa-solid fa-plus"></i> Add Risk
                </button>

                <ul id="riskList" class="tag-list"></ul>
            </div>

            <!-- 9. NOTES -->
            <div class="section">
                <div class="section-title">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>9. Notes</span>
                </div>

                <div class="form-row">
                    <div class="form-field full">
                        <textarea name="notes" rows="2"></textarea>
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="form-actions">
                <a href="list.php" class="btn-secondary dark">Cancel</a>
                <button type="submit" class="btn-primary">Create Initiative</button>
            </div>

        </form>

    </div>
</div>

<?php include "modal_kpi_select.php"; ?>
<?php include "modal_kpi_new.php"; ?>
<?php include "modal_collab.php"; ?>
<?php include "modal_risk.php"; ?>

<script>
let resourceIndex = 0;

function addResourceRow() {
    const container = document.getElementById("resourceContainer");

    const div = document.createElement("div");
    div.className = "resource-row";

    div.innerHTML = `
        <input type="text" name="resources[${resourceIndex}][name]" placeholder="Item Name">

        <select name="resources[${resourceIndex}][type_id]">
            <?php foreach ($resource_types as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <input type="number" name="resources[${resourceIndex}][qty]" min="1" value="1">

        <button type="button" class="icon-btn danger" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;

    container.appendChild(div);
    resourceIndex++;
}
</script>

<script src="../../assets/js/initiative.js"></script>

</body>
</html>
