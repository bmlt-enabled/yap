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
    $config->phone_number = getHelplineVolunteer( $serviceBodyConfiguration->service_body_id,
        $tracker,
        $serviceBodyConfiguration->call_strategy,
        VolunteerType::PHONE,
        isset($_SESSION['Gender']) ? $_SESSION['Gender'] : VolunteerGender::UNSPECIFIED);
    $config->voicemail_url = $webhook_url . '/voicemail.php?service_body_id=' . $serviceBodyConfiguration->service_body_id . '&caller_id=' . trim($caller_id) . getSessionLink();
    $config->options = array(
        'url'                  => $webhook_url . '/helpline-outdial-response.php?conference_name=' . $_REQUEST['FriendlyName'] . '&service_body_id=' . $serviceBodyConfiguration->service_body_id,
        'statusCallback'       => $serviceBodyConfiguration->call_strategy == CycleAlgorithm::BLASTING
            ? ($webhook_url . '/helpline-dialer.php?noop=1' . getSessionLink())
            : ($webhook_url . '/helpline-dialer.php?service_body_id=' . $serviceBodyConfiguration->service_body_id
                . ('&tracker=' . ++$tracker)
                . ('&FriendlyName=' . $_REQUEST['FriendlyName'])
                . ('&OriginalCallerId=' . trim($original_caller_id))
                . (getSessionLink())),
        'statusCallbackEvent'  => 'completed',
        'statusCallbackMethod' => 'GET',
        'timeout'              => $serviceBodyConfiguration->call_timeout,
        'callerId'             => $caller_id,
        'originalCallerId'     => $original_caller_id
    );

    return $config;
}

if (isset($_REQUEST['noop'])) {
    exit();
}

$service_body_id            = setting('service_body_id');
$serviceBodyConfiguration   = getServiceBodyConfiguration($service_body_id);

if (isset($_REQUEST['Debug']) && intval($_REQUEST['Debug']) == 1) {
    echo var_dump(getCallConfig($twilioClient, $serviceBodyConfiguration));
    exit();
}

$conferences = $twilioClient->conferences->read( array ("friendlyName" => $_REQUEST['FriendlyName'] ) );
if (count($conferences) > 0 && $conferences[0]->status != "completed") {
    if (isset( $_REQUEST['StatusCallbackEvent'] ) && $_REQUEST['StatusCallbackEvent'] == 'participant-join') {
        setConferenceParticipant($conferences[0]->sid, $_REQUEST['CallSid'], $_REQUEST['FriendlyName']);
    }

    // Make timeout configurable per volunteer
    if ( ( isset( $_REQUEST['SequenceNumber'] ) && intval( $_REQUEST['SequenceNumber'] ) == 1 ) ||
         ( isset( $_REQUEST['CallStatus'] ) && ( $_REQUEST['CallStatus'] == 'no-answer' || $_REQUEST['CallStatus'] == 'completed' ) ) ) {
        $callConfig = getCallConfig($twilioClient, $serviceBodyConfiguration);
        log_debug("Next volunteer to call " . $callConfig->phone_number);

        $participants = $twilioClient->conferences($conferences[0]->sid)->participants->read();

        // Do not call if the caller hung up.
        if (count($participants) > 0) {
            try {
                $callerSid = $participants[0]->callSid;
            	$callerNumber = $twilioClient->calls( $callerSid )->fetch()->from;
                if (strpos($callerNumber, "+") !== 0) {
                    $callerNumber .= "+" . trim($callerNumber);
                }
                log_debug("callerNumber: " . $callerNumber . ", callerSid: " . $callerSid);
                if ($callConfig->phone_number == SpecialPhoneNumber::VOICE_MAIL || $callConfig->phone_number == SpecialPhoneNumber::UNKNOWN) {
                    log_debug("Calling voicemail.");
                    $twilioClient->calls($callerSid)->update(array(
                        "method" => "GET",
                        "url" => $callConfig->voicemail_url . "&caller_number=" . $callerNumber
                    ));
                } else {
                    foreach (explode(",", $callConfig->phone_number) as $volunteer_number) {
                        if ($serviceBodyConfiguration->volunteer_sms_notification_enabled) {
                            log_debug("Sending volunteer SMS notification: " . $callConfig->phone_number);
                            $twilioClient->messages->create(
                                $volunteer_number,
                                array(
                                    "body" => "You have an incoming helpline call from " . $callerNumber . ".",
                                    "from" => $callConfig->options['originalCallerId']
                                ));
                        }

                        log_debug("Calling: " . $callConfig->phone_number);
                        $twilioClient->calls->create(
                            $volunteer_number,
                            $callConfig->options['callerId'],
                            $callConfig->options
                        );
                    }
                }
            } catch ( \Twilio\Exceptions\TwilioException $e ) {
                log_debug( $e );
            }
        }
    } elseif ( isset( $_REQUEST['StatusCallbackEvent'] ) && $_REQUEST['StatusCallbackEvent'] == 'participant-leave' ) {
        $conference_sid          = $conferences[0]->sid;
        $conference_participants = $twilioClient->conferences( $conference_sid )->participants;
        foreach ( $conference_participants as $participant ) {
            try {
                log_debug("Someone left the conference: " . $participant->callSid);
                $twilioClient->calls( $participant->callSid )->update( array( $status => 'completed' ) );
            } catch ( \Twilio\Exceptions\TwilioException $e ) {
                error_log($e);
            }
        }
    }
}
