<?php
require_once __DIR__ . '/../../vendor/autoload.php';

try {
    if (!array_key_exists("twilioClient", $GLOBALS)) {
        $twilioClient = new Twilio\Rest\Client(setting("twilio_account_sid"), setting("twilio_auth_token"));
        $GLOBALS['twilioClient'] = $twilioClient;
    }
} catch (\Twilio\Exceptions\ConfigurationException $e) {
    error_log("Missing Twilio Credentials");
    throw $e;
}

function hup($callSid)
{
    $GLOBALS['twilioClient']->calls($callSid)->update(array('status' => 'completed'));
}
