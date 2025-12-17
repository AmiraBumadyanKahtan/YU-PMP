<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once "functions.php";

if (!is_super_admin()) die("Access denied");

if (empty($_GET['id'])) die("Invalid KPI ID");

$id = intval($_GET['id']);
$kpi = get_kpi_by_id($id);

if (!$kpi) die("KPI not found");

// Load lists
$statuses    = get_kpi_statuses();
$owners      = get_kpi_owners();
$initiatives = get_initiatives_list();
$projects    = get_projects_list();

// Determine parent
$is_initiative = ($kpi['parent_type'] === "initiative");
$is_project    = ($kpi['parent_type'] === "project");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit KPI</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/kpis.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">

    <style>
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            max-width: 900px;
            margin: auto;
            font-family: "Times New Roman", serif;
        }
        .form-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #ff8c00;
            margin-bottom: 1.5rem;
        }
        .form-group { margin-bottom: 1.2rem; }
        label { font-weight: bold; color: #333; font-size: 0.95rem; }
        input, select, textarea {
            width: 100%;
            height: 42px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #fafafa;
            font-size: 0.95rem;
            transition: 0.2s;
            font-family: "Times New Roman", serif;
        }
        textarea { height: 90px; }
        input:focus, select:focus, textarea:focus {
            border-color: #ff9c31;
            background: #fff;
            box-shadow: 0 0 5px rgba(255, 156, 49, 0.4);
        }
        .btn-primary {
            background: #ff8c00;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: .2s;
            font-size: 0.95rem;
        }
        .btn-primary:hover { background: black; }
        .btn-cancel {
            padding: 10px 18px;
            border-radius: 8px;
            background: #eee;
            color: #444;
            text-decoration: none;
            transition: .2s;
        }
        .btn-cancel:hover { background: #ddd; }
    </style>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <h1 class="page-title"><i class="fa-solid fa-pen"></i> Edit KPI</h1>

        <!-- FORM -->
        <form method="POST" action="update.php" class="form-card">

            <input type="hidden" name="id" value="<?= $kpi['id'] ?>">

            <h2 class="form-title">KPI Information</h2>

            <div class="form-group">
                <label>KPI Name *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($kpi['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($kpi['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>KPI Type *</label>
                <select name="kpi_type" required>
                    <option value="number"     <?= $kpi['kpi_type']=="number"?"selected":"" ?>>Number</option>
                    <option value="percentage" <?= $kpi['kpi_type']=="percentage"?"selected":"" ?>>Percentage</option>
                    <option value="currency"   <?= $kpi['kpi_type']=="currency"?"selected":"" ?>>Currency</option>
                </select>
            </div>

            <div class="form-group">
                <label>Unit</label>
                <input type="text" name="unit" value="<?= htmlspecialchars($kpi['unit']) ?>">
            </div>

            <div class="form-group">
                <label>Target Value *</label>
                <input type="number" step="0.01" name="target_value" value="<?= $kpi['target_value'] ?>" required>
            </div>

            <div class="form-group">
                <label>Baseline Value</label>
                <input type="number" step="0.01" name="baseline_value" value="<?= $kpi['baseline_value'] ?>">
            </div>

            <div class="form-group">
                <label>Update Frequency *</label>
                <select name="frequency" required>
                    <option value="daily"     <?= $kpi['frequency']=="daily"?"selected":"" ?>>Daily</option>
                    <option value="weekly"    <?= $kpi['frequency']=="weekly"?"selected":"" ?>>Weekly</option>
                    <option value="monthly"   <?= $kpi['frequency']=="monthly"?"selected":"" ?>>Monthly</option>
                    <option value="quarterly" <?= $kpi['frequency']=="quarterly"?"selected":"" ?>>Quarterly</option>
                    <option value="yearly"    <?= $kpi['frequency']=="yearly"?"selected":"" ?>>Yearly</option>
                </select>
            </div>

            <div class="form-group">
                <label>Data Source</label>
                <input type="text" name="data_source" value="<?= htmlspecialchars($kpi['data_source']) ?>">
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status_id" required>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $kpi['status_id']==$s['id']?"selected":"" ?>>
                            <?= $s['status_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>KPI Owner *</label>
                <select name="owner_id" required>
                    <?php foreach ($owners as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $kpi['owner_id']==$o['id']?"selected":"" ?>>
                            <?= $o['full_name_en'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr><br>

            <h2 class="form-title">Parent Assignment</h2>

            <div class="form-group">
                <label>Parent Type *</label>
                <select name="parent_type" id="parentType" required>
                    <option value="initiative" <?= $is_initiative?"selected":"" ?>>Initiative</option>
                    <option value="project"    <?= $is_project?"selected":"" ?>>Operational Project</option>
                </select>
            </div>

            <!-- Initiative -->
            <div class="form-group" id="initBox" style="display: <?= $is_initiative?'block':'none' ?>;">
                <label>Select Initiative *</label>
                <select name="initiative_id">
                    <?php foreach ($initiatives as $i): ?>
                        <option value="<?= $i['id'] ?>" <?= ($is_initiative && $kpi['parent_id']==$i['id'])?"selected":"" ?>>
                            <?= $i['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Project -->
            <div class="form-group" id="projBox" style="display: <?= $is_project?'block':'none' ?>;">
                <label>Select Project *</label>
                <select name="project_id">
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($is_project && $kpi['parent_id']==$p['id'])?"selected":"" ?>>
                            <?= $p['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <br>

            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="list.php" class="btn-cancel">Cancel</a>
            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const type = document.getElementById("parentType");
    const init = document.getElementById("initBox");
    const proj = document.getElementById("projBox");

    function toggle() {
        init.style.display = (type.value === "initiative") ? "block" : "none";
        proj.style.display = (type.value === "project") ? "block" : "none";
    }

    type.addEventListener("change", toggle);
});
</script>

</body>
</html>
