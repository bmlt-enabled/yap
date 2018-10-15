<?php
require_once __DIR__.'/../endpoints/functions.php';

logout_auth($_POST['username']);
if (auth_bmlt($_POST['username'], $_POST['password'])) {
    $_SESSION['username'] = $_POST['username'];
    header('Location: home.php');
    exit();
} else {
    header('Location: index.php?auth=false');
    exit();
}
