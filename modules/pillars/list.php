<?php
// modules/pillars/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "functions.php"; // تأكد أن الدوال موجودة هنا

if (!Auth::check()) die("Access Denied");

// ✅ تحديث البيانات لضمان دقة الأرقام في الجدول
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
// (تم نقل الكود من functions.php إلى هنا أو استدعاء الدالة المحدثة)
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
    
    // التحقق من وجود مبادرات مرتبطة
    $check = $db->prepare("SELECT COUNT(*) FROM initiatives WHERE pillar_id = ? AND is_deleted = 0");
    $check->execute([$delId]);
    $count = $check->fetchColumn();
    
    if ($count > 0) {
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <style>
        body { background-color: #fdfbf7; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; }
        
        /* Header Style similar to image */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { font-size: 1.8rem; color: #ff8c00; font-weight: bold; display: flex; align-items: center; gap: 10px; margin: 0; }
        .page-title i { font-size: 1.6rem; }
        
        .btn-add { 
            background-color: #ff8c00; color: white; border: none; padding: 10px 20px; 
            border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-flex; 
            align-items: center; gap: 8px; transition: 0.2s; cursor: pointer;
        }
        .btn-add:hover { background-color: #e67e00; }

        /* Filter Bar */
        .filter-container { background: #fff; padding: 15px; border-radius: 10px; margin-bottom: 25px; display: flex; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); }
        .search-input { flex: 1; padding: 10px 15px; border: 1px solid #eee; border-radius: 6px; background-color: #f9f9f9; outline: none; }
        .select-input { padding: 10px; border: 1px solid #eee; border-radius: 6px; background-color: #f9f9f9; outline: none; min-width: 150px; }
        
        .btn-apply { background-color: #ff8c00; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .btn-reset { background-color: #fbe9e7; color: #d35400; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: 600; margin-left: 5px; }

        /* Table Style */
        .table-card { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 15px rgba(0,0,0,0.03); }
        .custom-table { width: 100%; border-collapse: collapse; }
        
        .custom-table th { 
            background-color: #fff8e1; /* بيج فاتح للعناوين */
            color: #d35400; /* برتقالي غامق للنص */
            padding: 15px 20px; 
            text-align: left; 
            font-weight: 600; 
            font-size: 0.9rem;
            border-bottom: 2px solid #fff3cd;
        }
        
        .custom-table td { padding: 15px 20px; border-bottom: 1px solid #f9f9f9; color: #444; font-size: 0.95rem; vertical-align: middle; }
        .custom-table tr:hover { background-color: #fffdf5; }
        .custom-table tr:last-child td { border-bottom: none; }

        /* Pillar Name with Color Bar */
        .pillar-name-cell { display: flex; align-items: center; gap: 10px; font-weight: 600; color: #d35400; }
        .color-bar { width: 4px; height: 25px; border-radius: 2px; }

        /* Status Badge */
        .status-badge { padding: 4px 10px; border-radius: 15px; font-size: 0.75rem; color: #fff; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Actions */
        .action-btn { 
            width: 32px; height: 32px; border-radius: 6px; display: inline-flex; 
            align-items: center; justify-content: center; text-decoration: none; 
            margin-left: 5px; font-size: 0.9rem; border: none; cursor: pointer;
        }
        .btn-view { background-color: #fff3e0; color: #f57c00; }
        .btn-view:hover { background-color: #ffe0b2; }
        
        .btn-edit { background-color: #e8f5e9; color: #2e7d32; }
        .btn-edit:hover { background-color: #c8e6c9; }
        
        .btn-delete { background-color: #ffebee; color: #c62828; }
        .btn-delete:hover { background-color: #ffcdd2; }
        
        /* Alert */
        .alert-error { background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper" style="padding: 30px;">

    <div class="page-header">
        <h1 class="page-title">
            <i class="fa-solid fa-bullseye"></i> Strategic Pillars
        </h1>
        <?php if(Auth::can('create_pillar')): ?>
            <a href="create.php" class="btn-add">
                <i class="fa-solid fa-plus"></i> Add Pillar
            </a>
        <?php endif; ?>
    </div>

    <?php if(isset($error)): ?>
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form class="filter-container" method="GET">
        <input type="text" name="search" class="search-input" placeholder="Search pillars..." value="<?= htmlspecialchars($search) ?>">
        
        <select name="status_id" class="select-input">
            <option value="">All Statuses</option>
            <?php foreach($statuses as $st): ?>
                <option value="<?= $st['id'] ?>" <?= $status_id == $st['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit" class="btn-apply"><i class="fa-solid fa-filter"></i> Apply</button>
        <?php if($search || $status_id): ?>
            <a href="list.php" class="btn-reset">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-card">
        <table class="custom-table">
            <thead>
                <tr>
                    <th width="30%">Pillar Name</th>
                    <th>Lead</th>
                    <th>Dates</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pillars)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:30px; color:#999;">No pillars found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pillars as $p): ?>
                    <tr>
                        <td>
                            <div class="pillar-name-cell">
                                <div class="color-bar" style="background-color: <?= $p['color'] ?>;"></div>
                                <div style="display:flex; flex-direction:column; gap:2px;">
                                    <span><?= htmlspecialchars($p['name']) ?></span>
                                    <span style="font-size:0.75rem; color:#888; font-weight:normal;">#<?= $p['pillar_number'] ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-user-tie" style="color:#bbb;"></i>
                                <?= htmlspecialchars($p['lead_name'] ?? 'Unassigned') ?>
                            </div>
                        </td>
                        <td style="font-size:0.85rem;">
                            <div style="color:#555;"><?= $p['start_date'] ?></div>
                            <div style="color:#999;">to <?= $p['end_date'] ?></div>
                        </td>
                        <td>
                            <span class="status-badge" style="background-color: <?= $p['status_color'] ?? '#ccc' ?>;">
                                <?= $p['status_name'] ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <div style="flex:1; background:#eee; height:6px; border-radius:3px; min-width:60px;">
                                    <div style="height:100%; width:<?= $p['progress_percentage'] ?>%; background:<?= $p['color'] ?>; border-radius:3px;"></div>
                                </div>
                                <span style="font-size:0.8rem; font-weight:bold; color:#666;"><?= $p['progress_percentage'] ?>%</span>
                            </div>
                        </td>
                        <td style="text-align:right;">
                            <a href="view.php?id=<?= $p['id'] ?>" class="action-btn btn-view" title="View"><i class="fa-solid fa-eye"></i></a>
                            
                            <?php if(Auth::can('edit_pillar')): ?>
                                <a href="edit.php?id=<?= $p['id'] ?>" class="action-btn btn-edit" title="Edit"><i class="fa-solid fa-pen"></i></a>
                            <?php endif; ?>
                            
                            <?php if(Auth::can('delete_pillar')): ?>
                                <?php if($p['initiatives_count'] > 0): ?>
                                    <button class="action-btn btn-delete" style="opacity:0.5; cursor:not-allowed;" title="Cannot delete: Has linked initiatives" disabled>
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <a href="?delete_id=<?= $p['id'] ?>" onclick="return confirm('Are you sure you want to delete this pillar?')" class="action-btn btn-delete" title="Delete">
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

</body>
</html>