<?php
    include '../config.php';
    include '../functions.php';

    if (auth_bmlt($_POST['username'], $_POST['password'])) {
        $_SESSION['username'] = $_POST['username'];
        if (isset($_REQUEST["admin_language"])) {
            $_SESSION["Language"] = $_REQUEST["admin_language"];
        }
        header('Location: home.php');
        exit();
    } else {
        header('Location: index.php?auth=false');
        exit();
    }
