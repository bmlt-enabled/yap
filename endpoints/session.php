<?php session_start();

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (strpos($key, "override_") == 0) {
            $_SESSION[$key] = $value;
        }
    }
}
