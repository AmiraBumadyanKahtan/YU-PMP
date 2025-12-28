<?php
// modules/departments/view.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";
require_once "department_functions.php"; 

// ✅ 1. التعديل: استخدام صلاحية العرض المحددة
if (!Auth::can('sys_dept_view')) {
    header("Location: ../../error/403.php");
    exit;
}

$db = Database::getInstance()->pdo();

// 2. التحقق من ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $db->prepare("
    SELECT d.*, 
           u.full_name_en AS manager_name,
           u.id AS manager_id,
           u.avatar
    FROM departments d
    LEFT JOIN users u ON d.manager_id = u.id
    WHERE d.id = :id
");
$stmt->execute([':id' => $id]);
$dept = $stmt->fetch();

if (!$dept) {
    die("Error: Department not found.");
}

// 3. جلب الفروع
$branchIds = getDepartmentBranches($dept['id']);
$allBranchesRaw = getAllActiveBranches();
$branchNames = [];
foreach ($allBranchesRaw as $b) {
    if (in_array($b['id'], $branchIds)) {
        $branchNames[] = $b['branch_name'];
    }
}

// 4. الإحصائيات
$stats = [
    "projects" => $db->query("SELECT COUNT(*) FROM operational_projects WHERE department_id = {$dept['id']} AND (is_deleted=0 OR is_deleted IS NULL)")->fetchColumn(),
    "collabs"  => $db->query("SELECT COUNT(*) FROM collaborations WHERE department_id = {$dept['id']}")->fetchColumn(),
    "users"    => $db->query("SELECT COUNT(*) FROM users WHERE department_id = {$dept['id']} AND (is_deleted=0 OR is_deleted IS NULL)")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Department Details</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/view.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <script src="js/delete.js"></script>
</head>

<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-building"></i> <?= htmlspecialchars($dept['name']) ?>
            </h1>
            <a href="list.php" class="btn btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="stats-grid">
            <div class="stats-card st-orange">
                <div class="stats-icon" style="color:#f39c12;"><i class="fa-solid fa-diagram-project"></i></div>
                <div class="value"><?= $stats['projects'] ?></div>
                <div class="label">Active Projects</div>
            </div>

            <div class="stats-card st-blue">
                <div class="stats-icon" style="color:#3498db;"><i class="fa-solid fa-handshake"></i></div>
                <div class="value"><?= $stats['collabs'] ?></div>
                <div class="label">Collaborations</div>
            </div>

            <div class="stats-card st-green">
                <div class="stats-icon" style="color:#2ecc71;"><i class="fa-solid fa-users"></i></div>
                <div class="value"><?= $stats['users'] ?></div>
                <div class="label">Staff Members</div>
            </div>
        </div>

        <div class="details-card">
            
            <h3 class="card-subtitle">General Information</h3>
            
            <div class="details-grid">
                
                <div class="details-row">
                    <div class="details-label">Department Name</div>
                    <div class="details-value name-value">
                        <?= htmlspecialchars($dept['name']); ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Assigned Branches</div>
                    <div class="details-value">
                        <?php if (!empty($branchNames)): ?>
                            <?php foreach ($branchNames as $bName): ?>
                                <span class="badge-branch">
                                    <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($bName) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">No branches assigned</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Head of Dept.</div>
                    <div class="details-value">
                        <?php if ($dept['manager_name']): ?>
                            <a href="../users/view.php?id=<?= $dept['manager_id'] ?>" class="manager-link">
                                <div class="manager-avatar">
                                    <?= strtoupper(substr($dept['manager_name'], 0, 1)) ?>
                                </div>
                                <?= htmlspecialchars($dept['manager_name']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color:#999; font-style:italic;">— Not Assigned —</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Created At</div>
                    <div class="details-value">
                        <i class="fa-regular fa-calendar"></i> <?= date('d M Y', strtotime($dept['created_at'])) ?>
                    </div>
                </div>

                <div class="details-row">
                    <div class="details-label">Last Updated</div>
                    <div class="details-value">
                        <i class="fa-solid fa-clock-rotate-left"></i> <?= date('d M Y, h:i A', strtotime($dept['updated_at'])) ?>
                    </div>
                </div>

            </div>

            <hr class="divider">

            <div class="details-actions">
                <?php if (Auth::can('sys_dept_edit')): ?>
                    <a href="edit.php?id=<?= $dept['id'] ?>" class="btn btn-edit">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Details
                    </a>
                <?php endif; ?>
                
                <?php if (Auth::can('sys_dept_delete')): ?>
                    <button class="btn btn-delete" onclick="deleteDepartment(<?= $dept['id'] ?>)">
                        <i class="fa-solid fa-trash-can"></i> Archive Department
                    </button>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

</body>
</html>