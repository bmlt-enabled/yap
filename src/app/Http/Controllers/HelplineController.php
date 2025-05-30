<?php

namespace App\Http\Controllers;

use App\Constants\CallRole;
use App\Constants\CycleAlgorithm;
use App\Constants\EventId;
use App\Constants\SmsDialbackOptions;
use App\Constants\SpecialPhoneNumber;
use App\Constants\TwilioCallStatus;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;
use App\Services\CallService;
use App\Services\ConferenceService;
use App\Services\ConfigService;
use App\Services\GeocodingService;
use App\Services\MeetingResultsService;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Services\TwilioService;
use App\Services\VolunteerService;
use App\Structures\CallConfig;
use App\Structures\VolunteerRoutingParameters;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\TwilioException;
use Twilio\TwiML\VoiceResponse;

class HelplineController extends Controller
{
    protected ConfigService $config;
    protected VolunteerService $volunteers;
    protected SettingsService $settings;
    protected CallService $call;
    protected RootServerService $rootServer;
    protected GeocodingService $geocoding;
    protected MeetingResultsService $meetingResults;
    protected TwilioService $twilio;
    protected ConferenceService $conference;

    public function __construct(
        ConfigService    $config,
        VolunteerService $volunteers,
        SettingsService  $settings,
        CallService $call,
        RootServerService $rootServer,
        GeocodingService $geocoding,
        MeetingResultsService $meetingResults,
        TwilioService $twilio,
        ConferenceService $conference,
    ) {
        $this->config = $config;
        $this->volunteers = $volunteers;
        $this->settings = $settings;
        $this->call = $call;
        $this->rootServer = $rootServer;
        $this->geocoding = $geocoding;
        $this->meetingResults = $meetingResults;
        $this->twilio = $twilio;
        $this->conference = $conference;
    }

    public function search(Request $request)
    {
        $twiml = new VoiceResponse();
        $dial_string = "";

        if (!$request->has('ForceNumber')) {
            if (session()->has("override_service_body_id")) {
                $service_body_obj = $this->rootServer->getServiceBody($this->settings->get("service_body_id"));
            } else {
                // TODO: Address is set for the session if you are using Gender routing.  This needs to be cleaned up as some technical debt from the pre-laravel version.
                $address = session()->get('Address') ?? $this->call->getIvrResponse($request);
                $coordinates  = $this->geocoding->getCoordinatesForAddress($address);
                try {
                    if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
                        throw new Exception("Couldn't find an address for that location.");
                    }

                    $service_body_obj = $this->meetingResults
                        ->getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);

                    if ($service_body_obj == null) {
                        if ($this->settings->has("fallback_number")) {
                            $dial_string = $this->settings->get("fallback_number");
                            $exploded_result = explode("|", $dial_string);
                            $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
                            $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
                            $this->call->insertCallEventRecord(
                                EventId::HELPLINE_ROUTE,
                                (object)["helpline_number" => $phone_number, "extension" => $extension]
                            );
                            $dial = $twiml->dial();
                            $dial->number($phone_number)->setSendDigits($extension);
                            return response($twiml)->header("Content-Type", "text/xml; charset=utf-8");
                        } else {
                            throw new Exception("Couldn't find service body coverage for that location.");
                        }
                    }
                } catch (Exception $e) {
                    $twiml->redirect("input-method.php?Digits=" . $request->get("SearchType") . "&Retry=1&RetryMessage=" . urlencode($e->getMessage()))
                        ->setMethod("GET");
                    return response($twiml)->header("Content-Type", "text/xml; charset=utf-8");
                }
            }

            $location    = $service_body_obj->name;
            if (isset($service_body_obj->helpline)) {
                $dial_string = explode(":", $service_body_obj->helpline)[0];
            } else {
                $dial_string = $this->settings->has("fallback_number") ? $this->settings->get("fallback_number") : "0000000000";
            }

            $waiting_message = true;
            $captcha = false;
        } else {
            $dial_string = $request->get('ForceNumber');
            $waiting_message = isset($GLOBALS['force_dialing_notice']) || $request->has('WaitingMessage');
            $captcha = $request->has('Captcha');
            $captcha_verified = $request->has('CaptchaVerified');
        }

        $exploded_result = explode("|", $dial_string);
        $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
        $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
        $service_body_id = isset($service_body_obj) ? $service_body_obj->id : 0;
        $GLOBALS['service_body_id'] = $service_body_id;

        if (!$request->has('ForceNumber')
            && (session()->has("override_service_body_id")
                || $this->rootServer->isServiceBodyHelplingCallInternallyRoutable($coordinates->latitude, $coordinates->longitude))) {
            $serviceBodyCallHandling = $this->config->getCallHandling($service_body_id);
        }

        if ($request->has('ForceNumber')) {
            $this->call->insertCallEventRecord(
                EventId::VOLUNTEER_SEARCH_FORCE_DIALED,
                (object)['number'=>$request->get('ForceNumber')]
            );
        } else if (isset($address)) {
            $this->call->insertCallEventRecord(
                EventId::VOLUNTEER_SEARCH,
                (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
            );
        } else {
            $this->call->insertCallEventRecord(EventId::VOLUNTEER_SEARCH);
        }

        if ($service_body_id > 0 && isset($serviceBodyCallHandling)
            && $serviceBodyCallHandling->volunteer_routing_enabled) {
            if ($serviceBodyCallHandling->gender_routing_enabled && !session()->has('Gender')) {
                if (isset($address)) {
                    session()->put("Address", $address);
                }

                $searchType = $request->get("SearchType") ?? "-1";
                $twiml->redirect("gender-routing.php?SearchType=" . urlencode($searchType))->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml; charset=utf-8");
            } elseif ($serviceBodyCallHandling->volunteer_routing_redirect
                && $serviceBodyCallHandling->volunteer_routing_redirect_id > 0) {
                $calculated_service_body_id = $serviceBodyCallHandling->volunteer_routing_redirect_id;
                $serviceBodyCallHandling = $this->config->getCallHandling($calculated_service_body_id);
            } else {
                $calculated_service_body_id = $service_body_id;
            }

            if ($this->settings->get("announce_servicebody_volunteer_routing")) {
                $twiml->say(
                    sprintf(
                        "%s... %s %s",
                        $this->settings->word('please_stand_by'),
                        $this->settings->word('relocating_your_call_to'),
                        $location
                    )
                )
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            } else {
                $twiml->say($this->settings->word('please_wait_while_we_connect_your_call'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            }

            $dial = $twiml->dial();
            $dial->conference($this->conference->getConferenceName($calculated_service_body_id))
                ->setWaitUrl($serviceBodyCallHandling->moh_count == 1 ? $serviceBodyCallHandling->moh : "playlist.php?items=" . $serviceBodyCallHandling->moh)
                ->setStatusCallback("helpline-dialer.php?service_body_id=" . $calculated_service_body_id . "&Caller=" . $request->get('Called') . $this->settings->getSessionLink())
                ->setStartConferenceOnEnter("false")
                ->setEndConferenceOnExit("true")
                ->setStatusCallbackMethod("GET")
                ->setStatusCallbackEvent("start join end leave")
                ->setWaitMethod("GET")
                ->setBeep("false");
        } elseif ($phone_number != "") {
            if (!$request->has("ForceNumber")) {
                $twiml->say(
                    $this->settings->word('please_stand_by') . "... " . $this->settings->word('relocating_your_call_to') . "... " . $location
                )
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            } elseif ($request->has("ForceNumber")) {
                if ($captcha) {
                    $gather = $twiml->gather()
                        ->setLanguage($this->settings->get('gather_language'))
                        ->setHints($this->settings->get('gather_hints'))
                        ->setInput("dtmf")
                        ->setTimeout(15)
                        ->setNumDigits(1)
                        ->setAction(("helpline-search.php?CaptchaVerified=1&ForceNumber="
                        . urlencode($request->get('ForceNumber'))
                        . ($this->settings->getSessionLink())
                        . ($waiting_message ? "&WaitingMessage=1" : "")));

                    $gather->say($this->settings->get('title') .  "..." . $this->settings->word('press_any_key_to_continue'))
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get('language'));
                    $twiml->hangup();
                } elseif ($waiting_message) {
                    $twiml->say(!$captcha_verified ? $this->settings->get('title') : ""
                        .  $this->settings->word('please_wait_while_we_connect_your_call'))
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get('language'));
                }
            }
            $this->call->insertCallEventRecord(
                EventId::HELPLINE_ROUTE,
                (object)["helpline_number" => $phone_number, "extension" => $extension]
            );
            $dial = $twiml->dial();
            $dial->number($phone_number)
                ->setSendDigits($extension);
        } else {
            $twiml->redirect("input-method.php?Digits="
                . urlencode($request->get("SearchType"))
                . "&Retry=1&RetryMessage="
                . urlencode($this->settings->word('the_location_you_entered_is_not_found')))
                ->setMethod("GET");
        }

        return response($twiml)->header("Content-Type", "text/xml; charset=utf-8");
    }

    public function dial(Request $request)
    {
        if ($request->has('noop')) {
            if ($request->has('CallStatus') &&
                ($request->get('CallStatus') == TwilioCallStatus::NOANSWER
                    || $request->get('CallStatus') == TwilioCallStatus::FAILED
                    || $request->get('CallStatus') == TwilioCallStatus::BUSY)) {
                $this->twilio->incrementNoAnswerCount();
            }

            return response([])->header("Content-Type", "application/json");
        }

        $serviceBodyCallHandling = $this->config->getCallHandling($this->settings->get('service_body_id'));

        if ($request->has('Debug') && intval($request->get('Debug')) == 1) {
            return response()
                ->json($this->getCallConfig($request, $serviceBodyCallHandling))
                ->header("Content-Type", "application/json");
        }

        // Sometime in August 2023, Twilio introduced a change in API Behavior. The conferences API
        // now seems to be eventually consistent. Sometimes we get the conference back on the first
        // try, and other times it takes a few tries. Retrying every half second seems to get the
        // job done after no more than about 4 retries in the worst case. We try up to 10 times just
        // to be safe.
        for ($i = 0; $i < 10; $i++) {
            $conferences = $this->twilio->client()
                ->conferences
                ->read(array("friendlyName" => $request->get('FriendlyName')));

            if ($i > 0) {
                Log::debug("conferences eventual consistency issue, retry $i");
            }

            if (count($conferences)) {
                break;
            }

            sleep(0.5);
        }

        if (count($conferences) > 0 && $conferences[0]->status != TwilioCallStatus::COMPLETED) {
            $sms_body = $this->settings->word('you_have_an_incoming_phoneline_call_from') . " ";

            if ($request->has('StatusCallbackEvent') && $request->get('StatusCallbackEvent') == 'participant-join' &&
                ($request->has('SequenceNumber') && intval($request->get('SequenceNumber')) == 1 )) {
                $this->call->setConferenceParticipant(
                    $request->get('FriendlyName'),
                    $request->get('CallSid'),
                    CallRole::CALLER
                );
                $this->call->insertCallEventRecord(EventId::CALLER_IN_CONFERENCE);
            }

            // TODO: Make timeout configurable per volunteer
            if (( $request->has('SequenceNumber') && intval($request->get('SequenceNumber')) == 1 ) ||
                ( $request->has('CallStatus') &&
                    ($request->get('CallStatus') == TwilioCallStatus::NOANSWER
                        || $request->get('CallStatus') == TwilioCallStatus::COMPLETED
                        || $request->get('CallStatus') == TwilioCallStatus::FAILED
                        || $request->get('CallStatus') == TwilioCallStatus::BUSY ))) {
                $callConfig = $this->getCallConfig($request, $serviceBodyCallHandling);

                if ($request->has('CallStatus') && $request->get('CallStatus') == TwilioCallStatus::NOANSWER) {
                    $this->call->insertCallEventRecord(EventId::VOLUNTEER_NOANSWER, (object)['to_number' => $request->get('Called')]);
                    $this->call->setConferenceParticipant(
                        $request->get('FriendlyName'),
                        $request->get('CallSid'),
                        CallRole::VOLUNTEER
                    );
                } else if ($request->has('CallStatus') && $request->get('CallStatus') == TwilioCallStatus::FAILED) {
                    Log::error(sprintf("Volunteer Call Failed %s: %s", $request->get('Called'), $request->get('ErrorMessage')));
                    $this->call->insertCallEventRecord(EventId::VOLUNTEER_NUMBER_BAD, (object)['to_number' => $request->get('Called'), 'error' => $request->get('ErrorMessage')]);
                    $this->call->setConferenceParticipant(
                        $request->get('FriendlyName'),
                        $request->get('CallSid'),
                        CallRole::VOLUNTEER
                    );
                } else if ($request->has('CallStatus') && $request->get('CallStatus') == TwilioCallStatus::BUSY) {
                    Log::error(sprintf("Volunteer Call Busy %s: %s", $request->get('Called'), $request->get('ErrorMessage')));
                    $this->call->insertCallEventRecord(EventId::VOLUNTEER_NUMBER_BUSY, (object)['to_number' => $request->get('Called')]);
                    $this->call->setConferenceParticipant(
                        $request->get('FriendlyName'),
                        $request->get('CallSid'),
                        CallRole::VOLUNTEER
                    );
                }

                Log::debug("Next volunteer to call " . $callConfig->volunteer->phoneNumber);
                $participants = $this->twilio->client()->conferences($conferences[0]->sid)->participants->read();

                // Do not call if the caller hung up.
                if (count($participants) == 1) {
                    try {
                        $callerSid = $participants[0]->callSid;
                        session()->put('master_callersid', $callerSid);
                        $callerNumber = $this->twilio->client()->calls($callerSid)->fetch()->from;
                        if (strpos($callerNumber, "+") !== 0) {
                            $callerNumber .= "+" . trim($callerNumber);
                        }
                        Log::debug("callerNumber: " . $callerNumber . ", callerSid: " . $callerSid);
                        if ($callConfig->volunteer->phoneNumber == SpecialPhoneNumber::VOICE_MAIL || $callConfig->volunteer->phoneNumber == SpecialPhoneNumber::UNKNOWN) {
                            Log::debug("Calling voicemail.");
                            $this->twilio->client()->calls($callerSid)->update(array(
                                "method" => "GET",
                                "url" => $callConfig->voicemail_url . "&caller_number=" . $callerNumber
                            ));
                        } else {
                            foreach (explode(",", $callConfig->volunteer->phoneNumber) as $volunteer_number) {
                                if ($serviceBodyCallHandling->volunteer_sms_notification_enabled) {
                                    Log::debug("Sending volunteer SMS notification: " . $callConfig->volunteer->phoneNumber);
                                    $dialbackString = $this->call->getDialbackString($callerSid, $callConfig->options['callerId'], SmsDialbackOptions::VOLUNTEER_NOTIFICATION);
                                    $this->twilio->client()->messages->create(
                                        $volunteer_number,
                                        array(
                                            "body" => sprintf("%s %s. %s", $sms_body, $callerNumber, $dialbackString),
                                            "from" => $callConfig->options['callerId']
                                        )
                                    );
                                }

                                Log::debug("Calling: " . $callConfig->volunteer->phoneNumber);
                                $this->call->insertCallEventRecord(EventId::VOLUNTEER_DIALED, (object)['to_number' => $volunteer_number]);
                                $this->twilio->client()->calls->create(
                                    $volunteer_number,
                                    $callConfig->options['callerId'],
                                    $callConfig->options
                                );
                            }
                        }
                    } catch (TwilioException $e) {
                        Log::critical($e);
                    }
                }
            } elseif ($request->has('StatusCallbackEvent') && $request->get('StatusCallbackEvent') == 'participant-leave') {
                $conference_sid = $conferences[0]->sid;
                $conference_participants = $this->twilio->client()->conferences($conference_sid)->participants;
                foreach ($conference_participants as $participant) {
                    try {
                        Log::debug("Someone left the conference: " . $participant->callSid);
                        $this->twilio->client()->calls($participant->callSid)->update(array( 'status' => TwilioCallStatus::COMPLETED ));
                    } catch (TwilioException $e) {
                        Log::critical($e);
                    }
                }
            }
        } elseif ($request->has('StatusCallbackEvent') && $request->get('StatusCallbackEvent') == 'participant-leave') {
            $participant = $this->call->getConferenceParticipant($request->get('CallSid'));
            if ($participant->role == CallRole::CALLER) {
                $this->call->insertCallEventRecord(EventId::CALLER_HUP);
            } elseif ($participant->role == CallRole::VOLUNTEER) {
                $this->call->insertCallEventRecord(EventId::VOLUNTEER_HUP);
            }
        }

        return response([])->header("Content-Type", "application/json");
    }

    private function getWebhookUrl(Request $request)
    {
        $voice_url = str_replace("/endpoints", "", "https://".$request->server('HTTP_HOST').$request->server('PHP_SELF'));
        if (strpos(basename($voice_url), ".php")) {
            return substr($voice_url, 0, strrpos($voice_url, "/"));
        } elseif (strpos($voice_url, "?")) {
            return substr($voice_url, 0, strrpos($voice_url, "?"));
        } else {
            return $voice_url;
        }
    }

    private function getCallConfig($request, $serviceBodyCallHandling)
    {
        $tracker = !$request->has("tracker") ? 0 : $request->get("tracker");

        $caller_id = $this->call->getOutboundDialingCallerId($serviceBodyCallHandling);

        if ($request->has("OriginalCallerId")) {
            $original_caller_id = $request->get("OriginalCallerId");
        } elseif ($request->has("Caller")) {
            $original_caller_id = $request->get("Caller");
        } else {
            $original_caller_id = SpecialPhoneNumber::UNKNOWN;
        }

        $config = new CallConfig();
        $volunteer_routing_parameters = new VolunteerRoutingParameters();
        $volunteer_routing_parameters->service_body_id = $serviceBodyCallHandling->service_body_id;
        $volunteer_routing_parameters->tracker = $tracker;
        $volunteer_routing_parameters->cycle_algorithm = $serviceBodyCallHandling->call_strategy;
        $volunteer_routing_parameters->volunteer_type = VolunteerType::PHONE;
        $volunteer_routing_parameters->volunteer_gender = session()->has('Gender') ?? VolunteerGender::UNSPECIFIED;
        $volunteer_routing_parameters->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
        $volunteer_routing_parameters->volunteer_language = $this->settings->get('language');
        session()->put("volunteer_routing_parameters", $volunteer_routing_parameters);
        $config->volunteer_routing_params = $volunteer_routing_parameters;
        $volunteer = $this->volunteers->getHelplineVolunteer($config->volunteer_routing_params);
        $config->volunteer = $volunteer;
        $config->options = array(
            'method' => 'GET',
            'url'  => ($this->getWebhookUrl($request) . '/helpline-outdial-response.php?conference_name='
                . $request->get('FriendlyName') . '&service_body_id='
                . $serviceBodyCallHandling->service_body_id . $this->settings->getSessionLink()),
            'statusCallback'       => $serviceBodyCallHandling->call_strategy == CycleAlgorithm::BLASTING
                ? ($this->getWebhookUrl($request) . '/helpline-dialer.php?noop=1' . $this->settings->getSessionLink())
                : ($this->getWebhookUrl($request) . '/helpline-dialer.php?service_body_id=' . $serviceBodyCallHandling->service_body_id
                    . ('&tracker=' . ++$tracker)
                    . ('&FriendlyName=' . $request->get('FriendlyName')
                    . ('&OriginalCallerId=' . trim($original_caller_id))
                    . ($this->settings->getSessionLink()))),
            'statusCallbackEvent'  => 'completed',
            'statusCallbackMethod' => 'GET',
            'timeout'              => $serviceBodyCallHandling->call_timeout,
            'callerId'             => $caller_id,
            'originalCallerId'     => $original_caller_id
        );

        $config->voicemail_url = $this->getWebhookUrl($request) . '/voicemail.php?service_body_id='
            . $serviceBodyCallHandling->service_body_id . '&caller_id='
            . trim($config->options['callerId']) . $this->settings->getSessionLink();
        if (!session()->has('ActiveVolunteer')) {
            session()->put('ActiveVolunteer', $volunteer);
        }

        if ($serviceBodyCallHandling->call_strategy == CycleAlgorithm::BLASTING) {
            session()->put('no_answer_max', count(explode(",", $config->volunteer->phoneNumber)));
            session()->put('voicemail_url', $config->voicemail_url);
        } else {
            session()->put('no_answer_max', 0);
        }

        return $config;
    }
}
