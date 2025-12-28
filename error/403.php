<?php 
// error/403.php
require_once "../core/config.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>403 - Access Denied</title>
    <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/images/favicon-32x32.png">
    <style>
        body { font-family: 'Varela Round', sans-serif; background: #fcfcfc; text-align: center; padding-top: 100px; color: #333; }
        h1 { font-size: 80px; margin: 0; color: #FF8C00; }
        p { font-size: 18px; color: #666; margin-bottom: 30px; }
        a { text-decoration: none; background: #FF8C00; color: white; padding: 12px 25px; border-radius: 30px; font-weight: bold; }
        a:hover { background: #e07b00; }
    </style>
</head>
<body>
    <h1>403</h1>
    <h2>Access Denied</h2>
    <p>You do not have permission to access this page.</p>
    <a href="<?php echo BASE_URL; ?>index.php">Go Back Home</a>
</body>
</html>