<?php
session_start();
include_once '../config.php';
include_once '../functions.php';

if (!isset($_SESSION['username']) || !check_auth($_SESSION['username'])) {
    session_unset();
    session_destroy();
    header('Location: index.php?auth=false');
    exit();
}
