<?php
    include '../../config.php';
    include '../../functions.php';

    if (auth_bmlt($_POST['username'], $_POST['password'])) {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /admin/index.php?auth=false');
    }