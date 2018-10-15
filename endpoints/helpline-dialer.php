<?php
require_once 'functions.php';
require_once 'twilio-client.php';

class CallConfig {
    public $phone_number;
    public $voicemail_url;
    public $options;
}

function getCallConfig($twilioClient, $serviceBodyConfiguration) {
    $tracker            = !isset( $_REQUEST["tracker"] ) ? 0 : $_REQUEST["tracker"];
    $voice_url          = "https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    if (strpos(basename($voice_url), ".php")) {
        $webhook_url = substr( $voice_url, 0, strrpos( $voice_url, "/" ) );
    } else if (strpos($voice_url, "?")) {
        $webhook_url = substr( $voice_url, 0, strrpos( $voice_url, "?" ) );
    } else {
        $webhook_url = $voice_url;
    }

    if ( $serviceBodyConfiguration->forced_caller_id_enabled ) {
        $caller_id = $serviceBodyConfiguration->forced_caller_id_number;
    } else {
        $caller_id = isset( $_REQUEST["Caller"] ) ? $_REQUEST["Caller"] : SpecialPhoneNumber::UNKNOWN;
    }

    if (isset( $_REQUEST["OriginalCallerId"] )) {
        $original_caller_id = $_REQUEST["OriginalCallerId"];
    } elseif (isset($_REQUEST["Caller"])) {
        $original_caller_id = $_REQUEST["Caller"];
    } else {
        $original_caller_id = SpecialPhoneNumber::UNKNOWN;
    }

    $config = new CallConfig();
    $config->phone_number = getHelplineVolunteer( $serviceBodyConfiguration->service_body_id, $tracker, $serviceBodyConfiguration->call_strategy, VolunteerType::PHONE );
    $config->voicemail_url = $webhook_url . '/voicemail.php?service_body_id=' . $serviceBodyConfiguration->service_body_id . '&caller_id=' . trim($caller_id);
    $config->options = array(
        'url'                  => $webhook_url . '/helpline-outdial-response.php?conference_name=' . $_REQUEST['FriendlyName'],
        'statusCallback'       => $webhook_url . '/helpline-dialer.php?service_body_id=' . $serviceBodyConfiguration->service_body_id . '&tracker=' . ++$tracker . '&FriendlyName=' . $_REQUEST['FriendlyName'] . '&OriginalCallerId=' . trim($original_caller_id),
        'statusCallbackEvent'  => 'completed',
        'statusCallbackMethod' => 'GET',
        'timeout'              => $serviceBodyConfiguration->call_timeout,
        'callerId'             => $caller_id,
        'originalCallerId'     => $original_caller_id
    );

    return $config;
}

$service_body_id            = setting('service_body_id');
$serviceBodyConfiguration   = getServiceBodyConfiguration($service_body_id);

if (isset($_REQUEST["Debug"])) {
    echo var_dump(getCallConfig($twilioClient, $serviceBodyConfiguration));
    exit();
}

$conferences = $twilioClient->conferences->read( array ("friendlyName" => $_REQUEST['FriendlyName'] ) );
if (count($conferences) > 0 && $conferences[0]->status != "completed") {
    // Make timeout configurable per volunteer
    if ( ( isset( $_REQUEST['SequenceNumber'] ) && intval( $_REQUEST['SequenceNumber'] ) == 1 ) ||
         ( isset( $_REQUEST['CallStatus'] ) && ( $_REQUEST['CallStatus'] == 'no-answer' || $_REQUEST['CallStatus'] == 'completed' ) ) ) {
        $callConfig = getCallConfig($twilioClient, $serviceBodyConfiguration);
        error_log( "Dialing " . $callConfig->phone_number );

        $participants = $twilioClient->conferences($conferences[0]->sid)->participants->read();

        // Do not call if the caller hung up.
        if (count($participants) > 0) {
            $callerSid = $participants[0]->callSid;
            $callerNumber = $twilioClient->calls( $callerSid )->fetch()->from;
            if (strpos($callerNumber, "+") !== 0) {
                $callerNumber .= "+" . $callerNumber;
            }
            if ($callConfig->phone_number == SpecialPhoneNumber::VOICE_MAIL || $callConfig->phone_number == SpecialPhoneNumber::UNKNOWN) {
                $twilioClient->calls($callerSid)->update(array(
                    "method" => "GET",
                    "url" => $callConfig->voicemail_url . "&caller_number=" . $callerNumber
                ));
            } else {
                try {
                    if ( $serviceBodyConfiguration->volunteer_sms_notification_enabled ) {
                        $twilioClient->messages->create(
                            $callConfig->phone_number,
                            array(
                                "body" => "You have an incoming helpline call from " . $callerNumber . ".",
                                "from" => $callConfig->options['originalCallerId']
                            ) );
                    }

                    $twilioClient->calls->create(
                        $callConfig->phone_number,
                        $callConfig->options['callerId'],
                        $callConfig->options
                    );
                } catch ( \Twilio\Exceptions\TwilioException $e ) {
                    error_log( $e );
                }
            }
        }
    } elseif ( isset( $_REQUEST['StatusCallbackEvent'] ) && $_REQUEST['StatusCallbackEvent'] == 'participant-leave' ) {
        $conference_sid          = $conferences[0]->sid;
        $conference_participants = $twilioClient->conferences( $conference_sid )->participants;
        foreach ( $conference_participants as $participant ) {
            try {
                $twilioClient->calls( $participant->callSid )->update( array( $status => 'completed' ) );
            } catch ( \Twilio\Exceptions\TwilioException $e ) {
                error_log($e);
            }
        }
    }
}
