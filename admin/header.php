<?php
    $word_language_selected = isset($GLOBALS['word_language']) ? $GLOBALS['word_language'] : 'en-US';
    include_once dirname(__DIR__).'/config.php';
    include_once dirname(__DIR__).'/lang/'.$word_language_selected.'.php';
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap-4.1.0.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <link rel="stylesheet" href="css/yap.css">
    <title>Yap Admin</title>
</head>
<body>
<?php include_once 'spinner_dialog.php';