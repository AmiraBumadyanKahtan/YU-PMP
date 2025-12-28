<?php
// modules/departments/create.php

require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";
require_once "department_functions.php";

// ✅ التعديل: استخدام الصلاحية المفصلة الجديدة
if (!Auth::can('sys_dept_create')) {
    header("Location: ../../error/403.php");
    exit;
}

$users = dept_get_potential_managers();
$allBranches = getAllActiveBranches();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Department</title>
    
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/create.css">
    <link rel="icon" type="image/png" href="../../assets/images/favicon-32x32.png">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">

    <style>
        /* تم دمج التنسيقات التي طلبتها سابقاً لضمان عدم ضياعها */
        .branch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 10px; }
        .branch-option { position: relative; }
        .branch-option input[type="checkbox"] { display: none; }
        
        .branch-label {
            display: flex; align-items: center; justify-content: center;
            padding: 15px; background: #f8f9fa; border: 2px solid #e9ecef;
            border-radius: 10px; cursor: pointer; transition: all 0.3s ease;
            font-weight: 500; color: #495057; text-align: center;
        }
        .branch-label:hover { border-color: #3498db; background: #edf7fc; }
        
        .branch-option input[type="checkbox"]:checked + .branch-label {
            background: #e8f4fd; border-color: #3498db; color: #3498db;
            font-weight: bold; box-shadow: 0 4px 6px rgba(52, 152, 219, 0.2);
        }
        .branch-label i { margin-right: 8px; }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2); }
    </style>
</head>

<body style="margin:0; background-color: #f4f6f9;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
    <div class="page-wrapper">

        <div class="page-header-flex">
            <h1 class="page-title">
                <i class="fa-solid fa-building-circle-check"></i> New Department
            </h1>
            <a href="list.php" class="btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> Cancel
            </a>
        </div>

        <div class="form-card">
            
            <?php if (isset($_SESSION['error'])): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: '<?= $_SESSION['error'] ?>',
                            confirmButtonColor: '#d33',
                        });
                    });
                </script>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form id="createDeptForm" action="save.php" method="post">

                <div class="form-group">
                    <label class="form-label">Department Name <span style="color:red">*</span></label>
                    <input type="text" name="name" id="deptName" class="form-control" required placeholder="e.g. Finance, HR, IT...">
                </div>

                <div class="form-group">
                    <label class="form-label">Head of Department</label>
                    <select name="manager_id" class="form-control">
                        <option value="">-- Select Manager --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>">
                                <?= htmlspecialchars($u['full_name_en']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Available in Branches <span style="color:red">*</span> <small class="text-muted">(Select at least one)</small></label>
                    
                    <div class="branch-grid">
                        <?php foreach ($allBranches as $b): ?>
                            <div class="branch-option">
                                <input type="checkbox" name="branches[]" id="branch_<?= $b['id'] ?>" value="<?= $b['id'] ?>">
                                <label for="branch_<?= $b['id'] ?>" class="branch-label">
                                    <i class="fa-solid fa-location-dot"></i> 
                                    <?= htmlspecialchars($b['branch_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="branchError" style="color: #dc3545; font-size: 0.9em; margin-top: 5px; display: none;">
                        <i class="fa-solid fa-circle-exclamation"></i> Please select at least one branch.
                    </div>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-check"></i> Save Department
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
    // التحقق قبل الإرسال
    document.getElementById('createDeptForm').addEventListener('submit', function(e) {
        const branches = document.querySelectorAll('input[name="branches[]"]:checked');
        const errorDiv = document.getElementById('branchError');
        const deptName = document.getElementById('deptName').value.trim();

        if (branches.length === 0) {
            e.preventDefault();
            errorDiv.style.display = 'block';
            Swal.fire({
                icon: 'warning',
                title: 'Attention',
                text: 'You must select at least one branch for this department.',
                confirmButtonColor: '#f39c12',
            });
            return;
        } else {
            errorDiv.style.display = 'none';
        }

        if (deptName === "") {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Missing Field',
                text: 'Department name is required.',
            });
        }
    });
</script>

</body>
</html>