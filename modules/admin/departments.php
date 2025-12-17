<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
    header("Location: ../index.php");
    exit();
}

$db = new Database();
$lang = getCurrentLang();
$baseUrl = SITE_URL; // لاستخدام المسارات الصحيحة

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Add Department
    if (isset($_POST['action']) && $_POST['action'] === 'add_dept') {
        $data = [
            'name_en' => sanitizeInput($_POST['name_en']),
            'name_ar' => sanitizeInput($_POST['name_ar']),
            'manager_id' => !empty($_POST['manager_id']) ? $_POST['manager_id'] : null
        ];
        $db->insert('departments', $data);
        header("Location: departments.php?msg=added");
        exit();
    }

    // 2. Edit Department (الجديد)
    if (isset($_POST['action']) && $_POST['action'] === 'edit_dept') {
        $deptId = $_POST['dept_id'];
        $data = [
            'name_en' => sanitizeInput($_POST['name_en']),
            'name_ar' => sanitizeInput($_POST['name_ar']),
            'manager_id' => !empty($_POST['manager_id']) ? $_POST['manager_id'] : null
        ];
        $db->update('departments', $data, 'id = :id', ['id' => $deptId]);
        header("Location: departments.php?msg=updated");
        exit();
    }

    // 3. Delete Department (الجديد)
    if (isset($_POST['action']) && $_POST['action'] === 'delete_dept') {
        $deptId = $_POST['dept_id'];
        // يمكن إضافة تحقق هنا إذا كان القسم يحتوي على مشاريع مرتبطة قبل الحذف
        $db->delete('departments', 'id = :id', ['id' => $deptId]);
        header("Location: departments.php?msg=deleted");
        exit();
    }
}

// Fetch Data
$departments = $db->fetchAll("SELECT d.*, u.full_name_en, u.full_name_ar FROM departments d LEFT JOIN users u ON d.manager_id = u.id ORDER BY d.id ASC");
$managers = $db->fetchAll("SELECT id, full_name_en, full_name_ar FROM users WHERE is_active = 1"); // لجلب قائمة المدراء المحتملين
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'إدارة الأقسام' : 'Departments'; ?></title>
    
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>/assets/images/favicon-32x32.png">
    
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
                    <h1><?php echo $lang === 'ar' ? 'الأقسام' : 'Departments'; ?></h1>
                    <p class="text-muted"><?php echo $lang === 'ar' ? 'إدارة الهيكل التنظيمي' : 'Manage organizational structure'; ?></p>
                </div>
                <button class="btn btn-primary" onclick="openModal('modal-add-dept')">
                    <i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة قسم' : 'Add Department'; ?>
                </button>
            </div>

            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success" style="background:#efe; color:#080; padding:1rem; margin-bottom:1rem; border-radius:8px;">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo $lang === 'ar' ? 'تمت العملية بنجاح' : 'Action completed successfully'; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">
                <?php foreach($departments as $dept): 
                    // تجهيز البيانات للجافاسكريبت
                    $deptJson = htmlspecialchars(json_encode($dept), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="card" style="border-top: 4px solid var(--primary-orange);">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                            <h3 style="margin: 0; font-size: 1.2rem;"><?php echo $dept[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></h3>
                            <div style="font-size: 0.9rem; color: #888; margin-top: 5px;">
                                <?php echo $lang === 'ar' ? 'المدير:' : 'Manager:'; ?> 
                                <span style="color: var(--primary-orange); font-weight: 600;">
                                    <?php echo $dept[$lang === 'ar' ? 'full_name_ar' : 'full_name_en'] ?? ($lang === 'ar' ? 'غير معين' : 'Not Assigned'); ?>
                                </span>
                            </div>
                        </div>
                        <div style="background: #fff8f0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-orange);">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px;">
                        <button class="btn-icon" style="color: #666; cursor: pointer;" onclick='openEditDeptModal(<?php echo $deptJson; ?>)'>
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

    <div id="modal-add-dept" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'إضافة قسم جديد' : 'Add New Department'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-add-dept')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_dept">
                    <div class="form-group">
                        <label class="form-label">English Name</label>
                        <input type="text" name="name_en" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الاسم بالعربية</label>
                        <input type="text" name="name_ar" class="form-input" required dir="rtl">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo $lang === 'ar' ? 'مدير القسم' : 'Manager'; ?></label>
                        <select name="manager_id" class="form-select">
                            <option value=""><?php echo $lang === 'ar' ? 'اختر مدير...' : 'Select Manager...'; ?></option>
                            <?php foreach($managers as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-dept')"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'حفظ' : 'Save'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-edit-dept" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'تعديل القسم' : 'Edit Department'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-edit-dept')">&times;</button>
            </div>
            <form method="POST" id="editDeptForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_dept">
                    <input type="hidden" name="dept_id" id="edit_dept_id">
                    
                    <div class="form-group">
                        <label class="form-label">English Name</label>
                        <input type="text" name="name_en" id="edit_name_en" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">الاسم بالعربية</label>
                        <input type="text" name="name_ar" id="edit_name_ar" class="form-input" required dir="rtl">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo $lang === 'ar' ? 'مدير القسم' : 'Manager'; ?></label>
                        <select name="manager_id" id="edit_manager_id" class="form-select">
                            <option value=""><?php echo $lang === 'ar' ? 'اختر مدير...' : 'Select Manager...'; ?></option>
                            <?php foreach($managers as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo $m[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: space-between;">
                    <button type="button" class="btn btn-outline" style="color: #dc3545; border-color: #dc3545;" onclick="confirmDeleteDept()">
                        <i class="fas fa-trash"></i> <?php echo $lang === 'ar' ? 'حذف القسم' : 'Delete'; ?>
                    </button>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-dept')"><?php echo $lang === 'ar' ? 'إلغاء' : 'Cancel'; ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'حفظ التغييرات' : 'Save Changes'; ?></button>
                    </div>
                </div>
            </form>
            
            <form id="deleteDeptForm" method="POST" style="display:none;">
                <input type="hidden" name="action" value="delete_dept">
                <input type="hidden" name="dept_id" id="delete_dept_id">
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        // دالة فتح مودال التعديل وتعبئة البيانات
        function openEditDeptModal(dept) {
            document.getElementById('edit_dept_id').value = dept.id;
            document.getElementById('delete_dept_id').value = dept.id;
            
            document.getElementById('edit_name_en').value = dept.name_en;
            document.getElementById('edit_name_ar').value = dept.name_ar;
            document.getElementById('edit_manager_id').value = dept.manager_id || '';
            
            openModal('modal-edit-dept');
        }

        // دالة تأكيد الحذف
        function confirmDeleteDept() {
            if(confirm('<?php echo $lang === 'ar' ? 'هل أنت متأكد من حذف هذا القسم؟ قد يؤثر ذلك على المشاريع المرتبطة.' : 'Are you sure you want to delete this department?'; ?>')) {
                document.getElementById('deleteDeptForm').submit();
            }
        }
    </script>
</body>
</html>