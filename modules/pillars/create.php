<?php
// modules/pillars/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/init.php";
require_once "functions.php"; 

if (!Auth::check() || !Auth::can('create_pillar')) die("Access denied");

$db = Database::getInstance()->pdo();

// جلب المستخدمين للقائمة
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

    // 2. التحقق
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
            ];

            $newId = createPillar($data);

            if ($newId) {
                // ✅ التصحيح هنا: إضافة العضو القائد مع تحديد الدور
                if (!empty($lead_user_id)) {
                    // البحث عن ID دور "Chair" أو استخدام 1 كافتراضي
                    // يفضل التأكد من قاعدة البيانات لديك عن رقم الدور الصحيح
                    // سأفترض هنا أن الدور "Chair" أو "Leader" هو رقم 1، أو يمكنك تغييره حسب جدول pillar_roles
                    $chairRoleId = 1; 
                    
                    // يمكنك محاولة جلبه ديناميكياً إذا أردت دقة أكبر:
                    // $chairRoleId = $db->query("SELECT id FROM pillar_roles WHERE name LIKE '%Chair%' LIMIT 1")->fetchColumn() ?: 1;

                    addPillarMember($newId, $lead_user_id, $chairRoleId); 
                }

                $_SESSION['toast_success'] = "Pillar draft created. Please add objectives.";
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
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Premium Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1000px; margin: 0 auto; }

        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }
        
        .btn-back { 
            background: #fff; border: 1px solid #ddd; color: #555; padding: 10px 20px; 
            border-radius: 30px; font-weight: 600; text-decoration: none; transition: 0.2s; 
        }
        .btn-back:hover { background: #f0f0f0; border-color: #ccc; }

        /* Form Card */
        .form-card { 
            background: #fff; padding: 35px; border-radius: 16px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0; 
        }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: 1 / -1; }
        
        .form-group label { display: block; margin-bottom: 8px; font-weight: 700; color: #2c3e50; font-size: 0.9rem; }
        .form-control { 
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; 
            font-family: inherit; font-size: 0.95rem; transition: 0.2s; box-sizing: border-box; 
            background: #fafafa;
        }
        .form-control:focus { border-color: #ff8c00; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1); }
        textarea.form-control { resize: vertical; min-height: 100px; }

        /* Icon Picker */
        .icon-picker-input { 
            border: 1px solid #ddd; padding: 12px; border-radius: 8px; cursor: pointer; 
            display: flex; align-items: center; gap: 15px; background: #fafafa; transition: 0.2s;
        }
        .icon-picker-input:hover { background: #fff; border-color: #ff8c00; }
        
        /* Modal Styles */
        .icon-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(3px); align-items: center; justify-content: center; }
        .icon-modal-content { background: #fff; width: 600px; max-height: 80vh; border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        .icon-modal-header { padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #fdfdfd; }
        .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(70px, 1fr)); gap: 10px; padding: 25px; overflow-y: auto; background: #fafafa; }
        .icon-item { 
            display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 15px 10px; 
            border-radius: 8px; cursor: pointer; border: 1px solid #eee; background: #fff; transition: 0.2s; 
        }
        .icon-item:hover { border-color: #ff8c00; transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .icon-item i { font-size: 1.5rem; color: #555; }
        .icon-item span { font-size: 0.7rem; color: #888; text-align: center; word-break: break-all; }
        
        #iconSearch { margin: 15px 25px; padding: 12px; border: 1px solid #ddd; border-radius: 8px; width: calc(100% - 50px); }

        /* Actions */
        .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #f0f0f0; padding-top: 25px; }
        .btn-submit { 
            background: linear-gradient(135deg, #ff8c00, #e67e00); color: #fff; border: none; 
            padding: 12px 30px; border-radius: 30px; cursor: pointer; font-size: 1rem; font-weight: 700; 
            transition: 0.2s; display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3); }

        .alert-error { background: #ffebee; color: #c0392b; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #ffcdd2; display: flex; align-items: center; gap: 10px; }
        .note-hint { text-align: center; color: #95a5a6; margin-top: 25px; font-size: 0.9rem; font-style: italic; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title"><i class="fa-solid fa-plus-circle"></i> Add Strategic Pillar</h1>
            <a href="list.php" class="btn-back">Cancel</a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <div>
                <?php foreach ($errors as $e): ?>
                    <div><?= $e ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" class="form-card">
            <div class="form-grid">

                <div class="form-group">
                    <label>Pillar Number <span style="color:red">*</span></label>
                    <input type="number" name="pillar_number" class="form-control" required placeholder="e.g. 1" min="1">
                </div>

                <div class="form-group">
                    <label>Pillar Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Operational Excellence">
                </div>

                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description" class="form-control" placeholder="Enter pillar description..."></textarea>
                </div>

                <div class="form-group">
                    <label>Pillar Lead</label>
                    <select name="lead_user_id" class="form-control">
                        <option value="">— Select User —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name_en']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <input type="text" value="Draft (Initial)" class="form-control" disabled style="background:#eee; color:#777; cursor:not-allowed;">
                </div>

                <div class="form-group">
                    <label>Theme Color</label>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="color" name="color" value="#ff8c00" style="width:50px; height:45px; border:none; border-radius:6px; cursor:pointer; padding:0;">
                        <span style="font-size:0.85rem; color:#777;">Pick a color for reports</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>Icon</label>
                    <input type="hidden" name="icon" id="icon" value="fa-building">
                    <div class="icon-picker-input" onclick="openIconPicker()">
                        <div style="width:40px; height:40px; background:#ff8c00; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white;">
                            <i id="selectedIconPreview" class="fa-solid fa-building"></i>
                        </div>
                        <div>
                            <div style="font-weight:bold; color:#333;">Select Icon</div>
                            <span id="selectedIconText" style="font-size:0.8rem; color:#777;">fa-building</span>
                        </div>
                        <i class="fa-solid fa-chevron-down" style="margin-left:auto; color:#ccc;"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Start Date <span style="color:red">*</span></label>
                    <input type="date" name="start_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>End Date <span style="color:red">*</span></label>
                    <input type="date" name="end_date" class="form-control" required>
                </div>

            </div>

            <div class="form-actions">
                <button class="btn-submit" type="submit">
                    Create Draft & Continue <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>

        </form>

        <p class="note-hint">
            <i class="fa-solid fa-circle-info"></i> After creating the pillar, you will be redirected to add strategic objectives and team members.
        </p>

    </div>
</div>

<div id="iconPickerModal" class="icon-modal">
    <div class="icon-modal-content">
        <div class="icon-modal-header">
            <h3 style="margin:0; font-size:1.2rem;">Select an Icon</h3>
            <span onclick="closeIconPicker()" class="close-btn" style="cursor:pointer; font-size:1.5rem; color:#999;">&times;</span>
        </div>
        <input type="text" id="iconSearch" placeholder="Search icons (e.g. chart, user)..." onkeyup="filterIcons()">
        <div class="icon-grid" id="iconGrid"></div>
    </div>
</div>

<script>
// --- Icon Picker JS (Same as before) ---
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
        el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${icon.replace('fa-', '')}</span>`;
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
    document.getElementById("iconSearch").focus();
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

<script src="../../assets/js/toast.js"></script>
<?php if (!empty($_SESSION['toast_error'])): ?>
<script> showToast("<?= $_SESSION['toast_error'] ?>", "error"); </script>
<?php unset($_SESSION['toast_error']); endif; ?>

</body>
</html>