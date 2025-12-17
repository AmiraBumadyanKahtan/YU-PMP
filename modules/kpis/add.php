<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/config.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/strategic-project-system/core/auth.php";
require_once "functions.php";

if (!is_super_admin()) die("Access denied");


// Load lists
$statuses = get_kpi_statuses();
$owners = get_kpi_owners();
$initiatives = get_initiatives_list();
$projects = get_projects_list();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add KPI</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/kpis.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">

    <style>
        /* FORM CARD */
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

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            font-weight: bold;
            color: #333;
            font-size: 0.95rem;
        }

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

        textarea {
            height: 90px;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #ff9c31;
            background: #fff;
            box-shadow: 0 0 5px rgba(255, 156, 49, 0.4);
            outline: none;
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
            font-family: "Times New Roman", serif;
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

        <!-- Title -->
        <h1 class="page-title"><i class="fa-solid fa-chart-line"></i> Add KPI</h1>

        <!-- FORM CARD -->
        <form method="POST" action="save.php" class="form-card">

            <h2 class="form-title">KPI Information</h2>

            <!-- KPI Name -->
            <div class="form-group">
                <label>KPI Name *</label>
                <input type="text" name="name" required>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>

            <!-- KPI Type -->
            <div class="form-group">
                <label>KPI Type *</label>
                <select name="kpi_type" required>
                    <option value="number">Number</option>
                    <option value="percentage">Percentage</option>
                    <option value="currency">Currency</option>
                </select>
            </div>

            <!-- Unit -->
            <div class="form-group">
                <label>Unit (e.g., %, SAR, students, etc.)</label>
                <input type="text" name="unit">
            </div>

            <!-- Target -->
            <div class="form-group">
                <label>Target Value *</label>
                <input type="number" step="0.01" name="target_value" required>
            </div>

            <!-- Baseline -->
            <div class="form-group">
                <label>Baseline Value</label>
                <input type="number" step="0.01" name="baseline_value">
            </div>

            <!-- Frequency -->
            <div class="form-group">
                <label>Update Frequency *</label>
                <select name="frequency" required>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <!-- Data Source -->
            <div class="form-group">
                <label>Data Source</label>
                <input type="text" name="data_source">
            </div>

            <!-- Status -->
            <div class="form-group">
                <label>Status *</label>
                <select name="status_id" required>
                    <option value="">--- select ---</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['status_name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Owner -->
            <div class="form-group">
                <label>KPI Owner *</label>
                <select name="owner_id" required>
                    <option value="">--- select ---</option>
                    <?php foreach ($owners as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= $o['full_name_en'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr><br>

            <h2 class="form-title">Parent Assignment</h2>

            <!-- Parent Type -->
            <div class="form-group">
                <label>Parent Type *</label>
                <select name="parent_type" id="parentType" required>
                    <option value="">Select</option>
                    <option value="initiative">Initiative</option>
                    <option value="project">Operational Project</option>
                </select>
            </div>

            <!-- Initiative List -->
            <div class="form-group" id="initBox" style="display:none;">
                <label>Select Initiative *</label>
                <select name="initiative_id">
                    <option value="">Select Initiative</option>
                    <?php foreach ($initiatives as $i): ?>
                        <option value="<?= $i['id'] ?>"><?= $i['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Project List -->
            <div class="form-group" id="projBox" style="display:none;">
                <label>Select Project *</label>
                <select name="project_id">
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= $p['name'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <br>

            <!-- Buttons -->
            <div style="display:flex; gap:1rem; margin-top:1.5rem;">
                <button type="submit" class="btn-primary">Save KPI</button>
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
