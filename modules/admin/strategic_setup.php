<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

// Security Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin', 'strategy_office', 'ceo'])) {
    header("Location: ../index.php"); exit();
}

$db = new Database();
$lang = getCurrentLang();
$msg = '';
$error = '';

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD PILLAR
    if (isset($_POST['action']) && $_POST['action'] === 'add_pillar') {
        // Check Duplicate
        $exists = $db->fetchOne("SELECT id FROM pillars WHERE pillar_number = ?", [$_POST['pillar_number']]);
        if($exists) { $error = ($lang === 'ar' ? 'رقم الركيزة موجود مسبقاً' : 'Pillar number already exists'); } 
        else {
            $data = [
                'pillar_number' => $_POST['pillar_number'],
                'name_en' => sanitizeInput($_POST['name_en']),
                'name_ar' => sanitizeInput($_POST['name_ar']),
                'description_en' => sanitizeInput($_POST['description_en']),
                'description_ar' => sanitizeInput($_POST['description_ar']),
                'lead_user_id' => !empty($_POST['lead_user_id']) ? $_POST['lead_user_id'] : null,
                'budget_allocated' => $_POST['budget_allocated'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'status' => 'not_started'
            ];
            $db->insert('pillars', $data);
            header("Location: strategic_setup.php?tab=pillars&msg=added"); exit();
        }
    }

    // 2. ADD OBJECTIVE
    if (isset($_POST['action']) && $_POST['action'] === 'add_objective') {
        $data = [
            'pillar_id' => $_POST['pillar_id'],
            'objective_number' => $_POST['objective_number'],
            'name_en' => sanitizeInput($_POST['name_en']),
            'name_ar' => sanitizeInput($_POST['name_ar']),
            'description_en' => sanitizeInput($_POST['description_en']),
            'description_ar' => sanitizeInput($_POST['description_ar'])
        ];
        $db->insert('strategic_objectives', $data);
        header("Location: strategic_setup.php?tab=objectives&msg=added"); exit();
    }
}

// Fetch Base Data
$pillars = $db->fetchAll("SELECT * FROM pillars ORDER BY pillar_number ASC");
$users = $db->fetchAll("SELECT * FROM users WHERE is_active=1 ORDER BY full_name_en");

// Filter Logic
$filterPillar = isset($_GET['filter_pillar']) ? $_GET['filter_pillar'] : '';
$searchQuery = isset($_GET['q']) ? sanitizeInput($_GET['q']) : '';

// Fetch Objectives with Filter
$objSql = "SELECT o.*, p.name_en as p_en, p.name_ar as p_ar FROM strategic_objectives o LEFT JOIN pillars p ON o.pillar_id = p.id WHERE 1=1";
if($filterPillar) $objSql .= " AND o.pillar_id = $filterPillar";
if($searchQuery) $objSql .= " AND (o.name_en LIKE '%$searchQuery%' OR o.name_ar LIKE '%$searchQuery%' OR o.objective_number LIKE '%$searchQuery%')";
$objSql .= " ORDER BY o.id DESC";
$objectives = $db->fetchAll($objSql);

// Fetch Initiatives with Filter
$initSql = "SELECT i.*, p.name_en as p_en, p.name_ar as p_ar, u.full_name_en, u.full_name_ar 
            FROM initiatives i 
            LEFT JOIN pillars p ON i.pillar_id = p.id 
            LEFT JOIN users u ON i.owner_user_id = u.id 
            WHERE 1=1";
if($filterPillar) $initSql .= " AND i.pillar_id = $filterPillar";
if($searchQuery) $initSql .= " AND (i.name_en LIKE '%$searchQuery%' OR i.name_ar LIKE '%$searchQuery%' OR i.initiative_number LIKE '%$searchQuery%')";
$initSql .= " ORDER BY i.id DESC";
$initiatives = $db->fetchAll($initSql);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $lang === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang === 'ar' ? 'الإعداد الاستراتيجي' : 'Strategic Setup'; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* تحسينات المودال والفورم */
        .modal-content { 
            max-width: 700px !important; 
            border-radius: 12px;
        }
        .form-grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
        }
        .full-width { 
            grid-column: span 2; 
        }
        .multi-select { 
            height: 100px; 
            background: #f9f9f9;
        }
        .filter-bar {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>
</head>
<body <?php echo $lang === 'ar' ? 'class="rtl"' : ''; ?>>

    <?php include '../includes/layout_header.php'; ?>
    <?php include '../includes/layout_sidebar.php'; ?>

    <div class="main-content">
        <div class="content-wrapper">
            
            <?php if($error): ?>
                <div class="alert alert-error mb-4" style="background:#fee; color:#c00; padding:1rem; border-radius:8px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['msg']) && $_GET['msg'] == 'added'): ?>
                <div class="alert alert-success mb-4" style="background:#efe; color:#080; padding:1rem; border-radius:8px;">
                    <i class="fas fa-check-circle"></i> <?php echo $lang === 'ar' ? 'تمت الإضافة بنجاح' : 'Added successfully'; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header mb-4">
                <h1><?php echo $lang === 'ar' ? 'بناء الخطة الاستراتيجية' : 'Strategic Plan Setup'; ?></h1>
                <p class="text-muted"><?php echo $lang === 'ar' ? 'إدارة الركائز، الأهداف، والمبادرات' : 'Manage Pillars, Objectives, and Initiatives'; ?></p>
            </div>

            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-btn active" onclick="openTab(event, 'tab-pillars')">
                        <i class="fas fa-columns"></i> <?php echo $lang === 'ar' ? 'الركائز' : 'Pillars'; ?>
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-objectives')">
                        <i class="fas fa-bullseye"></i> <?php echo $lang === 'ar' ? 'الأهداف' : 'Objectives'; ?>
                    </button>
                    <button class="tab-btn" onclick="openTab(event, 'tab-initiatives')">
                        <i class="fas fa-tasks"></i> <?php echo $lang === 'ar' ? 'المبادرات' : 'Initiatives'; ?>
                    </button>
                </div>

                <div id="tab-pillars" class="tab-content active">
                    <div style="text-align: end; margin-bottom: 1rem;">
                        <button class="btn btn-primary" onclick="openModal('modal-add-pillar')">
                            <i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة ركيزة' : 'Add Pillar'; ?>
                        </button>
                    </div>
                    
                    <div class="dashboard-grid">
                        <?php foreach($pillars as $p): ?>
                        <div class="card" style="border-top: 5px solid var(--primary-orange);">
                            <div style="display: flex; justify-content: space-between;">
                                <h3 style="margin: 0;"><?php echo $p[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></h3>
                                <span class="badge" style="background: #eee;">#<?php echo $p['pillar_number']; ?></span>
                            </div>
                            <p style="color: #666; font-size: 0.9rem; margin: 10px 0; min-height: 40px;">
                                <?php echo mb_substr($p[$lang === 'ar' ? 'description_ar' : 'description_en'], 0, 100) . '...'; ?>
                            </p>
                            <div class="d-flex justify-between mt-3 p-2 bg-light rounded" style="background: #f9f9f9; border-radius: 6px;">
                                <div style="font-size: 0.85rem;">
                                    <i class="fas fa-user-tie" style="color: #999;"></i>
                                    <?php 
                                        $lead = array_filter($users, function($u) use ($p) { return $u['id'] == $p['lead_user_id']; });
                                        $lead = reset($lead);
                                        echo $lead ? ($lang === 'ar' ? $lead['full_name_ar'] : $lead['full_name_en']) : '-';
                                    ?>
                                </div>
                                <div style="font-weight: 700; color: var(--primary-orange);"><?php echo formatCurrency($p['budget_allocated']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="tab-objectives" class="tab-content">
                    <form class="filter-bar">
                        <input type="hidden" name="tab" value="objectives">
                        <select name="filter_pillar" class="form-select" style="width: 200px;" onchange="this.form.submit()">
                            <option value=""><?php echo $lang === 'ar' ? 'كل الركائز' : 'All Pillars'; ?></option>
                            <?php foreach($pillars as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $filterPillar == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo $p[$lang === 'ar' ? 'name_ar' : 'name_en']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="q" class="form-input" style="flex: 1;" placeholder="<?php echo $lang === 'ar' ? 'بحث عن هدف...' : 'Search objectives...'; ?>" value="<?php echo $searchQuery; ?>">
                        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
                        <button type="button" class="btn btn-primary" onclick="openModal('modal-add-objective')" style="white-space: nowrap;">
                            <i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة هدف' : 'Add Objective'; ?>
                        </button>
                    </form>
                    
                    <table class="data-table full-width">
                        <thead>
                            <tr style="background: #f9f9f9; text-align: <?php echo $lang === 'ar' ? 'right' : 'left'; ?>;">
                                <th style="padding: 1rem;">#</th>
                                <th style="padding: 1rem;"><?php echo $lang === 'ar' ? 'الهدف الاستراتيجي' : 'Strategic Objective'; ?></th>
                                <th style="padding: 1rem;"><?php echo $lang === 'ar' ? 'الركيزة التابعة' : 'Parent Pillar'; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($objectives as $obj): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 1rem; font-weight: bold; color: var(--primary-orange);"><?php echo $obj['objective_number']; ?></td>
                                <td style="padding: 1rem;">
                                    <strong><?php echo $obj[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></strong>
                                    <div style="font-size: 0.85rem; color: #888; margin-top: 4px;">
                                        <?php echo mb_substr($obj[$lang === 'ar' ? 'description_ar' : 'description_en'], 0, 80) . '...'; ?>
                                    </div>
                                </td>
                                <td style="padding: 1rem;"><span class="badge" style="background: #eee;"><?php echo $obj[$lang === 'ar' ? 'p_ar' : 'p_en']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div id="tab-initiatives" class="tab-content">
                    <form class="filter-bar">
                        <input type="hidden" name="tab" value="initiatives">
                        <select name="filter_pillar" class="form-select" style="width: 200px;" onchange="this.form.submit()">
                            <option value=""><?php echo $lang === 'ar' ? 'كل الركائز' : 'All Pillars'; ?></option>
                            <?php foreach($pillars as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $filterPillar == $p['id'] ? 'selected' : ''; ?>>
                                <?php echo $p[$lang === 'ar' ? 'name_ar' : 'name_en']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="q" class="form-input" style="flex: 1;" placeholder="<?php echo $lang === 'ar' ? 'بحث عن مبادرة...' : 'Search initiatives...'; ?>" value="<?php echo $searchQuery; ?>">
                        <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i></button>
                        
                        <button type="button" class="btn btn-primary" onclick="window.location.href='../projects/create.php'" style="white-space: nowrap;">
                            <i class="fas fa-plus"></i> <?php echo $lang === 'ar' ? 'إضافة مبادرة' : 'Add Initiative'; ?>
                        </button>
                    </form>

                    <div class="dashboard-grid">
                        <?php foreach($initiatives as $init): ?>
                        <div class="project-card">
                            <div class="card-status-border" style="background: var(--primary-orange);"></div>
                            <div style="display: flex; justify-content: space-between;">
                                <div>
                                    <div style="font-size: 0.8rem; color: #888; margin-bottom: 5px;"><?php echo $init[$lang === 'ar' ? 'p_ar' : 'p_en']; ?></div>
                                    <h4 style="margin: 0; font-size: 1.1rem;"><?php echo $init[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></h4>
                                </div>
                                <span style="font-weight: bold; color: #ccc;">#<?php echo $init['initiative_number']; ?></span>
                            </div>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f5f5f5; font-size: 0.9rem; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-user-circle" style="color: #ccc;"></i> 
                                <?php echo $init[$lang === 'ar' ? 'full_name_ar' : 'full_name_en'] ?? 'No Owner'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="modal-add-pillar" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'إضافة ركيزة جديدة' : 'Add New Pillar'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-add-pillar')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_pillar">
                    <div class="form-grid">
                        <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'رقم الركيزة' : 'Pillar Number'; ?></label><input type="number" name="pillar_number" class="form-input" required></div>
                        <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'الميزانية' : 'Budget'; ?></label><input type="number" name="budget_allocated" class="form-input"></div>
                        
                        <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (إنجليزي)' : 'Name (EN)'; ?></label><input type="text" name="name_en" class="form-input" required></div>
                        <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (عربي)' : 'Name (AR)'; ?></label><input type="text" name="name_ar" class="form-input" required dir="rtl"></div>
                        
                        <div class="form-group full-width">
                            <label class="form-label"><?php echo $lang === 'ar' ? 'قائد الركيزة' : 'Pillar Lead'; ?></label>
                            <select name="lead_user_id" class="form-select">
                                <option value=""><?php echo $lang === 'ar' ? 'اختر القائد...' : 'Select Lead...'; ?></option>
                                <?php foreach($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo $u[$lang === 'ar' ? 'full_name_ar' : 'full_name_en']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (عربي)' : 'Description (AR)'; ?></label><textarea name="description_ar" class="form-textarea" rows="2" dir="rtl"></textarea></div>
                        <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (إنجليزي)' : 'Description (EN)'; ?></label><textarea name="description_en" class="form-textarea" rows="2"></textarea></div>
                        
                        <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'تاريخ البدء' : 'Start Date'; ?></label><input type="date" name="start_date" class="form-input"></div>
                        <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'تاريخ الانتهاء' : 'End Date'; ?></label><input type="date" name="end_date" class="form-input"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'حفظ الركيزة' : 'Save Pillar'; ?></button></div>
            </form>
        </div>
    </div>

    <div id="modal-add-objective" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo $lang === 'ar' ? 'إضافة هدف استراتيجي' : 'Add Strategic Objective'; ?></h3>
                <button class="close-modal" onclick="closeModal('modal-add-objective')">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_objective">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label class="form-label"><?php echo $lang === 'ar' ? 'الركيزة التابعة' : 'Parent Pillar'; ?></label>
                            <select name="pillar_id" class="form-select" required>
                                <?php foreach($pillars as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo $p[$lang === 'ar' ? 'name_ar' : 'name_en']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label"><?php echo $lang === 'ar' ? 'رقم الهدف' : 'Objective #'; ?></label><input type="number" name="objective_number" class="form-input" required></div>
                        <div class="form-group"><label class="form-label" style="visibility: hidden;">Spacer</label></div> <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (إنجليزي)' : 'Name (EN)'; ?></label><input type="text" name="name_en" class="form-input" required></div>
                        <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الاسم (عربي)' : 'Name (AR)'; ?></label><input type="text" name="name_ar" class="form-input" required dir="rtl"></div>
                        <div class="form-group full-width"><label class="form-label"><?php echo $lang === 'ar' ? 'الوصف (عربي)' : 'Description (AR)'; ?></label><textarea name="description_ar" class="form-textarea" rows="2" dir="rtl"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary"><?php echo $lang === 'ar' ? 'حفظ الهدف' : 'Save Objective'; ?></button></div>
            </form>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) tabcontent[i].style.display = "none";
            tablinks = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tablinks.length; i++) tablinks[i].className = tablinks[i].className.replace(" active", "");
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }
        
        // Auto open tab from URL
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if(tab) {
            const btn = document.querySelector(`.tab-btn[onclick*="${tab}"]`);
            if(btn) btn.click();
        }
    </script>
</body>
</html>