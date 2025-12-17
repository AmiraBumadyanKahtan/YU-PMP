<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
    header("Location: ../index.php");
    exit();
}

$db = new Database();
$lang = getCurrentLang();
$baseUrl = SITE_URL; // لاستخدام المسارات الصحيحة

// --- معالجة الطلبات (Add, Edit, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. إضافة مستخدم
    if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
        $username = sanitizeInput($_POST['username']);
        $exists = $db->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $_POST['email']]);
        
        if ($exists) {
            $error = "Username or Email already exists";
        } else {
            $data = [
                'username' => $username,
                'email' => sanitizeInput($_POST['email']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'full_name_en' => sanitizeInput($_POST['full_name_en']),
                'full_name_ar' => sanitizeInput($_POST['full_name_ar']),
                'role' => $_POST['role'],
                'department_id' => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
                'is_active' => 1
            ];
            $db->insert('users', $data);
            header("Location: users.php?msg=added");
            exit();
        }
    }

    // 2. تعديل مستخدم (هذا الجزء كان مفقوداً أو لا يعمل)
    if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
        $userId = $_POST['user_id'];
        
        $data = [
            'email' => sanitizeInput($_POST['email']),
            'full_name_en' => sanitizeInput($_POST['full_name_en']),
            'full_name_ar' => sanitizeInput($_POST['full_name_ar']),
            'role' => $_POST['role'],
            'department_id' => !empty($_POST['department_id']) ? $_POST['department_id'] : null,
            'is_active' => $_POST['is_active']
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $db->update('users', $data, 'id = :id', ['id' => $userId]);
        header("Location: users.php?msg=updated");
        exit();
    }

    // 3. حذف مستخدم
    if (isset($_POST['action']) && $_POST['action'] === 'delete_user') {
        $userId = $_POST['user_id'];
        if ($userId != $_SESSION['user_id']) {
            $db->delete('users', 'id = :id', ['id' => $userId]);
            header("Location: users.php?msg=deleted");
            exit();
        }
    }
}

$usersList = $db->fetchAll("SELECT u.*, d.name_en as dept_en, d.name_ar as dept_ar FROM users u LEFT JOIN departments d ON u.department_id = d.id ORDER BY u.id DESC");
$departments = $db->fetchAll("SELECT * FROM departments");
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang === 'ar' ? 'إدارة المستخدمين' : 'Users Management'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="icon" type="image/png" href="<?php echo $baseUrl; ?>/assets/images/favicon-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body <?php echo $lang === 'ar' ? 'class="rtl"' : ''; ?>>

    <?php include '../includes/layout_header.php'; ?>
    <?php include '../includes/layout_sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1><?php echo $lang === 'ar' ? 'المستخدمين والصلاحيات' : 'Users & Roles'; ?></h1>
                </div>
                <button class="btn btn-primary" onclick="openModal('modal-add-user')">
                    <i class="fas fa-user-plus"></i> <?php echo $lang === 'ar' ? 'مستخدم جديد' : 'New User'; ?>
                </button>
            </div>

            <div class="card">
                <div style="overflow-x: auto;">
                    <table class="data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f9f9f9; text-align: <?php echo $lang === 'ar' ? 'right' : 'left'; ?>;">
                                <th style="padding: 15px;"><?php echo $lang === 'ar' ? 'الاسم' : 'Name'; ?></th>
                                <th style="padding: 15px;"><?php echo $lang === 'ar' ? 'الدور' : 'Role'; ?></th>
                                <th style="padding: 15px;"><?php echo $lang === 'ar' ? 'القسم' : 'Department'; ?></th>
                                <th style="padding: 15px;"><?php echo $lang === 'ar' ? 'الحالة' : 'Status'; ?></th>
                                <th style="padding: 15px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usersList as $u): 
                                $userJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($u['full_name_en']); ?>&size=32&rounded=true" style="border-radius: 50%;">
                                        <div>
                                            <div style="font-weight: 600;"><?php echo $u[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></div>
                                            <div style="font-size: 0.8rem; color: #888;"><?php echo $u['email']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 15px;">
                                    <span class="badge" style="background: #eee;"><?php echo ucfirst(str_replace('_', ' ', $u['role'])); ?></span>
                                </td>
                                <td style="padding: 15px;">
                                    <?php echo $u[$lang === 'ar' ? 'dept_ar' : 'dept_en'] ?? '-'; ?>
                                </td>
                                <td style="padding: 15px;">
                                    <?php if($u['is_active']): ?>
                                        <span style="color: #28a745; font-weight: 600;"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-weight: 600;"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 15px; text-align: end;">
                                    <button class="btn-icon" style="color: var(--primary-orange); cursor: pointer;" onclick='openEditModal(<?php echo $userJson; ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-add-user" class="custom-modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'إضافة مستخدم جديد' : 'Add New User'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-add-user')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" required></div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group"><label class="form-label">Full Name (EN)</label><input type="text" name="full_name_en" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">الاسم (عربي)</label><input type="text" name="full_name_ar" class="form-input" required dir="rtl"></div>
                    </div>
                    <div class="form-group"><label class="form-label">Password</label><input type="password" name="password" class="form-input" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="employee">Employee</option>
                                <option value="project_manager">Project Manager</option>
                                <option value="initiative_owner">Initiative Owner</option>
                                <option value="pillar_lead">Pillar Lead</option>
                                <option value="strategy_office">Strategy Office</option>
                                <option value="ceo">CEO</option>
                                <option value="admin">System Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach($departments as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo $d['name_en']; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-user')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-edit-user" class="custom-modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'تعديل المستخدم' : 'Edit User'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-edit-user')">&times;</button>
            </div>
            <form method="POST" id="editUserForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" id="edit_username" class="form-input" disabled style="background: #f5f5f5;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group"><label class="form-label">Name (EN)</label><input type="text" name="full_name_en" id="edit_full_name_en" class="form-input" required></div>
                        <div class="form-group"><label class="form-label">Name (AR)</label><input type="text" name="full_name_ar" id="edit_full_name_ar" class="form-input" required></div>
                    </div>
                    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="edit_email" class="form-input" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="employee">Employee</option>
                                <option value="project_manager">Project Manager</option>
                                <option value="initiative_owner">Initiative Owner</option>
                                <option value="pillar_lead">Pillar Lead</option>
                                <option value="strategy_office">Strategy Office</option>
                                <option value="ceo">CEO</option>
                                <option value="admin">System Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select name="department_id" id="edit_department_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach($departments as $d): ?><option value="<?php echo $d['id']; ?>"><?php echo $d['name_en']; ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 10px;">
                        <div class="form-group">
                            <label class="form-label">Password (New)</label>
                            <input type="password" name="password" class="form-input" placeholder="Leave blank to keep">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="edit_is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: space-between;">
                    <button type="button" class="btn btn-outline" style="color: #dc3545; border-color: #dc3545;" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> <?php echo $lang === 'ar' ? 'حذف' : 'Delete'; ?>
                    </button>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-user')">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </div>
            </form>
            <form id="deleteUserForm" method="POST" style="display:none;">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="delete_user_id">
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('delete_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_full_name_en').value = user.full_name_en;
            document.getElementById('edit_full_name_ar').value = user.full_name_ar;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_department_id').value = user.department_id || '';
            document.getElementById('edit_is_active').value = user.is_active;
            openModal('modal-edit-user');
        }

        function confirmDelete() {
            if(confirm('Are you sure you want to delete this user permanently?')) {
                document.getElementById('deleteUserForm').submit();
            }
        }
    </script>
</body>
</html>