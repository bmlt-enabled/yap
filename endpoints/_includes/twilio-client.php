<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Twilio\Rest\Client;
if (isset($_REQUEST['flush'])) {
    $_SESSION["twilio_account_sid"] = null;
    $_SESSION["twilio_auth_token"] = null;
}

if (isset($_SESSION["twilio_account_sid"]) && isset($_SESSION["twilio_auth_token"])) {
    $sid = $_SESSION["twilio_account_sid"];
    $token = $_SESSION["twilio_auth_token"];
} else {
    if (has_setting("service_body_id")) {
        $config = getServiceBodyConfig(setting("service_body_id"));
    }

    if (isset($config)) {
        $sid = $config->twilio_account_sid;
        $token = $config->twilio_auth_token;
    } else {
        $sid = $GLOBALS["twilio_account_sid"];
        $token = $GLOBALS["twilio_auth_token"];
    }

    $_SESSION["twilio_account_sid"] = $sid;
    $_SESSION["twilio_auth_token"] = $token;
}


try {
    $twilioClient = new Client( $sid, $token );
} catch ( \Twilio\Exceptions\ConfigurationException $e ) {
    error_log("Missing Twilio Credentials");
}
