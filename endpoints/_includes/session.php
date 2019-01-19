<?php
if (isset($_REQUEST["PHPSESSID"])) {
    session_id($_REQUEST["PHPSESSID"]);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['override_service_body_id'])) {
    if (isset($_REQUEST["service_body_id"]) || isset($_REQUEST["override_service_body_id"])) {
        $service_body_id = isset($_REQUEST["service_body_id"]) ? $_REQUEST["service_body_id"] : $_REQUEST["override_service_body_id"];
        $service_body_config = getServiceBodyConfig($service_body_id);

        if (isset($service_body_config)) {
            foreach ($service_body_config as $item => $value) {
                $_SESSION["override_" . $item] = $value;
            }
        }
    }
}

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (strpos($key, "override_") !== false) {
            $_SESSION[$key] = $value;
        }
    }
}
