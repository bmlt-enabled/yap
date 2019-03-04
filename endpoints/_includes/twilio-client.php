<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Twilio\Rest\Client;

if (!isset($_SESSION["twilio_account_sid"]) && !isset($_SESSION["twilio_auth_token"])) {
    $_SESSION["twilio_account_sid"] = $GLOBALS["twilio_account_sid"];
    $_SESSION["twilio_auth_token"] = $GLOBALS["twilio_auth_token"];
}

try {
    $twilioClient = new Client($_SESSION["twilio_account_sid"], $_SESSION["twilio_auth_token"]);
} catch (\Twilio\Exceptions\ConfigurationException $e) {
    error_log("Missing Twilio Credentials");
}
