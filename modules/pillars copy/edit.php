<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "pillar_status_service.php";

if (!Auth::check()) die("Access denied");

$db = Database::getInstance()->pdo();

// هل المستخدم سوبر أدمن؟
$isSuperAdmin = ($_SESSION['role_key'] ?? '') === 'super_admin';

// جلب ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// جلب بيانات الركيزة
$stmt = $db->prepare("SELECT * FROM pillars WHERE id = ?");
$stmt->execute([$id]);
$pillar = $stmt->fetch();

if (!$pillar) {
    $_SESSION['error'] = "Pillar not found.";
    header("Location: list.php");
    exit;
}

// جلب الحالات
$statuses = $db->query("SELECT * FROM pillar_statuses ORDER BY sort_order ASC")->fetchAll();

// جلب ال Leads
$users = $db->query("SELECT id, full_name_en FROM users ORDER BY full_name_en ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pillar_number = trim($_POST['pillar_number']);
    $name          = trim($_POST['name']);
    $description   = trim($_POST['description']);
    $lead_user_id  = $_POST['lead_user_id'] ?: null;

    // إذا المستخدم ليس سوبر ادمن – تجاهل الـ POST وخليه نفس القديم
    $status_id = $isSuperAdmin ? ($_POST['status_id'] ?? $pillar['status_id']) : $pillar['status_id'];

    $color       = trim($_POST['color']);
    $icon        = trim($_POST['icon']);
    $start_date  = $_POST['start_date'] ?: null;
    $end_date    = $_POST['end_date'] ?: null;

    if ($pillar_number === "" || !is_numeric($pillar_number)) {
        $errors[] = "Pillar number is required and must be numeric.";
    }

    if ($name === "") {
        $errors[] = "Pillar name is required.";
    }

    if (empty($errors)) {

        $update = $db->prepare("
            UPDATE pillars
            SET pillar_number=?, name=?, description=?, lead_user_id=?, status_id=?, color=?, icon=?, start_date=?, end_date=?
            WHERE id=?
        ");

        $update->execute([
            $pillar_number,
            $name,
            $description,
            $lead_user_id,
            $status_id,
            $color,
            $icon,
            $start_date,
            $end_date,
            $id
        ]);

        // تحديث حالة الركيزة تلقائيًا بناءً على المبادرات
        PillarStatusService::updatePillarStatusFromInitiatives($id);

        $_SESSION['success'] = "Pillar updated successfully.";
        header("Location: list.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Pillar</title>

    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="css/form.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>

<body style="margin:0;">
<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-container">
            <h1 class="page-title"><i class="fa-solid fa-pen"></i> Edit Pillar</h1>

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
                        <label>Pillar Number</label>
                        <input type="number" name="pillar_number" value="<?= $pillar['pillar_number'] ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Pillar Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($pillar['name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"><?= htmlspecialchars($pillar['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Pillar Lead</label>
                        <select name="lead_user_id">
                            <option value="">— None —</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $pillar['lead_user_id'] == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name_en']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Pillar Status</label>
                        <select name="status_id" <?= $isSuperAdmin ? '' : 'disabled' ?>>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $pillar['status_id'] == $s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Color</label>
                        <input class="color" type="color" name="color" value="<?= $pillar['color'] ?>">
                    </div>

                    <div class="form-group full">
                        <label>Icon</label>

                        <input type="hidden" name="icon" id="icon" value="<?= $pillar['icon'] ?>">

                        <div class="icon-picker-input" onclick="openIconPicker()">
                            <i id="selectedIconPreview" class="fa-solid <?= $pillar['icon'] ?>"></i>
                            <span id="selectedIconText"><?= $pillar['icon'] ?></span>
                        </div>
                    </div>

                    <!-- Modal اختيار الايقونات -->
                    <div id="iconPickerModal" class="icon-modal">
                        <div class="icon-modal-content">
                            <div class="icon-modal-header">
                                <h3>Select an Icon</h3>
                                <button onclick="closeIconPicker()" class="close-btn">&times;</button>
                            </div>

                            <input type="text" id="iconSearch" placeholder="Search icons..." onkeyup="filterIcons()">

                            <div class="icon-grid" id="iconGrid"></div>
                        </div>
                    </div>


                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" value="<?= $pillar['start_date'] ?>">
                    </div>

                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" value="<?= $pillar['end_date'] ?>">
                    </div>

                </div>

                <div class="form-actions">
                    <button class="btn-submit" type="submit">Update Pillar</button>
                    <a href="list.php" class="btn-cancel">Cancel</a>
                </div>

            </form>

        </div>

    </div>
</div>

<script>
// نفس قائمة الايقونات
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

        el.innerHTML = `
            <i class="fa-solid ${icon}"></i>
            <span>${icon}</span>
        `;

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
        item.style.display = item.getAttribute("data-icon").toLowerCase().includes(term)
            ? "flex"
            : "none";
    });
}
</script>

</body>
</html>
