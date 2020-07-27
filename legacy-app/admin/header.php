<?php
    require_once __DIR__ . '/../endpoints/_includes/functions.php';
    include_once __DIR__.'/../lang/'.getWordLanguage().'.php';
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="favicon.ico">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="dist/css/yap.min.css?v=<?php echo time()?>">
    <title>Yap Admin</title>
</head>
<body>
<?php require_once 'spinner_dialog.php';
