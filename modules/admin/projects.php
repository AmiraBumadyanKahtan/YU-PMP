<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../index.php"); exit();
}

$db = new Database();
$lang = getCurrentLang();

// Fetch Projects with Manager and Department names
$projects = $db->fetchAll("
    SELECT p.*, u.full_name_en, u.full_name_ar, d.name_en as dept_en, d.name_ar as dept_ar
    FROM projects p
    LEFT JOIN users u ON p.manager_id = u.id
    LEFT JOIN departments d ON p.department_id = d.id
    ORDER BY p.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang === 'ar' ? 'كل المشاريع التشغيلية' : 'All Operational Projects'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body <?php echo $lang === 'ar' ? 'class="rtl"' : ''; ?>>

    <?php include '../includes/layout_header.php'; ?>
    <?php include '../includes/layout_sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            
            <div class="dashboard-header mb-4" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1><?php echo $lang === 'ar' ? 'سجل المشاريع التشغيلية' : 'Operational Projects Register'; ?></h1>
                    <p class="text-muted"><?php echo $lang === 'ar' ? 'عرض وإدارة جميع المشاريع التابعة للأقسام' : 'View and manage all department-based projects'; ?></p>
                </div>
                <a href="../projects/create.php" class="btn btn-primary"><i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'مشروع جديد' : 'New Project'; ?></a>
            </div>

            <div class="card">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr style="background:#f9f9f9; text-align:<?php echo $lang==='ar'?'right':'left';?>;">
                            <th style="padding:15px;">#</th>
                            <th style="padding:15px;"><?php echo $lang === 'ar' ? 'المشروع' : 'Project'; ?></th>
                            <th style="padding:15px;"><?php echo $lang === 'ar' ? 'القسم' : 'Department'; ?></th>
                            <th style="padding:15px;"><?php echo $lang === 'ar' ? 'المدير' : 'Manager'; ?></th>
                            <th style="padding:15px;"><?php echo $lang === 'ar' ? 'الميزانية' : 'Budget'; ?></th>
                            <th style="padding:15px;"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                            <th style="padding:15px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($projects)): ?>
                            <tr><td colspan="7" class="text-center p-3"><?php echo $lang === 'ar' ? 'لا توجد مشاريع.' : 'No projects found.'; ?></td></tr>
                        <?php else: foreach($projects as $p): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:15px; font-weight:bold; color:#888;"><?php echo $p['id']; ?></td>
                            <td style="padding:15px;">
                                <strong><?php echo $p[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></strong>
                                <div style="font-size:0.8rem; color:#888;"><?php echo formatDate($p['start_date']); ?> - <?php echo formatDate($p['end_date']); ?></div>
                            </td>
                            <td style="padding:15px;"><span class="badge" style="background:#fff3cd; color:#856404;"><?php echo $p[$lang === 'ar' ? 'dept_ar' : 'dept_en']; ?></span></td>
                            <td style="padding:15px;"><?php echo $p[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></td>
                            <td style="padding:15px;"><?php echo formatCurrency($p['budget_allocated']); ?></td>
                            <td style="padding:15px;"><?php echo getStatusBadge($p['status']); ?></td>
                            <td style="padding:15px; text-align:end;">
                                <a href="../project_detail.php?id=<?php echo $p['id']; ?>" class="btn-icon" style="color:var(--primary-orange);"><i class="fas fa-eye"></i></a>
                                <a href="../projects/edit.php?id=<?php echo $p['id']; ?>&type=operational" class="btn-icon" style="color:#666;"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>