<?php
// modules/initiatives/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::check()) die("Access Denied");

$db = Database::getInstance()->pdo();

// --- 1. الفلاتر ---
$search    = $_GET['search'] ?? '';
$pillar_id = $_GET['pillar_id'] ?? '';
$status_id = $_GET['status_id'] ?? '';

// --- 2. الاستعلام ---
$sql = "
    SELECT i.*, 
           p.name AS pillar_name, p.color AS pillar_color,
           s.name AS status_name, s.color AS status_color,
           u.full_name_en AS owner_name, u.avatar AS owner_avatar
    FROM initiatives i
    LEFT JOIN pillars p ON p.id = i.pillar_id
    LEFT JOIN initiative_statuses s ON s.id = i.status_id
    LEFT JOIN users u ON u.id = i.owner_user_id
    WHERE (i.is_deleted = 0 OR i.is_deleted IS NULL)
";

$params = [];
if ($search) {
    $sql .= " AND (i.name LIKE ? OR i.initiative_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($pillar_id) {
    $sql .= " AND i.pillar_id = ?";
    $params[] = $pillar_id;
}
if ($status_id) {
    $sql .= " AND i.status_id = ?";
    $params[] = $status_id;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$initiatives = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 3. جلب القوائم للفلتر ---
$pillars  = $db->query("SELECT id, name FROM pillars WHERE is_deleted=0 ORDER BY name")->fetchAll();
$statuses = $db->query("SELECT id, name FROM initiative_statuses ORDER BY id")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Strategic Initiatives</title>
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* --- Artistic Theme --- */
        body { font-family: "Varela Round", sans-serif; background-color: #f8f9fa; color: #2d3436; margin: 0; }
        .page-wrapper { padding: 3rem 2rem; max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .page-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 2rem; font-weight: 800; color: #2d3436; margin: 0; letter-spacing: -1px; display: flex; align-items: center; gap: 12px; }
        .page-title i { color: #ff8c00; }
        
        .btn-add { 
            background: linear-gradient(135deg, #ff8c00, #e67e00); color: #fff; border: none; 
            padding: 12px 25px; border-radius: 30px; font-weight: 700; text-decoration: none; 
            display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; 
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.2);
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(255, 140, 0, 0.3); }

        /* Filter Bar */
        .filter-bar { 
            background: #fff; padding: 15px 20px; border-radius: 16px; margin-bottom: 30px; 
            display: flex; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); flex-wrap: wrap; 
        }
        .filter-input, .filter-select { 
            flex: 1; padding: 12px 15px; border: 1px solid #eee; border-radius: 10px; 
            background: #fcfcfc; outline: none; transition: 0.2s; min-width: 150px;
        }
        .filter-input:focus, .filter-select:focus { border-color: #ff8c00; background: #fff; }
        .btn-filter { background: #2d3436; color: #fff; border: none; padding: 0 25px; border-radius: 10px; cursor: pointer; font-weight: 700; }

        /* Grid */
        .init-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }

        /* Initiative Card */
        .init-card { 
            background: #fff; border-radius: 20px; padding: 25px; position: relative; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid #f0f2f5; display: flex; flex-direction: column; gap: 15px;
        }
        .init-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0,0,0,0.08); border-color: #ffe0b2; }
        
        .card-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .init-code { font-size: 0.75rem; font-weight: 800; color: #b2bec3; background: #f8f9fa; padding: 5px 10px; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .status-badge { 
            padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; color: #fff; 
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .init-title { font-size: 1.2rem; font-weight: 800; color: #2d3436; margin: 0; line-height: 1.4; }
        .init-pillar { font-size: 0.8rem; color: #636e72; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .pillar-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

        .owner-row { display: flex; align-items: center; gap: 10px; margin-top: auto; padding-top: 15px; border-top: 1px dashed #eee; }
        .owner-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .owner-info div:first-child { font-size: 0.85rem; font-weight: 700; color: #2d3436; }
        .owner-info div:last-child { font-size: 0.7rem; color: #b2bec3; }

        .prog-circle { margin-left: auto; width: 40px; height: 40px; position: relative; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 800; color: #2d3436; }
        
        /* Actions */
        .card-actions { position: absolute; top: 20px; right: 20px; opacity: 0; transition: 0.2s; display: flex; gap: 5px; }
        .init-card:hover .card-actions { opacity: 1; }
        .action-icon { 
            width: 32px; height: 32px; border-radius: 8px; background: #fff; border: 1px solid #eee; 
            display: flex; align-items: center; justify-content: center; color: #555; text-decoration: none; 
            font-size: 0.9rem; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .action-icon:hover { background: #ff8c00; color: #fff; border-color: #ff8c00; }
        
        .empty-state { grid-column: 1 / -1; text-align: center; padding: 60px; color: #b2bec3; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-chess"></i> Strategic Initiatives</h1>
        <?php if(Auth::can('create_initiative')): ?>
            <a href="create.php" class="btn-add"><i class="fa-solid fa-plus"></i> New Initiative</a>
        <?php endif; ?>
    </div>

    <form class="filter-bar" method="GET">
        <input type="text" name="search" class="filter-input" placeholder="Search initiative name or code..." value="<?= htmlspecialchars($search) ?>">
        
        <select name="pillar_id" class="filter-select">
            <option value="">All Pillars</option>
            <?php foreach($pillars as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $pillar_id == $p['id'] ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select name="status_id" class="filter-select">
            <option value="">All Statuses</option>
            <?php foreach($statuses as $st): ?>
                <option value="<?= $st['id'] ?>" <?= $status_id == $st['id'] ? 'selected' : '' ?>><?= htmlspecialchars($st['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-filter">Filter</button>
        <?php if($search || $pillar_id || $status_id): ?>
            <a href="list.php" style="padding: 12px; color: #e74c3c; text-decoration: none; font-weight: bold;">Reset</a>
        <?php endif; ?>
    </form>

    <div class="init-grid">
        <?php if(empty($initiatives)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <h3>No initiatives found.</h3>
                <p>Try adjusting your filters or create a new one.</p>
            </div>
        <?php else: ?>
            <?php foreach($initiatives as $init): 
                $avatar = $init['owner_avatar'] ? BASE_URL.'assets/uploads/avatars/'.$init['owner_avatar'] : BASE_URL.'assets/uploads/avatars/default-profile.png';
                $pct = $init['progress_percentage'] ?? 0;
            ?>
                <div class="init-card">
                    <div class="card-top">
                        <span class="init-code"><?= $init['initiative_code'] ?></span>
                        <span class="status-badge" style="background:<?= $init['status_color'] ?? '#ccc' ?>;">
                            <?= $init['status_name'] ?>
                        </span>
                    </div>

                    <a href="view.php?id=<?= $init['id'] ?>" style="text-decoration:none;">
                        <h3 class="init-title"><?= htmlspecialchars($init['name']) ?></h3>
                    </a>

                    <div class="init-pillar">
                        <span class="pillar-dot" style="background:<?= $init['pillar_color'] ?? '#ccc' ?>;"></span>
                        <?= htmlspecialchars($init['pillar_name']) ?>
                    </div>

                    <div class="owner-row">
                        <img src="<?= $avatar ?>" class="owner-avatar" alt="Owner">
                        <div class="owner-info">
                            <div><?= htmlspecialchars($init['owner_name'] ?? 'Unassigned') ?></div>
                            <div>Initiative Owner</div>
                        </div>
                        
                        <div class="prog-circle">
                            <?= $pct ?>%
                        </div>
                    </div>

                    <div class="card-actions">
                        <a href="view.php?id=<?= $init['id'] ?>" class="action-icon" title="View"><i class="fa-solid fa-eye"></i></a>
                        <?php if(Auth::can('edit_initiative')): ?>
                            <a href="edit.php?id=<?= $init['id'] ?>" class="action-icon" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <?php endif; ?>
                        <?php if(Auth::can('delete_initiative')): ?>
                            <a href="delete.php?id=<?= $init['id'] ?>" class="action-icon" onclick="return confirm('Delete initiative?')" title="Delete" style="color:#e74c3c;"><i class="fa-solid fa-trash"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>
</div>

</body>
</html>