<?php
include 'config.php';
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

try {
    $sid                        = $GLOBALS['twilio_account_sid'];
    $token                      = $GLOBALS['twilio_auth_token'];

    try {
        $client = new Client( $sid, $token );
    } catch ( \Twilio\Exceptions\ConfigurationException $e ) {
        error_log("Missing Twilio Credentials");
    }

    if (isset( $_REQUEST["OriginalCallerId"] )) {
        $original_caller_id = $_REQUEST["OriginalCallerId"];
    }

    $service_body = getServiceBodyCoverage( $_REQUEST['Latitude'], $_REQUEST['Longitude'] );
    $serviceBodyConfiguration   = getServiceBodyConfiguration($service_body->id);
    $tracker                    = !isset( $_REQUEST["tracker"] ) ? 0 : $_REQUEST["tracker"];

    if ($serviceBodyConfiguration->sms_routing_enabled) {
        $phone_numbers = explode(',', getHelplineVolunteer( $serviceBodyConfiguration->service_body_id, $tracker, $serviceBodyConfiguration->sms_strategy, VolunteerType::SMS ));

        $client->messages->create(
            $original_caller_id,
            array(
                "body" => "Thank you and your request has been received.  A volunteer should be responding to you shortly.",
                "from" => $_REQUEST['To']
            ) );

        foreach ($phone_numbers as $phone_number) {
            if ($phone_number == SpecialPhoneNumber::UNKNOWN) {
                $phone_number = $serviceBodyConfiguration->primary_contact_number;
            }

            $client->messages->create(
                $phone_number,
                array(
                    "body" => "Helpline: Someone is requesting SMS help from " . $original_caller_id . ", please text or call them back.",
                    "from" => $_REQUEST['To']
                ) );
        }
    }
} catch ( Exception $e ) {
    $client->messages->create(
        $original_caller_id,
        array(
            "body" => "Could not find a volunteer for the location, please retry your request.",
            "from" => $_REQUEST['To']
        ) );
}
