<?php
    session_start();
    include '../config.php';
    include '../functions.php';

    if (auth_bmlt($_POST['username'], $_POST['password'])) {
        $_SESSION['username'] = $_POST['username'];
        header('Location: home.php');
        exit();
    } else {
        header('Location: index.php?auth=false');
        exit();
    }
