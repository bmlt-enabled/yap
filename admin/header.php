<?php
    include_once dirname(__DIR__).'/functions.php';
    include_once dirname(__DIR__).'/lang/'.getWordLanguage().'.php';
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/superhero.bootstrap.min.css">
    <link rel="stylesheet" href="css/yap.css?v=<?php echo time()?>">
    <title>Yap Admin</title>
</head>
<body>
<?php include_once 'spinner_dialog.php';
