<?php
// modules/announcements/list.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

// ✅ 1. التحقق من صلاحية العرض
if (!Auth::can('sys_ann_view')) {
    header("Location: ../../error/403.php");
    exit;
}

$db = Database::getInstance()->pdo();

// الفلترة
$filter = $_GET['status'] ?? 'all';
$sql = "SELECT a.*, u.full_name_en 
        FROM announcements a 
        LEFT JOIN users u ON u.id = a.created_by 
        WHERE 1=1";

if ($filter === 'active') {
    $sql .= " AND a.is_active = 1";
} elseif ($filter === 'archived') {
    $sql .= " AND a.is_active = 0";
}

$sql .= " ORDER BY a.created_at DESC";
$announcements = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Announcements Archive</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="../../modules/users/css/users.css"> <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
        /* تخصيصات بسيطة للإعلانات */
        .type-badge { padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .type-info { background: #e3f2fd; color: #1976d2; }
        .type-success { background: #e8f5e9; color: #2e7d32; }
        .type-warning { background: #fff3e0; color: #ef6c00; }
        .type-danger { background: #ffebee; color: #c62828; }
        
        .msg-preview { max-width: 400px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #666; }
    </style>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <script>Swal.fire({icon: 'success', title: 'Archived', text: 'Announcement has been deactivated.', timer: 2000, showConfirmButton: false});</script>
    <?php endif; ?>

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-bullhorn"></i> Announcements Archive</h1>
        
        <?php if (Auth::can('sys_ann_create')): ?>
            <a href="create.php" class="btn-primary"><i class="fa-solid fa-plus"></i> Post New</a>
        <?php endif; ?>
    </div>

    <div class="filter-bar">
        <a href="?status=all" class="btn-reset" style="<?= $filter == 'all' ? 'background:#eee; color:#333;' : '' ?>">All</a>
        <a href="?status=active" class="btn-reset" style="color:#27ae60; <?= $filter == 'active' ? 'background:#e8f5e9;' : '' ?>">Active Only</a>
        <a href="?status=archived" class="btn-reset" style="color:#7f8c8d; <?= $filter == 'archived' ? 'background:#f0f0f0;' : '' ?>">Archived</a>
    </div>

    <div class="user-table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Message Preview</th>
                    <th>Posted By</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="7" class="empty-state">No announcements found.</td></tr>
                <?php else: ?>
                    <?php foreach ($announcements as $row): ?>
                        <tr style="<?= $row['is_active'] == 0 ? 'opacity:0.6; background:#fafafa;' : '' ?>">
                            <td>
                                <span class="type-badge type-<?= $row['type'] ?>">
                                    <?= ucfirst($row['type']) ?>
                                </span>
                            </td>
                            <td style="font-weight:bold; color:#2c3e50;"><?= htmlspecialchars($row['title']) ?></td>
                            <td><div class="msg-preview"><?= htmlspecialchars(substr($row['message'], 0, 80)) ?>...</div></td>
                            <td><?= htmlspecialchars($row['full_name_en'] ?? 'System') ?></td>
                            <td style="font-size:0.9rem; color:#777;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <?php if ($row['is_active']): ?>
                                    <span class="badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge-inactive">Archived</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <?php if (Auth::can('sys_ann_edit')): ?>
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen"></i></a>
                                <?php endif; ?>
                                
                                <?php if (Auth::can('sys_ann_delete') && $row['is_active'] == 1): ?>
                                    <a href="delete.php?id=<?= $row['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Archive this announcement?')"><i class="fa-solid fa-box-archive"></i></a>
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