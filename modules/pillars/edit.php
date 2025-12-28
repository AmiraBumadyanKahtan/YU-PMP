<?php
// modules/pillars/edit.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php";

if (!Auth::check()) die("Access Denied");

$id = $_GET['id'] ?? 0;
$db = Database::getInstance()->pdo();

// 1. Get Data
$pillar = getPillarById($id);
if (!$pillar) die("Pillar not found");

// 2. Checks
$isApproved   = ($pillar['status_id'] == 11); 
$isSuperAdmin = ($_SESSION['role_key'] == 'super_admin');
$isStrategy   = ($_SESSION['role_key'] == 'strategy_office');
$isLead       = ($pillar['lead_user_id'] == $_SESSION['user_id']);
$isDraft      = ($pillar['status_id'] == 12 || $pillar['status_id'] == 6); 

if (!$isSuperAdmin && !$isStrategy && !($isLead && $isDraft)) {
    die("Access Denied: You do not have permission to edit this pillar.");
}

// 3. Dropdowns
$users = $db->query("SELECT id, full_name_en FROM users WHERE is_active=1 ORDER BY full_name_en")->fetchAll();

// 4. Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $description  = $_POST['description'];
    $lead_user_id = $_POST['lead_user_id'];
    $color        = $_POST['color'];
    $icon         = $_POST['icon'];

    if ($isApproved) {
        $name       = $pillar['name'];
        $start_date = $pillar['start_date'];
        $end_date   = $pillar['end_date'];
    } else {
        $name       = $_POST['name'];
        $start_date = $_POST['start_date'];
        $end_date   = $_POST['end_date'];
    }

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

// Readonly Logic
$fixedAttr = 'readonly style="background-color: #f0f2f5; cursor: not-allowed; color: #7f8c8d;"';
$lockedAttr = $isApproved ? 'readonly style="background-color: #f0f2f5; cursor: not-allowed; pointer-events: none;"' : '';
$lockedClass = $isApproved ? 'locked-field' : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit: <?= htmlspecialchars($pillar['name']) ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <style>
        /* --- Premium Theme Styles --- */
        body { font-family: "Varela Round", sans-serif; background-color: #fcfcfc; margin: 0; color: #444; }
        .page-wrapper { padding: 2rem; max-width: 1000px; margin: 0 auto; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 2rem; font-weight: 700; color: #ff8c00; margin: 0; display: flex; align-items: center; gap: 12px; }
        
        /* Form Card */
        .form-card { 
            background: #fff; padding: 40px; border-radius: 16px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #f0f0f0;
            border-top: 6px solid <?= $pillar['color'] ?>; /* Dynamic Top Border */
        }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
        .form-group { margin-bottom: 10px; }
        .form-group.full { grid-column: 1 / -1; }
        
        .form-group label { display: block; margin-bottom: 8px; font-weight: 700; color: #2c3e50; font-size: 0.9rem; }
        .form-control { 
            width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; 
            font-family: inherit; font-size: 0.95rem; transition: 0.2s; box-sizing: border-box; 
            background: #fafafa;
        }
        .form-control:focus { border-color: #ff8c00; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1); }
        
        /* Input Wrapper for Icons */
        .input-wrapper { position: relative; }
        .input-wrapper .fa-lock { position: absolute; right: 15px; top: 14px; color: #b2bec3; font-size: 0.9rem; }

        /* Icon Picker */
        .icon-picker-input { 
            border: 1px solid #ddd; padding: 12px; border-radius: 8px; cursor: pointer; 
            display: flex; align-items: center; gap: 15px; background: #fafafa; transition: 0.2s;
        }
        .icon-picker-input:hover { background: #fff; border-color: #ff8c00; }
        
        /* Actions */
        .form-actions { margin-top: 30px; text-align: right; border-top: 1px solid #f0f0f0; padding-top: 25px; display: flex; justify-content: flex-end; gap: 10px; }
        .btn-save { 
            background: linear-gradient(135deg, #ff8c00, #e67e00); color: #fff; border: none; 
            padding: 12px 30px; border-radius: 30px; cursor: pointer; font-size: 1rem; font-weight: 700; 
            transition: 0.2s; display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3); }
        
        .btn-cancel { 
            background: #fff; border: 1px solid #ddd; color: #555; padding: 12px 25px; 
            border-radius: 30px; font-weight: 600; text-decoration: none; transition: 0.2s; 
        }
        .btn-cancel:hover { background: #f0f0f0; border-color: #ccc; }

        /* Icon Modal */
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

        /* Alert */
        .info-alert { 
            background: #e3f2fd; color: #0d47a1; padding: 15px; border-radius: 8px; 
            margin-bottom: 25px; border: 1px solid #bbdefb; display: flex; align-items: center; gap: 10px; 
            font-size: 0.95rem; font-weight: 500;
        }
    </style>
</head>

<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header">
            <h1 class="page-title"><i class="fa-solid fa-pen-to-square"></i> Edit Pillar</h1>
        </div>

        <?php if(isset($error)): ?>
            <div style="background:#fee2e2; color:#c62828; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #ef9a9a;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if($isApproved): ?>
            <div class="info-alert">
                <i class="fa-solid fa-circle-info" style="font-size:1.2rem;"></i>
                <div>This pillar is <strong>Approved</strong>. Core fields (Name, Duration) are locked to maintain integrity.</div>
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
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="color" name="color" value="<?= $pillar['color'] ?>" style="width:50px; height:45px; border:none; border-radius:8px; cursor:pointer; padding:0;">
                            <input type="text" class="form-control" value="<?= $pillar['color'] ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Icon</label>
                        <input type="hidden" name="icon" id="icon" value="<?= $pillar['icon'] ?>">
                        <div class="icon-picker-input" onclick="openIconPicker()">
                            <div style="width:40px; height:40px; background:<?= $pillar['color'] ?>; border-radius:8px; display:flex; align-items:center; justify-content:center; color:white;">
                                <i id="selectedIconPreview" class="fa-solid <?= $pillar['icon'] ?>"></i>
                            </div>
                            <div>
                                <div style="font-weight:bold; color:#333;">Select Icon</div>
                                <span id="selectedIconText" style="font-size:0.8rem; color:#777;"><?= $pillar['icon'] ?></span>
                            </div>
                            <i class="fa-solid fa-chevron-down" style="margin-left:auto; color:#ccc;"></i>
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
            <h3 style="margin:0; font-size:1.2rem;">Select an Icon</h3>
            <span onclick="closeIconPicker()" class="close-btn" style="cursor:pointer; font-size:1.5rem; color:#999;">&times;</span>
        </div>
        <input type="text" id="iconSearch" placeholder="Search icons..." onkeyup="filterIcons()" style="margin:15px 25px; padding:12px; border:1px solid #ddd; border-radius:8px; width:calc(100% - 50px);">
        <div class="icon-grid" id="iconGrid"></div>
    </div>
</div>

<script>
// --- Icon Logic (Same as create.php) ---
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

</body>
</html>