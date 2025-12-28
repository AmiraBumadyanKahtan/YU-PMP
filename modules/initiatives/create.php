<?php
// modules/initiatives/create.php

// تنظيف أي مخرجات سابقة
ob_start();

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::check() || !Auth::can('create_initiative')) {
    http_response_code(403);
    die("Access Denied");
}

$db = Database::getInstance()->pdo();

// ================================================================
// 🟢 AJAX HANDLER
// ================================================================
if (isset($_GET['action']) && $_GET['action'] == 'get_pillar_details' && isset($_GET['pillar_id'])) {
    
    ob_clean(); 
    header('Content-Type: application/json');

    try {
        $pId = $_GET['pillar_id'];

        $team = $db->prepare("
            SELECT u.id, u.full_name_en 
            FROM pillar_team pt
            JOIN users u ON u.id = pt.user_id
            WHERE pt.pillar_id = ? 
            ORDER BY u.full_name_en ASC
        ");
        $team->execute([$pId]);
        $users = $team->fetchAll(PDO::FETCH_ASSOC);

        $objs = $db->prepare("
            SELECT id, objective_text, objective_code 
            FROM strategic_objectives 
            WHERE pillar_id = ? AND is_deleted = 0
            ORDER BY objective_code ASC
        ");
        $objs->execute([$pId]);
        $objectives = $objs->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 'success', 'users' => $users, 'objectives' => $objectives]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit;
}

// ================================================================
// 🔵 PAGE LOGIC
// ================================================================

// 1. جلب الركائز المعتمدة (Approved = 11) + النشطة (3, 4)
$pillars = $db->query("
    SELECT id, name 
    FROM pillars 
    WHERE is_deleted=0 AND status_id IN (11, 3, 4)
    ORDER BY pillar_number ASC
")->fetchAll();

$defaultCode = "INIT-" . date("Y") . "-" . strtoupper(substr(uniqid(), -4));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $code        = $_POST['initiative_code'];
    $name        = trim($_POST['name']);
    $pillar_id   = $_POST['pillar_id'];
    $owner_id    = $_POST['owner_user_id'];
    $start_date  = $_POST['start_date'];
    $end_date    = $_POST['due_date'];
    $description = trim($_POST['description']);
    
    $has_budget  = isset($_POST['has_budget']) ? 1 : 0;
    $budget_min  = $has_budget ? ($_POST['budget_min'] ?: 0) : 0;
    $budget_max  = $has_budget ? ($_POST['budget_max'] ?: 0) : 0;
    $approved_budget = $has_budget ? ($_POST['approved_budget'] ?: 0) : 0;
    $budget_item = $has_budget ? trim($_POST['budget_item']) : null;
    
    $priority    = $_POST['priority'];
    $frequency   = $_POST['update_frequency'];
    $objectives  = $_POST['objectives'] ?? [];

    if (empty($name) || empty($pillar_id) || empty($owner_id)) {
        $errors[] = "Please fill in all required fields.";
    }
    if (empty($objectives)) {
        $errors[] = "Please select at least one Strategic Objective.";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO initiatives (
                    initiative_code, name, description, pillar_id, owner_user_id, 
                    start_date, due_date, 
                    budget_min, budget_max, approved_budget, budget_item,
                    priority, update_frequency,
                    status_id, progress_percentage, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, 
                    ?, ?, 
                    ?, ?, ?, ?,
                    ?, ?,
                    8, 0, NOW()
                )
            ");
            
            $stmt->execute([
                $code, $name, $description, $pillar_id, $owner_id,
                $start_date, $end_date,
                $budget_min, $budget_max, $approved_budget, $budget_item,
                $priority, $frequency
            ]);
            
            $newInitId = $db->lastInsertId();

            // إدخال الأهداف (تأكد من تعديل الجدول ليكون AUTO_INCREMENT)
            $stmtObj = $db->prepare("INSERT INTO initiative_objectives (initiative_id, strategic_objective_id) VALUES (?, ?)");
            foreach ($objectives as $objId) {
                if(!empty($objId)) {
                    $stmtObj->execute([$newInitId, $objId]);
                }
            }
            
            if (!empty($objectives)) {
                $db->prepare("UPDATE initiatives SET strategic_objective_id = ? WHERE id = ?")->execute([$objectives[0], $newInitId]);
            }

            // إضافة المالك للفريق
            $roleId = 1; // Project Manager
            $db->prepare("INSERT INTO initiative_team (initiative_id, user_id, role_id, is_active) VALUES (?, ?, ?, 1)")
               ->execute([$newInitId, $owner_id, $roleId]);

            $db->commit();
            header("Location: view.php?id=$newInitId&msg=created");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            // رسالة خطأ أوضح للمستخدم
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                 $errors[] = "Database Error: Duplicate entry found. Check if IDs are unique.";
            } else {
                 $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Initiative</title>
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Artistic Theme --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1100px; margin: 0 auto; }

        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }

        .btn-back { background: #fff; border: 1px solid #ddd; color: #555; padding: 10px 20px; border-radius: 30px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .btn-back:hover { background: #f0f0f0; border-color: #ccc; }

        /* Form Styling */
        .form-card { background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; border-top: 5px solid #ff8c00; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full { grid-column: 1 / -1; }

        .form-label { display: block; margin-bottom: 8px; font-weight: 700; color: #2c3e50; font-size: 0.9rem; }
        .form-label i { color: #ff8c00; margin-right: 5px; width: 20px; text-align: center; }

        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 10px; font-family: inherit; font-size: 0.95rem; transition: 0.3s; box-sizing: border-box; background: #fafafa; }
        .form-control:focus { border-color: #ff8c00; background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(255, 140, 0, 0.1); }
        textarea.form-control { resize: vertical; min-height: 120px; }
        
        .readonly-field { background-color: #eee; cursor: not-allowed; color: #777; }

        /* Toggle Budget */
        .budget-toggle { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; background: #fff8e1; padding: 10px 15px; border-radius: 8px; border: 1px solid #ffe0b2; }
        .budget-section { display: none; padding: 15px; border: 1px dashed #ddd; border-radius: 10px; background: #f9f9f9; margin-bottom: 15px; grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #f0f0f0; padding-top: 25px; }
        .btn-submit { background: linear-gradient(135deg, #ff8c00, #e67e00); color: #fff; border: none; padding: 14px 35px; border-radius: 30px; cursor: pointer; font-size: 1rem; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 5px 15px rgba(255, 140, 0, 0.2); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 140, 0, 0.3); }

        .alert-error { background: #fff5f5; color: #c0392b; padding: 15px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #ffe0e0; }

        /* Select2 Customization */
        .select2-container .select2-selection--single, .select2-container .select2-selection--multiple { height: 45px; border-radius: 10px; border: 1px solid #e0e0e0; background: #fafafa; padding: 8px; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: #ff8c00; border: none; color: white; border-radius: 5px; padding: 2px 8px; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-plus-circle"></i> Create Initiative</h1>
        <a href="list.php" class="btn-back">Cancel</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?php foreach ($errors as $e) echo "<div>$e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <div class="form-grid">
            
            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-barcode"></i> Initiative Code</label>
                <input type="text" name="initiative_code" class="form-control readonly-field" readonly value="<?= $defaultCode ?>">
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-tag"></i> Initiative Name <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="Enter initiative name">
            </div>

            <div class="form-group full">
                <label class="form-label"><i class="fa-solid fa-layer-group"></i> Strategic Pillar (Approved Only) <span style="color:red">*</span></label>
                <select name="pillar_id" id="pillar_id" class="form-control" required>
                    <option value="">-- Select Pillar --</option>
                    <?php foreach($pillars as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small style="color:#7f8c8d;">Selection will load related Owners and Objectives.</small>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-user-tie"></i> Initiative Owner <span style="color:red">*</span></label>
                <select name="owner_user_id" id="owner_user_id" class="form-control" required disabled>
                    <option value="">(Select Pillar First)</option>
                </select>
                <small style="color:#e67e22;">Must be a member of the selected Pillar.</small>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-bullseye"></i> Strategic Objectives <span style="color:red">*</span></label>
                <select name="objectives[]" id="objectives" class="form-control" multiple required disabled>
                    <option value="">(Select Pillar First)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-regular fa-calendar"></i> Start Date <span style="color:red">*</span></label>
                <input type="date" name="start_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-regular fa-calendar-check"></i> Due Date <span style="color:red">*</span></label>
                <input type="date" name="due_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-flag"></i> Priority</label>
                <select name="priority" class="form-control">
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-stopwatch"></i> Update Frequency</label>
                <select name="update_frequency" class="form-control">
                    <option value="weekly">Weekly</option>
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                    <option value="every_2_days">Every 2 Days</option>
                </select>
            </div>

            <div class="form-group full">
                <label class="budget-toggle">
                    <input type="checkbox" name="has_budget" id="has_budget" style="width:20px; height:20px;">
                    <span style="font-weight:bold; font-size:1rem; color:#d35400;">Does this initiative require a budget?</span>
                </label>

                <div class="budget-section" id="budget_fields" style="display:none;">
                    <div>
                        <label class="form-label">Min Budget (SAR)</label>
                        <input type="number" name="budget_min" class="form-control" placeholder="0.00">
                    </div>
                    <div>
                        <label class="form-label">Max Budget (SAR)</label>
                        <input type="number" name="budget_max" class="form-control" placeholder="0.00">
                    </div>
                    <div>
                        <label class="form-label">Approved Budget (SAR)</label>
                        <input type="number" name="approved_budget" class="form-control" placeholder="0.00">
                    </div>
                    <div>
                        <label class="form-label">Budget Item/Line</label>
                        <input type="text" name="budget_item" class="form-control" placeholder="e.g. Software License">
                    </div>
                </div>
            </div>

            <div class="form-group full">
                <label class="form-label"><i class="fa-solid fa-align-left"></i> Description</label>
                <textarea name="description" class="form-control" placeholder="Detailed description of the initiative..."></textarea>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">
                Create Initiative <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        // Initialize Select2 for Objectives
        $('#objectives').select2({
            placeholder: "Select Strategic Objectives",
            allowClear: true,
            width: '100%'
        });

        // Toggle Budget Fields
        $('#has_budget').change(function() {
            if(this.checked) {
                $('#budget_fields').slideDown();
            } else {
                $('#budget_fields').slideUp();
                $('#budget_fields input').val(''); // Clear values
            }
        });

        // AJAX: On Pillar Change
        $('#pillar_id').change(function() {
            var pillarId = $(this).val();
            var ownerSelect = $('#owner_user_id');
            var objSelect = $('#objectives');

            if (pillarId) {
                // Loading State
                ownerSelect.prop('disabled', true).html('<option>Loading...</option>');
                objSelect.prop('disabled', true).empty();

                $.ajax({
                    url: 'create.php', // Same file
                    type: 'GET',
                    data: { action: 'get_pillar_details', pillar_id: pillarId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            // Populate Owners
                            ownerSelect.html('<option value="">-- Select Owner --</option>');
                            if (data.users.length > 0) {
                                $.each(data.users, function(i, user) {
                                    ownerSelect.append(`<option value="${user.id}">${user.full_name_en}</option>`);
                                });
                                ownerSelect.prop('disabled', false);
                            } else {
                                ownerSelect.html('<option value="">No active team members found in this Pillar</option>');
                            }

                            // Populate Objectives
                            objSelect.empty();
                            if (data.objectives.length > 0) {
                                $.each(data.objectives, function(i, obj) {
                                    objSelect.append(`<option value="${obj.id}">[${obj.objective_code}] ${obj.objective_text}</option>`);
                                });
                                objSelect.prop('disabled', false);
                            } else {
                                objSelect.append('<option disabled>No objectives found for this pillar</option>');
                            }
                        } else {
                            console.error("AJAX Error:", data.message);
                            alert('Failed to load pillar details: ' + data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("AJAX Request Failed:", status, error);
                        console.log(xhr.responseText); // For debugging
                        alert('Network or Server Error. Check console.');
                        ownerSelect.prop('disabled', true);
                        objSelect.prop('disabled', true);
                    }
                });
            } else {
                ownerSelect.prop('disabled', true).html('<option>(Select Pillar First)</option>');
                objSelect.prop('disabled', true).empty();
            }
        });
    });
</script>

</body>
</html>