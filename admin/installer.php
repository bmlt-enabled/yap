<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="dist/css/yap.min.css?v=<?php echo time()?>">
    <title>Yap Admin</title>
</head>
<body>
    <div id="signin" class="container">
        <form id="auth" class="form-signin" method="POST" action="auth_login.php">
            <div id="yap-logo">
                <img src="dist/img/yap_logo.png" alt="Yap" width="310" height="100">
                <div id="admin_title">Installer</div>
            </div>
        </form>
    </div>
    <script src="dist/js/yap.min.js<?php isset($_REQUEST['JSDEBUG']) ? sprintf("?v=%s", time()) : "";?>"></script>
</body>
</html>

