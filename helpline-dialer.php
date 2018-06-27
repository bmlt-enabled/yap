<?php
include 'config.php';
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

$sid                 = $GLOBALS['twilio_account_sid'];
$token               = $GLOBALS['twilio_auth_token'];
$client              = new Client( $sid, $token );
$tracker             = isset( $_REQUEST["tracker"] ) ? intval( $_REQUEST["tracker"] ) + 1 : 0;
$service_body_id     = $_REQUEST['service_body_id'];
$phone_number        = getHelplineVolunteer( $service_body_id, $tracker );

$numbers = $client->incomingPhoneNumbers->read(
    array( "phoneNumber" => $_REQUEST['Caller'] ) );

$voice_url   = $numbers[0]->voiceUrl;
$webhook_url = substr( $voice_url, 0, strrpos( $voice_url, "/" ) );

// Make timeout configurable per volunteer
if ((isset($_REQUEST['SequenceNumber']) && intval($_REQUEST['SequenceNumber']) == 1) ||
    (isset($_REQUEST['CallStatus']) && $_REQUEST['CallStatus'] == 'no-answer')) {
    $client->calls->create(
        $phone_number,
        $_REQUEST['Caller'],
        array(
            'url'                  => $webhook_url . '/helpline-outdial-response.php?conference-name=' . $_REQUEST['FriendlyName'],
            'statusCallback'       => $webhook_url . '/helpline-dialer.php?service_body_id=' . $service_body_id . '&tracker=' . $tracker . '&FriendlyName=' . $_REQUEST['FriendlyName'],
            'statusCallbackMethod' => 'GET',
            'timeout'              => 10
        )
    );
} elseif (isset($_REQUEST['CallStatus']) && $_REQUEST['CallStatus'] == 'completed') {
    $conferences = $client->conferences->read(
        array ("friendlyName" => $_REQUEST['FriendlyName'] ) );
    $conference_sid = $conferences[0]->sid;
    $conference_participants = $client->conferences($conference_sid)->participants;
    foreach ($conference_participants as $participant) {
        $client->calls($participant->callSid)->update(array($status => 'completed'));
    }
}

error_log("Dialing " . $phone_number);
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";