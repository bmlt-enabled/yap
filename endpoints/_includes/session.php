<?php
if (!isset($_SESSION['override_service_body_id'])) {
    $service_body_id = 0;
    if (isset($_REQUEST["service_body_id"]) || isset($_REQUEST["override_service_body_id"])) {
        $service_body_id = isset($_REQUEST["service_body_id"]) ? $_REQUEST["service_body_id"] : $_REQUEST["override_service_body_id"];
    } else if (isset($_REQUEST["override_service_body_config_id"])) {
        $service_body_id = $_REQUEST["override_service_body_config_id"];
    }

    if ($service_body_id > 0) {
        $service_body_config = getServiceBodyConfig($service_body_id);

        if (isset($service_body_config)) {
            foreach ($service_body_config as $item => $value) {
                if (($item == "twilio_account_sid" || $item == "twilio_auth_token") && isset($_SESSION['call_state'])) {
                    continue;
                }
                $_SESSION["override_" . $item] = $value;
            }
        }
    }
}

if (!isset($_SESSION['call_state'])) {
    $_SESSION['call_state'] = "STARTED";
}

if (isset($_REQUEST)) {
    foreach ($_REQUEST as $key => $value) {
        if (str_exists($key, "override_")) {
            $_SESSION[$key] = $value;
        }
    }
}
