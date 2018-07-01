<?php
include 'config.php';
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$tracker             = isset( $_REQUEST["tracker"] ) ? intval( $_REQUEST["tracker"] ) + 1 : 0;
$service_body_id     = $_REQUEST['service_body_id'];
// TODO: Can specify algorithm by service body.
$phone_number        = getHelplineVolunteer( $service_body_id, $tracker, CycleAlgorithm::LOOP_FOREVER );

if (isset($_REQUEST["Debug"]) && $_REQUEST["Debug"]) {
    echo "<Response><Dial><Number>" . $phone_number ."</Number></Dial></Response>";
    exit();
}

$sid                 = $GLOBALS['twilio_account_sid'];
$token               = $GLOBALS['twilio_auth_token'];
try {
    $client = new Client( $sid, $token );
} catch ( \Twilio\Exceptions\ConfigurationException $e ) {
    error_log("Missing Twilio Credentials");
}

$numbers = $client->incomingPhoneNumbers->read( array( "phoneNumber" => $_REQUEST['Caller'] ) );
$voice_url   = $numbers[0]->voiceUrl;
$webhook_url = substr( $voice_url, 0, strrpos( $voice_url, "/" ) );
$conferences = $client->conferences->read( array ("friendlyName" => $_REQUEST['FriendlyName'] ) );

if (count($conferences) > 0 && $conferences[0]->status != "completed") {
    // Make timeout configurable per volunteer
    if ( ( isset( $_REQUEST['SequenceNumber'] ) && intval( $_REQUEST['SequenceNumber'] ) == 1 ) ||
         ( isset( $_REQUEST['CallStatus'] ) && ( $_REQUEST['CallStatus'] == 'no-answer' || $_REQUEST['CallStatus'] == 'completed' ) ) ) {
        error_log( "Dialing " . $phone_number );
        try {
            $client->calls->create(
                $phone_number,
                $_REQUEST['Caller'],
                array(
                    'url'                  => $webhook_url . '/helpline-outdial-response.php?conference_name=' . $_REQUEST['FriendlyName'],
                    'statusCallback'       => $webhook_url . '/helpline-dialer.php?service_body_id=' . $service_body_id . '&tracker=' . $tracker . '&FriendlyName=' . $_REQUEST['FriendlyName'],
                    'statusCallbackEvent'  => 'completed',
                    'statusCallbackMethod' => 'GET',
                    'timeout'              => 10
                )
            );
        } catch ( \Twilio\Exceptions\TwilioException $e ) {
            error_log($e);
        }
    } elseif ( isset( $_REQUEST['StatusCallbackEvent'] ) && $_REQUEST['StatusCallbackEvent'] == 'participant-leave' ) {
        $conference_sid          = $conferences[0]->sid;
        $conference_participants = $client->conferences( $conference_sid )->participants;
        foreach ( $conference_participants as $participant ) {
            try {
                $client->calls( $participant->callSid )->update( array( $status => 'completed' ) );
            } catch ( \Twilio\Exceptions\TwilioException $e ) {
                error_log($e);
            }
        }
    }
}
