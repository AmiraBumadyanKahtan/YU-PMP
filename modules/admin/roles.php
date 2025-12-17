<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
    header("Location: ../index.php"); exit();
}

$db = new Database();
$lang = getCurrentLang();

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_role') {
    $data = [
        'name_en' => sanitizeInput($_POST['name_en']),
        'name_ar' => sanitizeInput($_POST['name_ar'])
    ];
    $db->insert('project_roles', $data);
    header("Location: roles.php?msg=added"); exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $db->delete('project_roles', 'id = :id', ['id' => $_GET['delete']]);
    header("Location: roles.php?msg=deleted"); exit();
}

$roles = $db->fetchAll("SELECT * FROM project_roles ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang === 'ar' ? 'أدوار الفريق' : 'Project Roles'; ?></title>
    <link rel="icon" type="image/png" href="../assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body <?php echo $lang === 'ar' ? 'class="rtl"' : ''; ?>>

    <?php include '../includes/layout_header.php'; ?>
    <?php include '../includes/layout_sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1><?php echo $lang === 'ar' ? 'أدوار الفريق' : 'Project Roles'; ?></h1>
                    <p class="text-muted"><?php echo $lang === 'ar' ? 'إدارة المسميات الوظيفية لأعضاء المشروع' : 'Manage team member roles'; ?></p>
                </div>
                <button class="btn btn-primary" onclick="openModal('modal-add-role')">
                    <i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة دور' : 'Add Role'; ?>
                </button>
            </div>

            <div class="card">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr style="background:#f9f9f9; text-align:start;">
                            <th style="padding:15px;">ID</th>
                            <th style="padding:15px;">Name (EN)</th>
                            <th style="padding:15px;">الاسم (عربي)</th>
                            <th style="padding:15px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($roles as $r): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:15px;">#<?php echo $r['id']; ?></td>
                            <td style="padding:15px; font-weight:600;"><?php echo $r['name_en']; ?></td>
                            <td style="padding:15px;"><?php echo $r['name_ar']; ?></td>
                            <td style="padding:15px; text-align:end;">
                                <a href="?delete=<?php echo $r['id']; ?>" class="btn-icon" style="color:#dc3545;" onclick="return confirm('Delete this role?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modal-add-role" class="custom-modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'إضافة دور جديد' : 'Add New Role'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-add-role')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_role">
                    <div class="form-group">
                        <label class="form-label">Role Name (EN)</label>
                        <input type="text" name="name_en" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">اسم الدور (عربي)</label>
                        <input type="text" name="name_ar" class="form-input" required dir="rtl">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>