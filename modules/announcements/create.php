<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";

if (!Auth::can('sys_ann_create')) {
    header("Location: ../../error/403.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Announcement</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/layout.css">
    <link rel="stylesheet" href="../../modules/users/css/create.css"> 
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon-32x32.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
</head>
<body style="margin:0;">

<?php include "../../layout/header.php"; ?>
<?php include "../../layout/sidebar.php"; ?>

<div class="main-content">
<div class="page-wrapper">

    <div class="page-header-flex">
        <h1 class="page-title"><i class="fa-solid fa-bullhorn"></i> New Announcement</h1>
        <a href="list.php" class="btn-secondary">Cancel</a>
    </div>

    <div class="user-form-wrapper" style="max-width:800px; margin:0 auto;">
        <form action="save.php" method="post">
            <div class="form-group">
                <label class="form-label">Title <span style="color:red">*</span></label>
                <input type="text" name="title" class="form-control" required placeholder="e.g. System Maintenance">
            </div>

            <div class="form-group">
                <label class="form-label">Type / Priority</label>
                <select name="type" class="form-control">
                    <option value="info">Info (Blue)</option>
                    <option value="success">Success (Green)</option>
                    <option value="warning">Warning (Orange)</option>
                    <option value="danger">Urgent (Red)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Message Content <span style="color:red">*</span></label>
                <textarea name="message" class="form-control" rows="5" required style="width:100%; padding:15px; border-radius:10px; border:1px solid #ddd; font-family:inherit;"></textarea>
            </div>

            <div class="form-actions" style="text-align:right; margin-top:20px;">
                <button type="submit" class="save-btn" style="width:auto; padding: 12px 30px;">
                    <i class="fa-solid fa-paper-plane"></i> Post Now
                </button>
            </div>
        </form>
    </div>

</div>
</div>
</body>
</html>