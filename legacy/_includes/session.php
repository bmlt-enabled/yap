<?php
if (!isset($_SESSION['override_service_body_id']) && !isset($_SESSION["override_service_body_config_id"])) {
    $service_body_id = 0;
    if (isset($_REQUEST["service_body_id"]) || isset($_REQUEST["override_service_body_id"])) {
        $service_body_id = isset($_REQUEST["service_body_id"]) ? $_REQUEST["service_body_id"] : $_REQUEST["override_service_body_id"];
    } elseif (isset($_REQUEST["override_service_body_config_id"])) {
        $service_body_id = $_REQUEST["override_service_body_config_id"];
    }

    setConfigForService($service_body_id);
}

if (!isset($_SESSION['call_state'])) {
    $_SESSION['call_state'] = "STARTED";
}

if (!isset($_SESSION['initial_webhook'])) {
    $webhook_array = explode("/", $_SERVER['REQUEST_URI']);
    $_SESSION['initial_webhook'] = str_replace("&", "&amp;", $webhook_array[count($webhook_array) - 1]);
}

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (str_contains($key, "override_")) {
            $_SESSION[$key] = $value;
        }
    }
}
