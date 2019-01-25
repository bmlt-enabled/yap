<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';

if (auth_bmlt($_POST['username'], $_POST['password'])) {
    $_SESSION['username'] = $_POST['username'];
    header('Location: home.php');
    exit();
} else {
    logout_auth($_POST['username']);
    header('Location: index.php?auth=false');
    exit();
}
