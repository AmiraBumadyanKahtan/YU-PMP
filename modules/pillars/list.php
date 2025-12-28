<?php
// modules/pillars/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php"; 

if (!Auth::check()) die("Access Denied");

// ✅ تحديث البيانات لضمان دقة الأرقام
$db = Database::getInstance()->pdo();
$allPillarIds = $db->query("SELECT id FROM pillars WHERE is_deleted=0")->fetchAll(PDO::FETCH_COLUMN);
foreach ($allPillarIds as $pId) {
    if (function_exists('updatePillarStatusAutomatic')) {
        updatePillarStatusAutomatic($pId);
    }
}

// استقبال الفلاتر
$search = $_GET['search'] ?? '';
$status_id = $_GET['status_id'] ?? '';

// جلب البيانات مع الفلاتر
$sql = "SELECT p.*, u.full_name_en as lead_name, s.name as status_name, s.color as status_color,
               (SELECT COUNT(*) FROM initiatives WHERE pillar_id = p.id AND is_deleted=0) as initiatives_count
        FROM pillars p
        LEFT JOIN users u ON u.id = p.lead_user_id
        LEFT JOIN pillar_statuses s ON s.id = p.status_id
        WHERE (p.is_deleted = 0 OR p.is_deleted IS NULL)";

$params = [];
if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status_id) {
    $sql .= " AND p.status_id = ?";
    $params[] = $status_id;
}
$sql .= " ORDER BY p.pillar_number ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$pillars = $stmt->fetchAll();

$statuses = $db->query("SELECT * FROM pillar_statuses")->fetchAll();

// معالجة الحذف
if (isset($_GET['delete_id']) && Auth::can('delete_pillar')) {
    $delId = $_GET['delete_id'];
    // التحقق من وجود مبادرات
    $check = $db->prepare("SELECT COUNT(*) FROM initiatives WHERE pillar_id = ? AND is_deleted = 0");
    $check->execute([$delId]);
    if ($check->fetchColumn() > 0) {
        $error = "Cannot delete pillar: It has linked initiatives.";
    } else {
        $db->prepare("UPDATE pillars SET is_deleted = 1, deleted_at = NOW() WHERE id = ?")->execute([$delId]);
        header("Location: list.php?msg=deleted");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Pillars</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/pillars_list.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
       </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-bullseye"></i> Strategic Pillars
        </h1>
        <?php if(Auth::can('create_pillar')): ?>
            <a href="create.php" class="btn-primary">
                <i class="fa-solid fa-plus"></i> Add Pillar
            </a>
        <?php endif; ?>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET">
        <input type="text" name="search" class="filter-input" placeholder="Search by name or description..." value="<?= htmlspecialchars($search) ?>">
        
        <select name="status_id" class="filter-select">
            <option value="">All Statuses</option>
            <?php foreach($statuses as $st): ?>
                <option value="<?= $st['id'] ?>" <?= $status_id == $st['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn-primary" style="height: 42px;">Filter</button>
        
        <?php if($search || $status_id): ?>
            <a href="list.php" class="btn-reset"><i class="fa-solid fa-rotate-right"></i> Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align:left;">Pillar Name</th>
                    <th>Lead</th>
                    <th>Timeline</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Inits</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pillars)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#999;">
                            <i class="fa-solid fa-folder-open" style="font-size:2.5rem; margin-bottom:10px; display:block; opacity:0.5;"></i>
                            No strategic pillars found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pillars as $p): 
                        $pct = $p['progress_percentage'] ?? 0;
                        $color = $p['color'] ?: '#ff8c00';
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center;">
                                <div class="pillar-icon-box" style="background-color: <?= $color ?>;">
                                    <i class="fa-solid <?= $p['icon'] ?: 'fa-layer-group' ?>"></i>
                                </div>
                                <div>
                                    <span class="pillar-num">#<?= $p['pillar_number'] ?></span>
                                    <div class="pillar-name"><?= htmlspecialchars($p['name']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px; font-size:0.9rem;">
                                <i class="fa-solid fa-user-tie" style="color:#bdc3c7;"></i>
                                <?= htmlspecialchars($p['lead_name'] ?? 'Unassigned') ?>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:0.85rem; color:#555;"><?= date('M Y', strtotime($p['start_date'])) ?></div>
                            <div style="font-size:0.8rem; color:#999;">to <?= date('M Y', strtotime($p['end_date'])) ?></div>
                        </td>
                        <td>
                            <span class="status-badge" style="background-color: <?= $p['status_color'] ?: '#95a5a6' ?>;">
                                <?= $p['status_name'] ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center;">
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: <?= $pct ?>%; background-color: <?= $color ?>;"></div>
                                </div>
                                <span class="progress-text"><?= $pct ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span style="background:#f0f2f5; padding:4px 10px; border-radius:12px; font-weight:bold; font-size:0.8rem;">
                                <?= $p['initiatives_count'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $p['id'] ?>" class="action-btn btn-view" title="View Details"><i class="fa-solid fa-eye"></i></a>
                            
                            <?php if(Auth::can('edit_pillar')): ?>
                                <a href="edit.php?id=<?= $p['id'] ?>" class="action-btn btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <?php endif; ?>
                            
                            <?php if(Auth::can('delete_pillar')): ?>
                                <?php if($p['initiatives_count'] > 0): ?>
                                    <button class="action-btn btn-delete" style="opacity:0.5; cursor:not-allowed; background:#eee; color:#aaa;" title="Has linked initiatives" disabled>
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="?delete_id=<?= $p['id'] ?>" onclick="return confirm('Are you sure?')" class="action-btn btn-delete" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <script>
        const msg = "<?= $_GET['msg'] ?>";
        let title = 'Success';
        if(msg == 'deleted') title = 'Pillar deleted successfully';
        
        const Toast = Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
            timerProgressBar: true, didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
        Toast.fire({icon: 'success', title: title});
    </script>
<?php endif; ?>

</body>
</html>