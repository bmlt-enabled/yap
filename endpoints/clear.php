<?php
session_start();
foreach ($_SESSION as $key => $value) {
    if (strpos($key, "cache_") === 0
        || strpos($key, "override_") === 0
        || strpos($key, 'call_state') === 0) {
        echo sprintf("Cleared <i>%s</i><br/>", $key);
        unset($_SESSION[$key]);
    }
}
