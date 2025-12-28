<?php
require_once "../../core/config.php";
require_once "../../core/auth.php";
require_once "../../core/Database.php";

if (!Auth::can('sys_ann_edit')) {
    header("Location: ../../error/403.php");
    exit;
}

$id = (int)$_GET['id'];
$db = Database::getInstance()->pdo();
$stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$id]);
$ann = $stmt->fetch();

if (!$ann) die("Announcement not found");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Announcement</title>
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
        <h1 class="page-title"><i class="fa-solid fa-pen-to-square"></i> Edit Announcement</h1>
        <a href="list.php" class="btn-secondary">Cancel</a>
    </div>

    <div class="user-form-wrapper" style="max-width:800px; margin:0 auto;">
        <form action="save.php" method="post">
            <input type="hidden" name="id" value="<?= $ann['id'] ?>">
            
            <div class="form-group">
                <label class="form-label">Title <span style="color:red">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($ann['title']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Type / Priority</label>
                <select name="type" class="form-control">
                    <option value="info" <?= $ann['type']=='info'?'selected':'' ?>>Info (Blue)</option>
                    <option value="success" <?= $ann['type']=='success'?'selected':'' ?>>Success (Green)</option>
                    <option value="warning" <?= $ann['type']=='warning'?'selected':'' ?>>Warning (Orange)</option>
                    <option value="danger" <?= $ann['type']=='danger'?'selected':'' ?>>Urgent (Red)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= $ann['is_active']?'selected':'' ?>>Active (Visible)</option>
                    <option value="0" <?= !$ann['is_active']?'selected':'' ?>>Archived (Hidden)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Message Content</label>
                <textarea name="message" class="form-control" rows="5" required style="width:100%; padding:15px; border-radius:10px; border:1px solid #ddd; font-family:inherit;"><?= htmlspecialchars($ann['message']) ?></textarea>
            </div>

            <div class="form-actions" style="text-align:right; margin-top:20px;">
                <button type="submit" class="save-btn" style="width:auto; padding: 12px 30px;">
                    <i class="fa-solid fa-floppy-disk"></i> Update
                </button>
            </div>
        </form>
    </div>

</div>
</div>
</body>
</html>