<?php
require_once '../lib/CallConfig.php';
use app\bmltenabled\yap\CallConfig;

require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';

function getCallConfig($twilioClient, $serviceBodyCallHandling, $tandem = VolunteerShadowOption::UNSPECIFIED)
{
    $tracker            = !isset($_REQUEST["tracker"]) ? 0 : $_REQUEST["tracker"];

    $caller_id = getOutboundDialingCallerId($serviceBodyCallHandling);

    if (isset($_REQUEST["OriginalCallerId"])) {
        $original_caller_id = $_REQUEST["OriginalCallerId"];
    } elseif (isset($_REQUEST["Caller"])) {
        $original_caller_id = $_REQUEST["Caller"];
    } else {
        $original_caller_id = SpecialPhoneNumber::UNKNOWN;
    }

    $config = new CallConfig();
    $volunteer_routing_parameters = new VolunteerRoutingParameters();
    $volunteer_routing_parameters->service_body_id = $serviceBodyCallHandling->service_body_id;
    $volunteer_routing_parameters->tracker = $tracker;
    $volunteer_routing_parameters->cycle_algorithm = $tandem == VolunteerShadowOption::TRAINEE ? CycleAlgorithm::BLASTING : $serviceBodyCallHandling->call_strategy;
    $volunteer_routing_parameters->volunteer_type = VolunteerType::PHONE;
    $volunteer_routing_parameters->volunteer_gender = isset($_SESSION['Gender']) ? $_SESSION['Gender'] : VolunteerGender::UNSPECIFIED;
    $volunteer_routing_parameters->volunteer_shadow = $tandem == VolunteerShadowOption::TRAINEE ? VolunteerShadowOption::TRAINER : VolunteerShadowOption::UNSPECIFIED;
    $volunteer_routing_parameters->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_routing_parameters->volunteer_language = VolunteerLanguage::UNSPECIFIED;
    if ($tandem == VolunteerShadowOption::UNSPECIFIED) {
        $_SESSION["volunteer_routing_parameters"] = $volunteer_routing_parameters;
    }
    $config->volunteer_routing_params = $volunteer_routing_parameters;
    $volunteer = getHelplineVolunteer($config->volunteer_routing_params);
    $config->volunteer = $volunteer;
    $config->options = array(
        'url'  => $tandem !== VolunteerShadowOption::TRAINEE
            ? (getWebhookUrl() . '/helpline-outdial-response.php?conference_name=' . $_REQUEST['FriendlyName'] . '&service_body_id=' . $serviceBodyCallHandling->service_body_id . getSessionLink())
            : (getWebhookUrl() . '/tandem-answer-response.php?conference_name=' . $_REQUEST['FriendlyName'] . '&service_body_id=' . $serviceBodyCallHandling->service_body_id . getSessionLink()),
        'statusCallback'       => $serviceBodyCallHandling->call_strategy == CycleAlgorithm::BLASTING
            ? (getWebhookUrl() . '/helpline-dialer.php?noop=1' . getSessionLink())
            : (getWebhookUrl() . '/helpline-dialer.php?service_body_id=' . $serviceBodyCallHandling->service_body_id
                . ('&tracker=' . ++$tracker)
                . ('&FriendlyName=' . $_REQUEST['FriendlyName'])
                . ('&OriginalCallerId=' . trim($original_caller_id))
                . (getSessionLink())),
        'statusCallbackEvent'  => 'completed',
        'statusCallbackMethod' => 'GET',
        'timeout'              => $serviceBodyCallHandling->call_timeout,
        'callerId'             => $caller_id,
        'originalCallerId'     => $original_caller_id
    );
    $config->voicemail_url = getWebhookUrl() . '/voicemail.php?service_body_id=' . $serviceBodyCallHandling->service_body_id . '&caller_id=' . trim($config->options['caller_id']) . getSessionLink();
    if (!isset($_SESSION['ActiveVolunteer'])) {
        $_SESSION['ActiveVolunteer'] = $volunteer;
    }

    return $config;
}

if (isset($_REQUEST['noop'])) {
    exit();
}

$service_body_id = setting('service_body_id');
$serviceBodyCallHandling = getServiceBodyCallHandling($service_body_id);

if (isset($_REQUEST['Debug']) && intval($_REQUEST['Debug']) == 1) {
    echo var_dump(getCallConfig($twilioClient, $serviceBodyCallHandling));
    exit();
}

$conferences = $twilioClient->conferences->read(array ("friendlyName" => $_REQUEST['FriendlyName'] ));
if (count($conferences) > 0 && $conferences[0]->status != "completed") {
    $tandem = 0;
    $sms_body = "You have an incoming helpline call from ";
    if (isset($_REQUEST['StatusCallbackEvent']) && $_REQUEST['StatusCallbackEvent'] == 'participant-join') {
        setConferenceParticipant($conferences[0]->sid, $_REQUEST['CallSid'], $_REQUEST['FriendlyName']);

        if (isset($_SESSION["ActiveVolunteer"])) {
            $volunteer = $_SESSION["ActiveVolunteer"];
            if (isset($volunteer->volunteerInfo) && $volunteer->volunteerInfo->shadow == VolunteerShadowOption::TRAINEE) {
                $_REQUEST['SequenceNumber'] = 1;
                $sms_body = "You have an incoming helpline trainee call.  The originating helpline call is from ";
                $_SESSION["ActiveVolunteer"] = null;
                $tandem = 1;
            }
        }
    }

    // Make timeout configurable per volunteer
    if (( isset($_REQUEST['SequenceNumber']) && intval($_REQUEST['SequenceNumber']) == 1 ) ||
         ( isset($_REQUEST['CallStatus']) && ( $_REQUEST['CallStatus'] == 'no-answer' || $_REQUEST['CallStatus'] == 'completed' ) ) ) {
        $callConfig = getCallConfig($twilioClient, $serviceBodyCallHandling, $tandem);
        log_debug("Next volunteer to call " . $callConfig->volunteer->phoneNumber);

        $participants = $twilioClient->conferences($conferences[0]->sid)->participants->read();

        // Do not call if the caller hung up.
        if (count($participants) == 1 || (count($participants) > 0 && $tandem == VolunteerShadowOption::TRAINEE)) {
            try {
                $callerSid = $participants[0]->callSid;
                $callerNumber = $twilioClient->calls($callerSid)->fetch()->from;
                if (strpos($callerNumber, "+") !== 0) {
                    $callerNumber .= "+" . trim($callerNumber);
                }
                log_debug("callerNumber: " . $callerNumber . ", callerSid: " . $callerSid);
                if ($callConfig->volunteer->phoneNumber == SpecialPhoneNumber::VOICE_MAIL || $callConfig->volunteer->phoneNumber == SpecialPhoneNumber::UNKNOWN) {
                    log_debug("Calling voicemail.");
                    $twilioClient->calls($callerSid)->update(array(
                        "method" => "GET",
                        "url" => $callConfig->voicemail_url . "&caller_number=" . $callerNumber
                    ));
                } else {
                    foreach (explode(",", $callConfig->volunteer->phoneNumber) as $volunteer_number) {
                        if ($serviceBodyCallHandling->volunteer_sms_notification_enabled) {
                            log_debug("Sending volunteer SMS notification: " . $callConfig->volunteer->phoneNumber);
                            $twilioClient->messages->create(
                                $volunteer_number,
                                array(
                                    "body" => $sms_body . $callerNumber,
                                    "from" => $callConfig->options['callerId']
                                )
                            );
                        }

                        log_debug("Calling: " . $callConfig->volunteer->phoneNumber);
                        $twilioClient->calls->create(
                            $volunteer_number,
                            $callConfig->options['callerId'],
                            $callConfig->options
                        );
                    }
                }
            } catch (\Twilio\Exceptions\TwilioException $e) {
                log_debug($e);
            }
        }
    } elseif (isset($_REQUEST['StatusCallbackEvent']) && $_REQUEST['StatusCallbackEvent'] == 'participant-leave') {
        $conference_sid          = $conferences[0]->sid;
        $conference_participants = $twilioClient->conferences($conference_sid)->participants;
        foreach ($conference_participants as $participant) {
            try {
                log_debug("Someone left the conference: " . $participant->callSid);
                $twilioClient->calls($participant->callSid)->update(array( $status => 'completed' ));
            } catch (\Twilio\Exceptions\TwilioException $e) {
                error_log($e);
            }
        }
    }
}
