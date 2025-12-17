<?php
// modules/pillars/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "functions.php"; // الدوال الخاصة بالركائز

if (!Auth::check() || !Auth::can('create_pillar')) die("Access denied");

$db = Database::getInstance()->pdo();

// جلب المستخدمين للقائمة المنسدلة
$users = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 AND is_deleted=0 ORDER BY full_name_en ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. تجميع البيانات
    $pillar_number = trim($_POST['pillar_number']);
    $name          = trim($_POST['name']);
    $description   = trim($_POST['description']);
    $lead_user_id  = $_POST['lead_user_id'] ?: null;
    $start_date    = $_POST['start_date'] ?: null;
    $end_date      = $_POST['end_date'] ?: null;
    $color         = trim($_POST['color']);
    $icon          = trim($_POST['icon']);

    // 2. التحقق من البيانات (Validation)
    if ($pillar_number === "" || !is_numeric($pillar_number)) {
        $errors[] = "Pillar number is required and must be a number.";
    }
    if ($name === "") {
        $errors[] = "Pillar name is required.";
    }
    if (empty($start_date) || empty($end_date)) {
        $errors[] = "Start Date and End Date are required.";
    }

    // 3. التنفيذ
    if (empty($errors)) {
        try {
            $data = [
                'pillar_number' => $pillar_number,
                'name'          => $name,
                'description'   => $description,
                'lead_user_id'  => $lead_user_id,
                'start_date'    => $start_date,
                'end_date'      => $end_date,
                'color'         => $color,
                'icon'          => $icon
                // الحالة ستكون Draft تلقائياً داخل دالة createPillar
            ];

            // استدعاء دالة الإنشاء من functions.php
            $newId = createPillar($data);

            if ($newId) {
                // ✅ إضافة القائد كأول عضو في الفريق تلقائياً
                if (!empty($lead_user_id)) {
                    addPillarMember($newId, $lead_user_id);
                }

                $_SESSION['toast_success'] = "Pillar draft created. Please add objectives and team members.";
                
                // ✅ التوجيه إلى صفحة العرض (View) بدلاً من القائمة
                header("Location: view.php?id=$newId");
                exit;
            } else {
                $errors[] = "Failed to create pillar record.";
            }

        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $_SESSION['toast_error'] = "This pillar number already exists.";
            } else {
                $_SESSION['toast_error'] = "An unexpected error occurred: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Pillar</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/toast.css">
    <link rel="stylesheet" href="css/form.css"> <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        /* تنسيقات إضافية لضمان تطابق الشكل */
        .form-card { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 0.9rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; transition: 0.2s; box-sizing: border-box; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3498db; outline: none; box-shadow: 0 0 0 3px rgba(52,152,219,0.1); }
        
        .icon-picker-input { border: 1px solid #ddd; padding: 10px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 10px; background: #fff; }
        .icon-picker-input:hover { background: #f9f9f9; }
        
        /* Icon Modal */
        .icon-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .icon-modal-content { background: #fff; width: 500px; max-height: 80vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; }
        .icon-modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; padding: 20px; overflow-y: auto; }
        .icon-item { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 10px; border-radius: 6px; cursor: pointer; border: 1px solid transparent; }
        .icon-item:hover { background: #f0f8ff; border-color: #cceeff; color: #3498db; }
        .icon-item i { font-size: 1.5rem; }
        .icon-item span { font-size: 0.7rem; color: #777; text-align: center; word-break: break-all; }
        #iconSearch { margin: 10px 20px; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }

        .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 20px; }
        .btn-submit { background: #3498db; color: #fff; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; }
        .btn-submit:hover { background: #2980b9; }
        .btn-cancel { background: #f1f1f1; color: #333; text-decoration: none; padding: 10px 20px; border-radius: 6px; margin-left: 10px; font-weight: 600; }
        .btn-cancel:hover { background: #e0e0e0; }
        
        .alert-error { background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fca5a5; }
        .note-hint { text-align: center; color: #777; margin-top: 20px; font-size: 0.9rem; font-style: italic; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-container">

            <h1 class="page-title" style="margin-bottom: 20px;"><i class="fa-solid fa-plus"></i> Add New Pillar</h1>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?>
                    <p>• <?= $e ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="form-card">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Pillar Number <span style="color:red">*</span></label>
                        <input type="number" name="pillar_number" required placeholder="e.g. 1">
                    </div>

                    <div class="form-group">
                        <label>Pillar Name <span style="color:red">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Operational Excellence">
                    </div>

                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" placeholder="Enter pillar description..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Pillar Lead</label>
                        <select name="lead_user_id">
                            <option value="">— Select User —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name_en']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" value="Draft" disabled style="background:#eee; color:#777;">
                    </div>

                    <div class="form-group">
                        <label>Color</label>
                        <input class="color" type="color" name="color" value="#3498db" style="height: 42px; padding: 2px;">
                    </div>

                    <div class="form-group">
                        <label>Icon</label>
                        <input type="hidden" name="icon" id="icon" value="fa-building">
                        <div class="icon-picker-input" onclick="openIconPicker()">
                            <i id="selectedIconPreview" class="fa-solid fa-building" style="font-size: 1.2rem; color: #555;"></i>
                            <span id="selectedIconText">fa-building</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Start Date <span style="color:red">*</span></label>
                        <input type="date" name="start_date" required>
                    </div>

                    <div class="form-group">
                        <label>End Date <span style="color:red">*</span></label>
                        <input type="date" name="end_date" required>
                    </div>

                </div>

                <div class="form-actions">
                    <button class="btn-submit" type="submit">Create Draft & Continue</button>
                    <a href="list.php" class="btn-cancel">Cancel</a>
                </div>

            </form>

            <p class="note-hint">
                <i class="fa-solid fa-circle-info"></i> After creating the pillar, you will be redirected to add strategic objectives and team members.
            </p>

        </div>
    </div>
</div>

<div id="iconPickerModal" class="icon-modal">
    <div class="icon-modal-content">
        <div class="icon-modal-header">
            <h3 style="margin:0;">Select an Icon</h3>
            <span onclick="closeIconPicker()" class="close-btn" style="cursor:pointer; font-size:1.5rem;">&times;</span>
        </div>
        <input type="text" id="iconSearch" placeholder="Search icons..." onkeyup="filterIcons()">
        <div class="icon-grid" id="iconGrid"></div>
    </div>
</div>

<script>
const iconList = [
    "fa-building","fa-briefcase","fa-chart-line","fa-coins","fa-sitemap","fa-diagram-project",
    "fa-folder-tree","fa-industry","fa-handshake","fa-landmark",
    "fa-layer-group","fa-bars","fa-sliders","fa-gear","fa-pen","fa-pencil","fa-trash",
    "fa-check","fa-circle-info","fa-bell","fa-flag","fa-bookmark","fa-list","fa-filter",
    "fa-users","fa-user","fa-user-group","fa-user-tie","fa-user-shield","fa-user-plus",
    "fa-person-chalkboard","fa-people-group",
    "fa-book","fa-book-open","fa-graduation-cap","fa-lightbulb","fa-chalkboard",
    "fa-clock","fa-calendar","fa-hourglass","fa-check-circle","fa-hourglass-half",
    "fa-spinner","fa-gauge","fa-circle-notch",
    "fa-file","fa-file-lines","fa-file-circle-check","fa-folder","fa-folder-open",
    "fa-database","fa-server",
    "fa-chart-pie","fa-chart-simple","fa-percent","fa-scale-balanced","fa-arrow-trend-up",
    "fa-bullseye","fa-flag-checkered","fa-mountain-sun","fa-road","fa-compass",
    "fa-laptop-code","fa-microchip","fa-wifi","fa-code","fa-circle-nodes",
    "fa-star","fa-heart","fa-qrcode","fa-triangle-exclamation",
    "fa-tree","fa-globe","fa-map","fa-map-location-dot",
    "fa-chess","fa-chess-rook","fa-chess-queen","fa-chess-knight"
];

function buildIconGrid() {
    const grid = document.getElementById("iconGrid");
    grid.innerHTML = "";
    iconList.forEach(icon => {
        const el = document.createElement("div");
        el.className = "icon-item";
        el.setAttribute("data-icon", icon);
        el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${icon}</span>`;
        el.onclick = () => selectIcon(icon);
        grid.appendChild(el);
    });
}
buildIconGrid();

function selectIcon(icon) {
    document.getElementById("icon").value = icon;
    document.getElementById("selectedIconPreview").className = "fa-solid " + icon;
    document.getElementById("selectedIconText").innerText = icon;
    closeIconPicker();
}

function openIconPicker() {
    document.getElementById("iconPickerModal").style.display = "flex";
}
function closeIconPicker() {
    document.getElementById("iconPickerModal").style.display = "none";
}
function filterIcons() {
    const term = document.getElementById("iconSearch").value.toLowerCase();
    document.querySelectorAll(".icon-item").forEach(item => {
        const icon = item.getAttribute("data-icon").toLowerCase();
        item.style.display = icon.includes(term) ? "flex" : "none";
    });
}

// Close modal if clicked outside
window.onclick = function(event) {
    const modal = document.getElementById("iconPickerModal");
    if (event.target == modal) {
        closeIconPicker();
    }
}
</script>

<script src="../../assets/js/toast.js"></script>

<?php if (!empty($_SESSION['toast_success'])): ?>
<script> showToast("<?= $_SESSION['toast_success'] ?>", "success"); </script>
<?php unset($_SESSION['toast_success']); endif; ?>

<?php if (!empty($_SESSION['toast_error'])): ?>
<script> showToast("<?= $_SESSION['toast_error'] ?>", "error"); </script>
<?php unset($_SESSION['toast_error']); endif; ?>

</body>
</html>