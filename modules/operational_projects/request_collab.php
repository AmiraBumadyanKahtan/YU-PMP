<?php
// modules/operational_projects/request_collab.php
require_once "php/request_collab_BE.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Resource</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="css/request_collab.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans+Condensed:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Harmattan:wght@400;500;600;700&family=Varela+Round&display=swap" rel="stylesheet">
    
    <style>
    
    </style>
</head>
<body>

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title">
            <i class="fa-solid fa-handshake"></i> Request Resource
        </h1>
        <a href="collaborations.php?id=<?= $id ?>" class="btn-secondary">
            <i class="fa-solid fa-xmark"></i> Cancel
        </a>
    </div>

    <div class="form-card">
        
        <div class="project-info-box">
            <div class="project-info-icon"><i class="fa-solid fa-folder-open"></i></div>
            <div class="project-info-text">
                <h4>Requesting for: <?= htmlspecialchars($project['name']) ?></h4>
                <p>Code: <strong><?= $project['project_code'] ?></strong></p>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-box">
                <i class="fa-solid fa-triangle-exclamation"></i> 
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="form-group">
                <label class="form-label">Target Department <span class="req">*</span></label>
                <select name="department_id" class="form-select" required>
                    <option value="">-- Select Department --</option>
                    <?php foreach($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="helper-text">Select the department you need assistance from.</span>
            </div>

            <div class="form-group">
                <label class="form-label">Reason & Requirements <span class="req">*</span></label>
                <textarea name="reason" class="form-textarea" required placeholder="Describe the task, required skills, and estimated duration (e.g., 'Need a UI Designer for 2 weeks to create project mockups')."></textarea>
            </div>

            <div style="text-align:right; margin-top:30px; border-top:1px solid #f0f0f0; padding-top:20px;">
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Send Request
                </button>
            </div>

        </form>

    </div>

</div>
</div>

</body>
</html>