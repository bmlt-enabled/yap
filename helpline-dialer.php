<?php
include 'config.php';
include 'functions.php';
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

class CallConfig {
    public $phone_number;
    public $options;
}

function getCallConfig($client, $serviceBodyConfiguration) {
    $tracker            = !isset( $_REQUEST["tracker"] ) ? 0 : $_REQUEST["tracker"];
    $numbers            = $client->incomingPhoneNumbers->read( array( "phoneNumber" => $_REQUEST['Caller'] ) );
    $voice_url          = $numbers[0]->voiceUrl;
    $webhook_url        = substr( $voice_url, 0, strrpos( $voice_url, "/" ) );

    if ( $serviceBodyConfiguration->forced_caller_id_enabled ) {
        $caller_id = $serviceBodyConfiguration->forced_caller_id_number;
    } else {
        $caller_id = isset( $_REQUEST["Caller"] ) ? $_REQUEST["Caller"] : "0000000000";
    }

    $config = new CallConfig();
    // TODO: Can specify algorithm by service body.
    $config->phone_number = getHelplineVolunteer( $serviceBodyConfiguration->service_body_id, $tracker, CycleAlgorithm::LOOP_FOREVER );
    $config->options = array(
        'url'                  => $webhook_url . '/helpline-outdial-response.php?conference_name=' . $_REQUEST['FriendlyName'],
        'statusCallback'       => $webhook_url . '/helpline-dialer.php?service_body_id=' . $serviceBodyConfiguration->service_body_id . '&tracker=' . ++ $tracker . '&FriendlyName=' . $_REQUEST['FriendlyName'],
        'statusCallbackEvent'  => 'completed',
        'statusCallbackMethod' => 'GET',
        'timeout'              => 20,
        'callerId'             => $caller_id
    );

    return $config;
}

$service_body_id     = $_REQUEST['service_body_id'];
$serviceBodyConfiguration = getServiceBodyConfiguration($service_body_id);

$sid                 = $GLOBALS['twilio_account_sid'];
$token               = $GLOBALS['twilio_auth_token'];
try {
    $client = new Client( $sid, $token );
} catch ( \Twilio\Exceptions\ConfigurationException $e ) {
    error_log("Missing Twilio Credentials");
}

if (isset($_REQUEST["Debug"])) {
    echo var_dump(getCallConfig($client, $serviceBodyConfiguration));
    exit();
}

$conferences = $client->conferences->read( array ("friendlyName" => $_REQUEST['FriendlyName'] ) );
if (count($conferences) > 0 && $conferences[0]->status != "completed") {
    // Make timeout configurable per volunteer
    if ( ( isset( $_REQUEST['SequenceNumber'] ) && intval( $_REQUEST['SequenceNumber'] ) == 1 ) ||
         ( isset( $_REQUEST['CallStatus'] ) && ( $_REQUEST['CallStatus'] == 'no-answer' || $_REQUEST['CallStatus'] == 'completed' ) ) ) {
        $callConfig = getCallConfig($client, $serviceBodyConfiguration);
        error_log( "Dialing " . $callConfig->phone_number );

        try {
            $client->calls->create(
                $callConfig->phone_number,
                $callConfig->options['callerId'],
                $callConfig->options
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
