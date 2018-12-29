<?php
require_once '../lib/Session.php';
if (isset($_REQUEST["session_id"])) {
    session_id($_REQUEST["session_id"]);
}

$session = new Session();

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (strpos($key, "override_") == 0) {
            $_SESSION[$key] = $value;
        }
    }
}
