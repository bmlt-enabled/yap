<?php
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$serviceBodyConfiguration = getServiceBodyConfiguration(setting("service_body_id"));

if ($serviceBodyConfiguration->primary_contact_enabled) {
    $sid                        = $GLOBALS['twilio_account_sid'];
    $token                      = $GLOBALS['twilio_auth_token'];
    try {
        $client = new Client( $sid, $token );
    } catch ( \Twilio\Exceptions\ConfigurationException $e ) {
        error_log("Missing Twilio Credentials");
    }

    $serviceBodyName = getServiceBody(setting("service_body_id"))->name;

    $client->messages->create(
        $serviceBodyConfiguration->primary_contact_number,
        array(
            "from" => $_REQUEST["caller_id"],
            "body" => "You have a message from the " . $serviceBodyName . " helpline from caller " . $_REQUEST["caller_number"] . ", " . $_REQUEST["RecordingUrl"]
        )
    );
}
