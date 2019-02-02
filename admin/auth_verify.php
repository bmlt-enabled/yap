<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';

function session_expired() {
    if (!isset($_SESSION['username']) || !check_auth($_SESSION['username'])) {
        session_unset();
        return true;
    } else {
        return false;
    }
}

$expired = session_expired();

if (isset($_REQUEST["format"]) && $_REQUEST["format"] === "json") {
    header('Content-Type: application/json');
    echo "{\"expired\":" . get_str_val($expired) . "}";
} else if ($expired) {
    header('Location: index.php?auth=false');
    exit();
}
