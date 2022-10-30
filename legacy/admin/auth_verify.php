<?php
require_once __DIR__ . '/../_includes/functions.php';

function is_session_expired()
{
    if (!isset($_SESSION['username']) || !check_auth()) {
        session_unset();
        return true;
    } else {
        return false;
    }
}

if (isset($_REQUEST["service_body_id"]) && $_REQUEST["service_body_id"] > 0) {
    $found = false;
    $service_body_rights = getServiceBodiesRights();
    if (isset($service_body_rights)) {
        foreach ($service_body_rights as $service_body) {
            if ($service_body->id == $_REQUEST['service_body_id']) {
                $found = true;
                continue;
            }
        }
    }

    if (!$found) {
        session_unset();
    }
} elseif (isset($_REQUEST["service_body_id"]) && $_REQUEST["service_body_id"] == 0 && isTopLevelAdmin()) {
    $found = true;
}

$expired = is_session_expired();

if (isset($_REQUEST["format"]) && $_REQUEST["format"] === "json") {
//    header('Content-Type: application/json');
    echo "{\"expired\":" . get_str_val($expired) . "}";
} else if ($expired) {
    header('Location: index.php?auth=false');
    exit();
}
