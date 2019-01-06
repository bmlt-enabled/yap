<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';

if (!isset($_SESSION['username']) || !check_auth($_SESSION['username'])) {
    session_unset();
    header('Location: index.php?auth=false');
    exit();
}
