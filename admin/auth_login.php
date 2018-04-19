<?php
    include '../config.php';
    include '../functions.php';

    if (auth_bmlt($_POST['username'], $_POST['password'])) {
        header('Location: home.php');
    } else {
        header('Location: index.php?auth=false');
    }