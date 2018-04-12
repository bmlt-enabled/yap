<?php
include '../config.php';
include '../functions.php';
require_once '../vendor/autoload.php';
use Twilio\Rest\Client;

if ($_REQUEST['SequenceNumber'] == 1) {
    $sid    = $GLOBALS['twilio_account_sid'];
    $token  = $GLOBALS['twilio_auth_token'];
    $client = new Client( $sid, $token );

    $statusCallbackEvent = $_REQUEST['StatusCallbackEvent'];
    $tracker             = isset( $_REQUEST["tracker"] ) ? intval( $_REQUEST["tracker"] ) + 1 : 0;
    $service_body_id     = $_REQUEST['service_body_id'];
    $phone_number        = getHelplineVolunteer( $service_body_id, $tracker );

    $numbers = $client->incomingPhoneNumbers->read(
        array( "phoneNumber" => $_REQUEST['called_number'] ) );

    $voice_url   = $numbers[0]->voiceUrl;
    $webhook_url = substr( $voice_url, 0, strrpos( $voice_url, "/" ) );

    $client->calls->create(
        $phone_number,
        $_REQUEST['called_number'],
        array( 'url' => $webhook_url . '/helpline-outdial-response.php?conference-name=' . $_REQUEST['FriendlyName'] ) );
}