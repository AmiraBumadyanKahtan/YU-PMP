<?php
// modules/pillars/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$db = Database::getInstance()->pdo();

// 1. جلب بيانات الركيزة
$pillar = getPillarById($id);
if (!$pillar) die("Pillar not found");

// 2. التحقق من الحالة والصلاحيات
$isApproved   = ($pillar['status_id'] == 11); // 11 = Approved
$isSuperAdmin = ($_SESSION['role_key'] == 'super_admin');
$isStrategy   = ($_SESSION['role_key'] == 'strategy_office');
$isLead       = ($pillar['lead_user_id'] == $_SESSION['user_id']);
$isDraft      = ($pillar['status_id'] == 12 || $pillar['status_id'] == 6); 

// السماح بالدخول: للسوبر أدمن، الاستراتيجية، أو القائد (فقط إذا كانت مسودة)
if (!$isSuperAdmin && !$isStrategy && !($isLead && $isDraft)) {
    die("Access Denied: You do not have permission to edit this pillar.");
}

// 3. جلب القوائم
$users = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 ORDER BY full_name_en")->fetchAll();

// 4. معالجة الحفظ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // البيانات التي يمكن تعديلها دائماً
    $description  = $_POST['description'];
    $lead_user_id = $_POST['lead_user_id'];
    $color        = $_POST['color'];
    $icon         = $_POST['icon'];

    // البيانات المقيدة (نأخذها من القاعدة إذا كانت الحالة "Approved" ولم يكن المستخدم سوبر أدمن)
    // (ملاحظة: السوبر أدمن عادة يُستثنى، لكن بناءً على طلبك "مش مسموح تتغير"، سنطبق القيد على الجميع لضمان البيانات)
    if ($isApproved) {
        $name       = $pillar['name'];       // إبقاء الاسم القديم
        $start_date = $pillar['start_date']; // إبقاء التاريخ القديم
        $end_date   = $pillar['end_date'];   // إبقاء التاريخ القديم
    } else {
        $name       = $_POST['name'];
        $start_date = $_POST['start_date'];
        $end_date   = $_POST['end_date'];
    }

    // الرقم ثابت دائماً (نأخذه من القاعدة الأصلية)
    $pillar_number = $pillar['pillar_number']; 

    $data = [
        'pillar_number' => $pillar_number,
        'name'          => $name,
        'description'   => $description,
        'lead_user_id'  => $lead_user_id,
        'start_date'    => $start_date,
        'end_date'      => $end_date,
        'color'         => $color,
        'icon'          => $icon
    ];

    if (updatePillar($id, $data)) {
        $_SESSION['toast_success'] = "Pillar updated successfully.";
        header("Location: view.php?id=$id");
        exit;
    } else {
        $error = "Failed to update pillar.";
    }
}

// --- إعداد خصائص الحقول (Readonly Logic) ---
// 1. حقول ثابتة دائماً (رقم الركيزة + الحالة)
$fixedAttr = 'readonly style="background-color: #eee; cursor: not-allowed; color: #777;"';

// 2. حقول تقفل بعد الموافقة (الاسم + التواريخ)
// نضيف pointer-events: none للتواريخ لمنع ظهور الـ Calendar Popup
$lockedAttr = $isApproved ? 'readonly style="background-color: #f9f9f9; cursor: not-allowed; pointer-events: none;"' : '';
$lockedClass = $isApproved ? 'locked-field' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit: <?= htmlspecialchars($pillar['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        body { background-color: #fdfbf7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 0 10px; }
        .page-title { font-size: 1.8rem; color: #d35400; font-weight: bold; margin: 0; }

        .form-card { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); max-width: 900px; margin: 0 auto; border-top: 5px solid <?= $pillar['color'] ?>; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { margin-bottom: 5px; }
        .form-group.full { grid-column: 1 / -1; }
        
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem; box-sizing: border-box; transition: 0.2s; background-color: #fcfcfc; }
        .form-control:focus { border-color: #e67e00; outline: none; background-color: #fff; box-shadow: 0 0 0 3px rgba(230, 126, 0, 0.1); }
        
        /* Icon Picker Style */
        .icon-picker-input { border: 1px solid #ddd; padding: 12px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 12px; background: #fcfcfc; transition: 0.2s; }
        .icon-picker-input:hover { background: #fff; border-color: #ccc; }
        
        /* Buttons */
        .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #eee; padding-top: 25px; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-save { background-color: #e67e00; color: #fff; border: none; padding: 12px 30px; border-radius: 6px; cursor: pointer; font-size: 1rem; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .btn-save:hover { background-color: #cf7100; }
        .btn-cancel { background-color: #f5f5f5; color: #555; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; border: 1px solid #ddd; }
        .btn-cancel:hover { background-color: #eee; }

        /* Lock Icon for Readonly Fields */
        .input-wrapper { position: relative; }
        .input-wrapper .fa-lock { position: absolute; right: 10px; top: 12px; color: #999; font-size: 0.8rem; }

        /* Modal for Icon */
        .icon-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; backdrop-filter: blur(2px); }
        .icon-modal-content { background: #fff; width: 500px; max-height: 80vh; border-radius: 12px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .icon-modal-header { padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f9f9f9; }
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(60px, 1fr)); gap: 10px; padding: 20px; overflow-y: auto; }
        .icon-item { display: flex; flex-direction: column; align-items: center; gap: 5px; padding: 10px; border-radius: 6px; cursor: pointer; border: 1px solid transparent; transition: 0.2s; }
        .icon-item:hover { background: #fff3e0; border-color: #ffe0b2; color: #e67e00; }
    </style>
</head>

<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper" style="padding: 30px;">

        <div class="page-header">
            <h1 class="page-title"><i class="fa-solid fa-pen-to-square"></i> Edit Pillar</h1>
        </div>

        <?php if(isset($error)): ?>
            <div style="background:#fee2e2; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if($isApproved): ?>
            <div style="background:#e3f2fd; color:#0d47a1; padding:12px; border-radius:6px; margin-bottom:20px; font-size:0.9rem; border-left:4px solid #1976d2;">
                <i class="fa-solid fa-circle-info"></i> This pillar is <strong>Approved</strong>. Core fields (Name, Duration) are locked.
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label>Pillar Number</label>
                        <div class="input-wrapper">
                            <input type="number" name="pillar_number" class="form-control" <?= $fixedAttr ?> value="<?= htmlspecialchars($pillar['pillar_number']) ?>">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Pillar Name <span style="color:red">*</span></label>
                        <div class="input-wrapper">
                            <input type="text" name="name" class="form-control <?= $lockedClass ?>" <?= $lockedAttr ?> required value="<?= htmlspecialchars($pillar['name']) ?>">
                            <?php if($isApproved): ?><i class="fa-solid fa-lock"></i><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" rows="4" class="form-control"><?= htmlspecialchars($pillar['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Pillar Lead</label>
                        <select name="lead_user_id" class="form-control" required>
                            <option value="">-- Select Leader --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($pillar['lead_user_id'] == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['full_name_en']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <div class="input-wrapper">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($pillar['status_name']) ?>" <?= $fixedAttr ?>>
                            <i class="fa-solid fa-lock"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Start Date</label>
                        <div class="input-wrapper">
                            <input type="date" name="start_date" class="form-control <?= $lockedClass ?>" <?= $lockedAttr ?> required value="<?= $pillar['start_date'] ?>">
                            <?php if($isApproved): ?><i class="fa-solid fa-lock"></i><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>End Date</label>
                        <div class="input-wrapper">
                            <input type="date" name="end_date" class="form-control <?= $lockedClass ?>" <?= $lockedAttr ?> required value="<?= $pillar['end_date'] ?>">
                            <?php if($isApproved): ?><i class="fa-solid fa-lock"></i><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Color Code</label>
                        <div style="display:flex; gap:10px;">
                            <input type="color" name="color" class="form-control" style="height:45px; width:60px; padding:2px;" value="<?= $pillar['color'] ?>">
                            <input type="text" class="form-control" value="<?= $pillar['color'] ?>" readonly style="background:#f9f9f9;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Icon</label>
                        <input type="hidden" name="icon" id="icon" value="<?= $pillar['icon'] ?>">
                        <div class="icon-picker-input" onclick="openIconPicker()">
                            <i id="selectedIconPreview" class="fa-solid <?= $pillar['icon'] ?>" style="font-size: 1.4rem; color: <?= $pillar['color'] ?>;"></i>
                            <span id="selectedIconText"><?= $pillar['icon'] ?></span>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="view.php?id=<?= $id ?>" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-save"><i class="fa-regular fa-floppy-disk"></i> Save Changes</button>
                </div>
            </form>
        </div>

    </div>
</div>

<div id="iconPickerModal" class="icon-modal">
    <div class="icon-modal-content">
        <div class="icon-modal-header">
            <h3 style="margin:0; color:#333;">Select Icon</h3>
            <span onclick="closeIconPicker()" style="cursor:pointer; font-size:1.5rem; color:#999;">&times;</span>
        </div>
        <input type="text" id="iconSearch" placeholder="Search icons..." onkeyup="filterIcons()" style="margin:15px 20px; padding:10px; border:1px solid #ddd; border-radius:6px; display:block;">
        <div class="icon-grid" id="iconGrid"></div>
    </div>
</div>

<script>
// --- كود الأيقونات (نفس السابق) ---
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

window.onclick = function(event) {
    const modal = document.getElementById("iconPickerModal");
    if (event.target == modal) {
        closeIconPicker();
    }
}
</script>

</body>
</html>