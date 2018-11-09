<?php
require_once __DIR__.'/../vendor/autoload.php';
use Twilio\Rest\Client;
$sid                        = $GLOBALS['twilio_account_sid'];
$token                      = $GLOBALS['twilio_auth_token'];
try {
    $twilioClient = new Client( $sid, $token );
} catch ( \Twilio\Exceptions\ConfigurationException $e ) {
    error_log("Missing Twilio Credentials");
}
