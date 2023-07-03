<?php

namespace App\Http\Controllers;

use App\Constants\AlertId;
use App\Constants\EventId;
use App\Constants\SearchType;
use App\Constants\VolunteerGender;
use App\Models\CallRecord;
use App\Models\RecordType;
use App\Models\VolunteerRoutingParameters;
use App\Services\CallService;
use App\Services\ConfigService;
use App\Services\GeocodingService;
use App\Services\MeetingResultsService;
use App\Services\ReadingService;
use App\Services\SettingsService;
use App\Services\TwilioService;
use App\Services\VolunteerService;
use App\Constants\CallRole;
use DateTime;
use Exception;
use App\Constants\LocationSearchMethod;
use App\Constants\ReadingType;
use App\Constants\SpecialPhoneNumber;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Http\Request;
use App\Constants\VolunteerType;

class CallFlowController extends Controller
{
    protected VolunteerService $volunteers;
    protected MeetingResultsService $meetingResults;
    protected SettingsService $settings;
    protected CallService $call;
    protected TwilioService $twilio;
    protected GeocodingService $geocoding;
    protected ReadingService $reading;
    protected ConfigService $config;

    public function __construct(
        VolunteerService      $volunteers,
        MeetingResultsService $meetingResults,
        SettingsService       $settings,
        CallService           $call,
        TwilioService         $twilio,
        GeocodingService      $geocoding,
        ReadingService        $reading,
        ConfigService         $config,
    ) {
        $this->volunteers = $volunteers;
        $this->meetingResults = $meetingResults;
        $this->settings = $settings;
        $this->call = $call;
        $this->twilio = $twilio;
        $this->geocoding = $geocoding;
        $this->reading = $reading;
        $this->config = $config;
    }

    public function index(Request $request)
    {
        $digit = $this->call->getDigitResponse($request, 'language_selections', 'Digits');

        $twiml = new VoiceResponse();
        if (strlen($this->settings->get('language_selections')) > 0) {
            if ($digit == null) {
                $twiml->redirect("lng-selector.php");
                return response($twiml)->header("Content-Type", "text/xml");
            } else {
                $selected_language = explode(",", $this->settings->get('language_selections'))[intval($digit) - 1];
                $_SESSION["override_word_language"] = $selected_language;
                $_SESSION["override_gather_language"] = $selected_language;
                $_SESSION["override_language"] = $selected_language;
                include_once __DIR__.'/../../../lang/'.$this->settings->getWordLanguage().'.php';
            }
        }

        if ($request->has('CallSid')) {
            $phoneNumberSid = $this->twilio->client()->calls($request->query('CallSid'))->fetch()->phoneNumberSid;
            $incomingPhoneNumber = $this->twilio->client()->incomingPhoneNumbers($phoneNumberSid)->fetch();

            if ($incomingPhoneNumber->statusCallback == null
                || !str_contains($incomingPhoneNumber->statusCallback, "status.php")) {
                $this->call->insertAlert(AlertId::STATUS_CALLBACK_MISSING, $incomingPhoneNumber->phoneNumber);
            }
        }

        if ($request->has("override_service_body_id")) {
            $this->config->getCallHandling($request->query("override_service_body_id"));
        }

        $promptset_name = str_replace("-", "_", $this->settings->getWordLanguage()) . "_greeting";
        if ($this->settings->has("extension_dial") && json_decode($this->settings->get("extension_dial"))) {
            $gather = $twiml->gather()
                ->setLanguage($this->settings->get('gather_language'))
                ->setInput("dtmf")
                ->setFinishOnKey("#")
                ->setTimeout("10")
                ->setAction("service-body-ext-response.php")
                ->setMethod("GET");
            $gather->say("Enter the service body ID, followed by the pound sign.");
        } else {
            $gather = $twiml->gather()
                ->setLanguage($this->settings->get('gather_language'))
                ->setInput($this->settings->getInputType())
                ->setNumDigits("1")
                ->setTimeout("10")
                ->setSpeechTimeout("auto")
                ->setAction("input-method.php")
                ->setMethod("GET");
            $gather->pause()->setLength($this->settings->get('initial_pause'));
            if ($this->settings->has($promptset_name)) {
                $gather->play($this->settings->get($promptset_name));
            } else {
                if (!$request->has("Digits")) {
                    $gather->say($this->settings->get('title'))
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get("language"));
                }

                $searchTypeSequence = $this->settings->getDigitMapSequence('digit_map_search_type');

                foreach ($searchTypeSequence as $digit => $type) {
                    if ($type == SearchType::VOLUNTEERS) {
                        $gather->say($this->settings->getPressWord() . " " . $this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_find') . " " . $this->settings->word('someone_to_talk_to'))
                            ->setVoice($this->settings->voice())
                            ->setLanguage($this->settings->get("language"));
                    } elseif ($type == SearchType::MEETINGS) {
                        $gather->say($this->settings->getPressWord() . " " . $this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_search_for') . " " . $this->settings->word('meetings'))
                            ->setVoice($this->settings->voice())
                            ->setLanguage($this->settings->get("language"));
                    } elseif ($type == SearchType::JFT) {
                        $gather->say($this->settings->getPressWord() . " " . $this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_listen_to_the_just_for_today'))
                            ->setVoice($this->settings->voice())
                            ->setLanguage($this->settings->get("language"));
                    } elseif ($type == SearchType::SPAD) {
                        $gather->say($this->settings->getPressWord() . " " . $this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_listen_to_the_spad'))
                            ->setVoice($this->settings->voice())
                            ->setLanguage($this->settings->get("language"));
                    }
                }
            }
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function inputMethod(Request $request)
    {
        $response = $this->call->getIvrResponse(
            $request,
            $this->settings->getPossibleDigits('digit_map_search_type')
        );
        $twiml = new VoiceResponse();
        if ($response == null) {
            $twiml->say($this->settings->word('you_might_have_invalid_entry'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
            $twiml->redirect("index.php");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $searchType = $this->call->getDigitResponse($request, 'digit_map_search_type', 'Digits');
        $playTitle = $request->has('PlayTitle') ? $request->query('PlayTitle') : 0;

        if ($searchType == SearchType::MEETINGS) {
            $this->call->insertCallEventRecord(EventId::MEETING_SEARCH);
        } elseif ($searchType == SearchType::JFT) {
            $this->call->insertCallEventRecord(EventId::JFT_LOOKUP);
        } elseif ($searchType == SearchType::SPAD) {
            $this->call->insertCallEventRecord(EventId::SPAD_LOOKUP);
        }

        if (($searchType == SearchType::VOLUNTEERS || $searchType == SearchType::MEETINGS)
            && json_decode($this->settings->get('disable_postal_code_gather'))) {
            $twiml->redirect("input-method-result.php?SearchType=" . $searchType . "&Digits=1")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($searchType == SearchType::VOLUNTEERS) {
            if (isset($_SESSION['override_service_body_id'])) {
                $twiml->redirect("helpline-search.php?Called=" . $request->query("Called") . $this->settings->getSessionLink(true))
                    ->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml");
            }

            $searchDescription = $this->settings->word('someone_to_talk_to');
        } elseif ($searchType == SearchType::MEETINGS) {
            if (!strpos($this->settings->get('custom_query'), '{LATITUDE}')
                || !strpos($this->settings->get('custom_query'), '{LONGITUDE}')) {
                $twiml->redirect("meeting-search.php?Called=" . $request->query("Called"))
                    ->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml");
            }

            $searchDescription = $this->settings->word('meetings');
        } elseif ($searchType == SearchType::JFT) {
            $twiml->redirect("fetch-jft.php")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($searchType == SearchType::SPAD) {
            $twiml->redirect("fetch-spad.php")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($searchType == SearchType::DIALBACK) {
            $twiml->redirect("dialback.php")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($searchType == SearchType::CUSTOM_EXTENSIONS
            && count($this->settings->get('custom_extensions')) > 0) {
            $twiml->redirect("custom-ext.php")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $gather = $twiml->gather()
            ->setLanguage($this->settings->get('gather_language'))
            ->setInput($this->settings->getInputType())
            ->setNumDigits("1")
            ->setTimeout("10")
            ->setSpeechTimeout("auto")
            ->setAction("input-method-result.php?SearchType=".$searchType)
            ->setMethod("GET");
        if ($playTitle == "1") {
            $gather->say($this->settings->get("title"))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
        }

        if ($request->has("Retry")) {
            $retry_message = $request->has("RetryMessage") ? $request->query("RetryMessage") : $this->settings->word("could_not_find_location_please_retry_your_entry");
            $gather->say($retry_message)
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
            $gather->pause()->setLength("1");
        }

        $locationSearchMethodSequence = $this->settings->getDigitMapSequence('digit_map_location_search_method');
        foreach ($locationSearchMethodSequence as $digit => $method) {
            if ($method == LocationSearchMethod::VOICE) {
                $gather->say($this->settings->getPressWord() . " " . $this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_search_for') . " " . $searchDescription . " " . $this->settings->word('by') . " " . $this->settings->word('city_or_county'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
            } elseif ($method == LocationSearchMethod::DTMF) {
                $gather->say($this->settings->getPressWord() . " " . $this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_search_for') . " " . $searchDescription . " " . $this->settings->word('by') . " " . $this->settings->word('zip_code'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            } elseif ($method == SearchType::JFT && $searchType == SearchType::MEETINGS) {
                $gather->say($this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_listen_to_the_just_for_today'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            } elseif ($method == SearchType::SPAD && $searchType == SearchType::MEETINGS) {
                $gather->say($this->settings->getWordForNumber($digit) . " " . $this->settings->word('to_listen_to_the_spad'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            }
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function zipinput(Request $request)
    {
        $searchType = $request->query("SearchType");
        if ($searchType == SearchType::VOLUNTEERS) {
            $action = sprintf("helpline-search.php?SearchType=%s", $searchType);
        } else {
            $action = sprintf("address-lookup.php?SearchType=%s", $searchType);
        }
        $enterWord = ($this->settings->has('speech_gathering') && json_decode($this->settings->get('speech_gathering'))
            ? $this->settings->word('please_enter_or_say_your_digit') : $this->settings->word('please_enter_your_digit'));

        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage($this->settings->get("gather_language"))
            ->setInput($this->settings->getInputType())
            ->setNumDigits($this->settings->get('postal_code_length'))
            ->setTimeout(10)
            ->setAction($action)
            ->setMethod("GET");

        if (str_contains($this->settings->getInputType(), "speech")) {
            $gather->setSpeechTimeout("auto");
        }

        $gather->say(sprintf("%s %s", $enterWord, $this->settings->word('zip_code')))
            ->setVoice($this->settings->voice())
            ->setLanguage($this->settings->get('language'));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function customext()
    {
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage($this->settings->get("gather_language"))
            ->setInput($this->settings->getInputType())
            ->setFinishOnKey("#")
            ->setTimeout(15)
            ->setAction("custom-ext-dialer.php")
            ->setMethod("GET");

        $gather->play($this->settings->get(str_replace("-", "_", $this->settings->getWordLanguage()) . "_custom_extensions_greeting"));
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function cityorcountyinput(Request $request)
    {
        $province = json_decode($this->settings->get('province_lookup')) ? $request->query("SpeechResult") : "";
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage($this->settings->get('gather_language'))
            ->setInput("speech")
            ->setHints($this->settings->get('gather_hints'))
            ->setTimeout(10)
            ->setSpeechTimeout("auto")
            ->setAction(sprintf(
                "voice-input-result.php?SearchType=%s&Province=%s",
                $request->query("SearchType"),
                urlencode($province)
            ))
            ->setMethod('GET');
        $gather->say(sprintf("%s %s", $this->settings->word('please_say_the_name_of_the'), $this->settings->word('city_or_county')))
            ->setVoice($this->settings->voice())
            ->setLanguage($this->settings->get('language'));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function servicebodyextresponse(Request $request)
    {
        $twiml = new VoiceResponse();
        $twiml->redirect(sprintf(
            "helpline-search.php?override_service_body_id=%s",
            $request->query('Digits')
        ), ["method" => "GET"]);
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function genderroutingresponse(Request $request)
    {
        $gender = $this->call->getIvrResponse(
            $request,
            [VolunteerGender::MALE, VolunteerGender::FEMALE, VolunteerGender::NO_PREFERENCE],
        );
        $twiml = new VoiceResponse();
        if ($gender == null) {
            $twiml->say($this->settings->word('you_might_have_invalid_entry'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
            $twiml->redirect("gender-routing.php")
                ->setMethod("GET");
        } else {
            $_SESSION['Gender'] = $gender;
            $twiml->redirect(sprintf("helpline-search.php?SearchType=%s", $request->query('SearchType')))
                ->setMethod('GET');
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function playlist(Request $request)
    {
        $items = $request->query("items");
        $twiml = new VoiceResponse();
        $playlist_uris = explode(",", $items);
        foreach ($playlist_uris as $item) {
            $twiml->play($item);
        }
        $twiml->redirect(sprintf("playlist.php?items=%s", $items));

        return response($twiml)
            ->header("Content-Type", "text/xml");
    }

    public function voiceinputresult(Request $request)
    {
        $province = ($this->settings->has('province_lookup')
        && json_decode($this->settings->get('province_lookup')) ? $request->query('Province')
            : $this->call->getProvince());
        $speechResult = $request->query('SpeechResult');
        $searchType = $request->query('SearchType');
        $action = ($searchType == SearchType::VOLUNTEERS ? "helpline-search.php" : "address-lookup.php");

        $twiml = new VoiceResponse();
        $twiml->redirect(sprintf(
            "%s?Digits=%s&SearchType=%s",
            $action,
            urlencode($speechResult . ", " . $province),
            $searchType
        ))->setMethod('GET');

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function addresslookup(Request $request)
    {
        $address = $this->call->getIvrResponse($request);
        $coordinates = $this->geocoding->getCoordinatesForAddress($address);
        $this->call->insertCallEventRecord(
            EventId::MEETING_SEARCH_LOCATION_GATHERED,
            (object)['gather' => $address, 'coordinates' => $coordinates ?? null]
        );
        $twiml = new VoiceResponse();
        if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
            $twiml->redirect(sprintf("input-method.php?Digits=%s&Retry=1", $request->query('SearchType')))
                ->setMethod('GET');
        } else {
            $twiml->say(sprintf("%s %s", $this->settings->word('searching_meeting_information_for'), $coordinates->location))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
            $twiml->redirect(sprintf(
                "meeting-search.php?Latitude=%s&Longitude=%s",
                $coordinates->latitude,
                $coordinates->longitude
            ))->setMethod('GET');
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function fallback()
    {
        $exploded_result = explode("\|", $this->settings->get("helpline_fallback"));
        $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
        $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
        $twiml = new VoiceResponse();
        $twiml->say(sprintf(
            "%s... %s... %s.",
            $this->settings->word('there_seems_to_be_a_problem'),
            $this->settings->word('please_wait_while_we_connect_your_call'),
            $this->settings->word('please_stand_by')
        ))->setVoice($this->settings->voice())->setLanguage($this->settings->get("language"));
        $twiml->dial()->number($phone_number, ['sendDigits' => $extension]);

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function customextdialer(Request $request)
    {
        $twiml = new VoiceResponse();
        $dial = $twiml->dial()->setCallerId($request->query("Called"));
        $dial->number($this->settings->get('custom_extensions')[str_replace("#", "", $request->query('Digits'))]);
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function dialback(Request $request)
    {
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage($this->settings->get("gather_language"))
            ->setInput("dtmf")
            ->setTimeout(15)
            ->setFinishOnKey("#")
            ->setAction("dialback-dialer.php")
            ->setMethod("GET");
        $gather->say("Please enter the dialback pin, followed by the pound sign.")
            ->setVoice($this->settings->voice())
            ->setLanguage($this->settings->get("language"));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function dialbackDialer(Request $request)
    {
        $dialbackPinValid = $this->call->isDialbackPinValid($request->query("Digits"));
        $twiml = new VoiceResponse();
        if ($dialbackPinValid) {
            $twiml->say($this->settings->word('please_wait_while_we_connect_your_call'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
            $twiml->dial($request->query("Digits"))
                ->setCallerId($request->query("Called"));
        } else {
            $twiml->say("Invalid pin entry")
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
            $twiml->pause()->setLength(2);
            $twiml->redirect("index.php");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function genderrouting(Request $request)
    {
        $gender_no_preference = ($this->settings->get("gender_no_preference")
            ? sprintf(", %s %s %s", $this->settings->getPressWord(), $this->settings->word('three'), $this->settings->word('speak_no_preference'))
            : "");
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage($this->settings->get('gather_language'))
            ->setHints($this->settings->get('gather_hints'))
            ->setInput($this->settings->getInputType())
            ->setTimeout(10)
            ->setSpeechTimeout("auto")
            ->setAction(sprintf("gender-routing-response.php?SearchType=%s", $request->query("SearchType")))
            ->setMethod('GET');

        $gather->say(sprintf(
            "%s %s %s, %s %s %s%s",
            $this->settings->getPressWord(),
            $this->settings->word('one'),
            $this->settings->word('to_speak_to_a_man'),
            $this->settings->getPressWord(),
            $this->settings->word('two'),
            $this->settings->word('to_speak_to_a_woman'),
            $gender_no_preference
        ))
            ->setVoice($this->settings->voice())
            ->setLanguage($this->settings->get('language'));
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function provincelookuplistresponse(Request $request)
    {
        $search_type = $request->query("SearchType");
        $province_lookup_item = $this->call->getIvrResponse(
            $request,
            range(1, count($this->settings->get('province_lookup_list')))
        );
        $twiml = new VoiceResponse();
        if ($province_lookup_item == null) {
            $twiml->say($this->settings->word('you_might_have_invalid_entry'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
            $twiml->redirect(sprintf("province-voice-input.php?SearchType=%s", $search_type))
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $this->call->insertCallEventRecord(
            EventId::PROVINCE_LOOKUP_LIST,
            (object)['province_lookup_list' => $this->settings->get('province_lookup_list')[$province_lookup_item - 1]]
        );

        $twiml->redirect(sprintf(
            "city-or-county-voice-input.php?SearchType=%s&SpeechResult=%s",
            $search_type,
            urlencode($this->settings->get('province_lookup_list')[$province_lookup_item - 1])
        ));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function statusCallback(Request $request)
    {
        $callRecord = new CallRecord();
        $callRecord->callSid = $request->query('CallSid');
        $callRecord->to_number = $request->query('Called');
        $callRecord->from_number = $request->query('Caller');
        $callRecord->duration = intval($request->query('CallDuration'));

        if ($request->query("TimestampNow")) {
            $start_time = date("Y-m-d H:i:s");
            $end_time = date("Y-m-d H:i:s");
        } else {
            $twilioRecords = $this->twilio->client()->calls($callRecord->callSid)->fetch();
            $start_time = $twilioRecords->startTime->format("Y-m-d H:i:s");
            $end_time = $twilioRecords->endTime->format("Y-m-d H:i:s");
        }
        $callRecord->start_time = $start_time;
        $callRecord->end_time = $end_time;
        $callRecord->type = RecordType::PHONE;
        $callRecord->payload = json_encode($request->query->all());

        $this->call->insertCallRecord($callRecord);

        $twiml = new VoiceResponse();
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function postCallAction(Request $request)
    {
        $sms_messages = $request->query('Payload') ? json_decode(urldecode($request->query('Payload'))) : [];
        $digits = $this->call->getIvrResponse($request);

        $twiml = new VoiceResponse();
        if (($digits == 1 || $digits == 3) && count($sms_messages) > 0) {
            if ($this->settings->get("sms_combine")) {
                $this->twilio->client()->messages->create(
                    $request->query('From'),
                    array("from" => $request->query('To'),
                        "body" => implode(
                            "\n\n",
                            $sms_messages
                        ))
                );
            } else {
                for ($i = 0; $i < count($sms_messages); $i++) {
                    $this->twilio->client()->messages->create(
                        $request->query('From'),
                        array("from" => $request->query('To'), "body" => $sms_messages[$i])
                    );
                }
            }
        }

        if ($digits == 2 || $digits == 3) {
            $twiml->redirect(str_replace("&", "&amp;", $_SESSION['initial_webhook']))
                ->setMethod("GET");
        } else {
            $twiml->say($this->settings->word('thank_you_for_calling_goodbye'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function languageSelector(Request $request)
    {
        $twiml = new VoiceResponse();
        if (!$this->settings->has('language_selections')) {
            $twiml->say("language gateway options are not set, please refer to the documentation to utilize this feature.");
            $twiml->hangup();
        } else {
            $language_selection_options = explode(",", $this->settings->get('language_selections'));
            $twiml->pause()->setLength($this->settings->get('initial_pause'));
            $gather = $twiml->gather()
                ->setLanguage($this->settings->get('gather_language'))
                ->setInput($this->settings->getInputType())
                ->setNumDigits(1)
                ->setTimeout(10)
                ->setSpeechTimeout("auto")
                ->setAction("index.php")
                ->setMethod("GET");
            for ($i = 0; $i < count($language_selection_options); $i++) {
                $message = sprintf(
                    "%s %s %s %s",
                    $this->settings->word('for', $language_selection_options[$i]),
                    $this->settings->word('language_title', $language_selection_options[$i]),
                    $this->settings->getPressWord($language_selection_options[$i]),
                    $this->settings->getWordForNumber($i + 1, $language_selection_options[$i])
                );
                $gather->say($message)
                    ->setVoice($this->settings->voice(str_replace("-", "_", $language_selection_options[$i])))
                    ->setLanguage($language_selection_options[$i]);
            }
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function provinceVoiceInput(Request $request)
    {
        $province_lookup_list = $this->settings->get('province_lookup_list');
        $twiml = new VoiceResponse();
        if (count($province_lookup_list) > 0) {
            for ($i = 0; $i < count($province_lookup_list); $i++) {
                $say = sprintf(
                    "%s %s %s %s",
                    $this->settings->word('for'),
                    $province_lookup_list[$i],
                    $this->settings->getPressWord(),
                    $this->settings->getWordForNumber($i + 1)
                );
                $gather = $twiml->gather()
                    ->setLanguage($this->settings->get("gather_language"))
                    ->setHints($this->settings->get('gather_hints'))
                    ->setInput($this->settings->getInputType())
                    ->setNumDigits(1)
                    ->setTimeout(10)
                    ->setSpeechTimeout("auto")
                    ->setAction("province-lookup-list-response.php?SearchType=".$request->query("SearchType"))
                    ->setMethod("GET");
                $gather->say($say)
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
            }
        } else {
            $say = sprintf("%s %s", $this->settings->word('please_say_the_name_of_the'), $this->settings->word('state_or_province'));
            $gather = $twiml->gather()
                ->setLanguage($this->settings->get("gather_language"))
                ->setHints($this->settings->get('gather_hints'))
                ->setInput("speech")
                ->setTimeout(10)
                ->setSpeechTimeout("auto")
                ->setAction("city-or-county-voice-input.php?SearchType=".$request->query("SearchType"))
                ->setMethod("GET");
            $gather->say($say)
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineAnswerResponse(Request $request)
    {
        $twiml = new VoiceResponse();
        if ($request->query('Digits') == "1") {
            $conferences = $this->twilio->client()->conferences
                ->read(array ("friendlyName" => $request->query('conference_name') ));
            $participants = $this->twilio->client()->conferences($conferences[0]->sid)->participants->read();

            if (count($participants) == 2) {
                error_log("Enough volunteers have joined.  Hanging up this volunteer.");
                $twiml->say($this->settings->word('volunteer_has_already_joined_the_call_goodbye'))
                    ->setVoice($this->settings->voice())->setLanguage($this->settings->get('language'));
                $twiml->hangup();
            } else {
                $this->call->insertCallEventRecord(EventId::VOLUNTEER_IN_CONFERENCE, (object)["to_number" => $request->query('Called')]);
                $dial = $twiml->dial();
                $dial->conference($request->query("conference_name"))
                    ->setStatusCallbackMethod("GET")
                    ->setStatusCallbackEvent("join")
                    ->setStartConferenceOnEnter("true")
                    ->setEndConferenceOnExit("true")
                    ->setBeep("false");
            }
        } else {
            $this->call->insertCallEventRecord(
                EventId::VOLUNTEER_REJECTED,
                (object)["digits" => $request->query('Digits'),
                    "to_number" => $request->query('Called')]
            );
            $this->twilio->incrementNoAnswerCount();
            $this->call->setConferenceParticipant(
                $request->query('conference_name'),
                $request->query('CallSid'),
                CallRole::VOLUNTEER
            );
            $this->settings->logDebug("They rejected the call.");
            $twiml->hangup();
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineOutdialResponse(Request $request)
    {
        $conferences = $this->twilio->client()->conferences
            ->read(array ("friendlyName" => $request->query('conference_name') ));
        $participants = $this->twilio->client()->conferences($conferences[0]->sid)->participants->read();

        $twiml = new VoiceResponse();
        if (count($participants) == 2) {
            $this->call->setConferenceParticipant(
                $request->query('conference_name'),
                $request->query('CallSid'),
                CallRole::VOLUNTEER
            );
            error_log("Enough volunteers have joined.  Hanging up this volunteer.");
            $twiml->say($this->settings->word('volunteer_has_already_joined_the_call_goodbye'))
                ->setVoice($this->settings->voice())->setLanguage($this->settings->get('language'));
            $twiml->hangup();
        } elseif (count($participants) > 0) {
            $this->call->insertCallEventRecord(
                EventId::VOLUNTEER_ANSWERED,
                (object)['to_number' => $request->query('Called')]
            );
            $this->call->setConferenceParticipant(
                $request->query('conference_name'),
                $request->query('CallSid'),
                CallRole::VOLUNTEER
            );
            error_log("Volunteer picked up or put to their voicemail, asking if they want to take the call, timing out after 15 seconds of no response.");
            if ($this->settings->has('volunteer_auto_answer') && $this->settings->get('volunteer_auto_answer')) {
                $twiml->redirect("helpline-answer-response.php?Digits=1&conference_name=".$request->query('conference_name')."&service_body_id=" . $request->query('service_body_id') . $this->settings->getSessionLink(true))
                ->setMethod("GET");
            } else {
                $gather = $twiml->gather()
                    ->setActionOnEmptyResult(true)
                    ->setNumDigits("1")
                    ->setTimeout("15")
                    ->setAction("helpline-answer-response.php?conference_name=".$request->query('conference_name')."&service_body_id=" . $request->query('service_body_id') . $this->settings->getSessionLink(true))
                    ->setMethod("GET");
                $gather->say($this->settings->word('you_have_a_call_from_the_helpline'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get('language'));
            }
        } else {
            $this->call->setConferenceParticipant(
                $request->query('conference_name'),
                $request->query('CallSid'),
                CallRole::CALLER
            );
            $this->call->insertCallEventRecord(EventID::VOLUNTEER_ANSWERED_BUT_CALLER_HUP, (object)[
                'to_number' => $request->query('Called')]);
            error_log("The caller hungup.");
            $twiml->say($this->settings->word('the_caller_hungup'))
                ->setVoice($this->settings->voice())->setLanguage($this->settings->get('language'));
            $twiml->hangup();
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineSms(Request $request)
    {
        try {
            if ($request->has("OriginalCallerId")) {
                $original_caller_id = $request->query("OriginalCallerId");
            }

            $service_body = $this->meetingResults
                ->getServiceBodyCoverage($request->query("Latitude"), $request->query("Longitude"));
            $serviceBodyCallHandling   = $this->config->getCallHandling($service_body->id);
            $tracker                   = $request->has("tracker") ? 0 : $request->query("tracker");

            if ($serviceBodyCallHandling->sms_routing_enabled) {
                $volunteer_routing_parameters = new VolunteerRoutingParameters();
                $volunteer_routing_parameters->service_body_id = $serviceBodyCallHandling->service_body_id;
                $volunteer_routing_parameters->tracker = $tracker;
                $volunteer_routing_parameters->cycle_algorithm = $serviceBodyCallHandling->sms_strategy;
                $volunteer_routing_parameters->volunteer_type = VolunteerType::SMS;
                $phone_numbers = explode(
                    ',',
                    $this->volunteers->getHelplineVolunteer($volunteer_routing_parameters)->phoneNumber
                );

                $this->twilio->client()->messages->create(
                    $original_caller_id,
                    array(
                        "body" => $this->settings->word('your_request_has_been_received'),
                        "from" => $request->query('To')
                    )
                );

                foreach ($phone_numbers as $phone_number) {
                    if ($phone_number == SpecialPhoneNumber::UNKNOWN) {
                        $phone_number = $serviceBodyCallHandling->primary_contact_number;
                    }

                    if ($phone_number != "") {
                        $this->twilio->client()->messages->create(
                            $phone_number,
                            array(
                                "body" => sprintf(
                                    "%s: %s %s %s",
                                    $this->settings->word('helpline'),
                                    $this->settings->word('someone_is_requesting_sms_help_from'),
                                    $original_caller_id,
                                    $this->settings->word('please_call_or_text_them_back')
                                ),
                                "from" => $request->query('To')
                            )
                        );
                    } else {
                        $this->settings->logDebug("No phone number was found and no fallback number in primary_contact_number was found.");
                    }
                }
            } else {
                $this->settings->logDebug(sprintf("SMS Helpline capability not enabled for service body id: %s", $service_body->id));
            }
        } catch (Exception $e) {
            $this->settings->logDebug($e);
            $this->twilio->client()->messages->create(
                $original_caller_id,
                array(
                    "body" => $this->settings->word('could_not_find_a_volunteer'),
                    "from" => $request->query('To')
                )
            );
        }

        $twiml = new VoiceResponse();
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function inputMethodResult(Request $request)
    {
        $twiml = new VoiceResponse();
        $response = $this->call->getIvrResponse(
            $request,
            $this->settings->getPossibleDigits('digit_map_location_search_method')
        );
        if ($response == null) {
            $twiml->say($this->settings->word('you_might_have_invalid_entry'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
            $twiml->redirect("index.php");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $locationSearchMethod = $this->call->getDigitResponse($request, 'digit_map_location_search_method', 'Digits');
        if ($locationSearchMethod == SearchType::JFT) {
            $twiml->redirect("fetch-jft.php")->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($locationSearchMethod == SearchType::SPAD) {
            $twiml->redirect("fetch-spad.php")->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        if ($this->settings->has('province_lookup') && json_decode($this->settings->get('province_lookup'))) {
            $action = "province-voice-input.php";
        } else {
            $action = "city-or-county-voice-input.php";
        }

        if ($locationSearchMethod == LocationSearchMethod::VOICE) { // voice based
            $twiml->redirect($action."?SearchType=".$request->query('SearchType')."&InputMethod=".LocationSearchMethod::VOICE)
            ->setMethod("GET");
        } elseif ($locationSearchMethod == LocationSearchMethod::DTMF) {
            $twiml->redirect("zip-input.php?SearchType=".$request->query('SearchType')."&InputMethod=".LocationSearchMethod::DTMF)
            ->setMethod("GET");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function smsGateway(Request $request)
    {
        $callRecord = new CallRecord();
        $callRecord->callSid = $request->query('SmsSid');
        $callRecord->to_number = $request->query('To');
        $callRecord->from_number = $request->query('From');
        $callRecord->duration = 0;
        $callRecord->start_time = date("Y-m-d H:i:s");
        $callRecord->end_time = date("Y-m-d H:i:s");
        $callRecord->type = RecordType::SMS;
        $callRecord->payload = json_encode($_REQUEST);

        $this->call->insertCallRecord($callRecord);
        $this->call->checkSMSBlackhole();

        $address = $request->get('Body');
        if (str_contains($address, ',')) {
            $coordinates = $this->geocoding->getCoordinatesForAddress($address);
        } else {
            $coordinates = $this->geocoding->getCoordinatesForAddress($address . "," . $this->call->getProvince());
        }

        $twiml = new VoiceResponse();
        $sms_helpline_keyword = $this->settings->get("sms_helpline_keyword");
        if (str_contains(strtoupper($address), strtoupper($sms_helpline_keyword))) {
            if (strlen(trim(str_replace(strtoupper($sms_helpline_keyword), "", strtoupper($address)))) > 0) {
                $twiml->redirect("helpline-sms.php?OriginalCallerId=" . $request->get("From") . "&To=" . $request->get("To") . "&Latitude=" . $coordinates->latitude . "&Longitude=" . $coordinates->longitude)
                    ->setMethod("GET");
            } else {
                $message = $this->settings->word('please_send_a_message_formatting_as') . " " . $sms_helpline_keyword . ", " . $this->settings->word('followed_by_your_location') . " " . $this->settings->word('for') . " " .  $this->settings->word('someone_to_talk_to');
                $this->twilio->client()->messages->create($request->get("From"), array("from" => $request->get("To"), "body" => $message));
            }
        } elseif (json_decode($this->settings->get('jft_option')) && str_contains(strtoupper($address), strtoupper('jft'))) {
            $reading_chunks = $this->reading->get(ReadingType::JFT, true);
            for ($i = 0; $i < count($reading_chunks); $i++) {
                $this->twilio->client()->messages->create($request->get("From"), array("from" => $request->get("To"), "body" => $reading_chunks[$i]));
            }
        } elseif (json_decode($this->settings->get('spad_option')) && str_contains(strtoupper($address), strtoupper('spad'))) {
            $reading_chunks = $this->reading->get(ReadingType::SPAD, true);
            for ($i = 0; $i < count($reading_chunks); $i++) {
                $this->twilio->client()->messages->create($request->get("From"), array("from" => $request->get("To"), "body" => $reading_chunks[$i]));
            }
        } else {
            $this->call->insertCallEventRecord(EventId::MEETING_SEARCH_SMS);
            $this->call->insertCallEventRecord(
                EventId::MEETING_SEARCH_LOCATION_GATHERED,
                (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
            );
            $twiml->redirect("meeting-search.php?SearchType=" . $this->settings->getDigitForAction('digit_map_search_type', SearchType::VOLUNTEERS) . "&Latitude=" . $coordinates->latitude . "&Longitude=" . $coordinates->longitude)
                ->setMethod("GET");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function meetingSearch(Request $request)
    {
        $twiml = new VoiceResponse();

        $latitude = $request->has("Latitude") ? $request->get('Latitude') : null;
        $longitude = $request->has("Longitude") ? $request->get('Longitude') : null;

        try {
            $suppress_voice_results = $this->settings->has('suppress_voice_results') && json_decode($this->settings->get('suppress_voice_results'));
            $sms_disable = $this->settings->has('sms_disable') && json_decode($this->settings->get('sms_disable'));
            $results_count = $this->settings->has('result_count_max') ? intval($this->settings->get('result_count_max')) : 5;
            $meeting_results = $this->meetingResults->getMeetings(
                $latitude,
                $longitude,
                $results_count,
                null,
                null
            );
            $results_count_num = count($meeting_results->filteredList) < $results_count ? count($meeting_results->filteredList) : $results_count;
        } catch (Exception $e) {
            $twiml->redirect("fallback.php")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $filtered_list = $meeting_results->filteredList;
        $sms_messages = [];

        $text_space = " ";
        $comma_space = ", ";
        $message = "";

        $isFromSmsGateway = $request->has("SmsSid");
        if (!$isFromSmsGateway) {
            if ($meeting_results->originalListCount == 0) {
                $twiml->say($this->settings->word('no_results_found') . "... " .
                    $this->settings->word('you_might_have_invalid_entry') . "... " . $this->settings->word('try_again'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
                $twiml->redirect("input-method.php?Digits=2")
                    ->setMethod("GET");
            } elseif (count($filtered_list) == 0) {
                $twiml->say($this->settings->word('there_are_no_other_meetings_for_today') . ".... " . $this->settings->word('try_again'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
                $twiml->redirect("input-method.php?Digits=2")
                    ->setMethod("GET");
            } elseif ($suppress_voice_results) {
                $twiml->say($results_count_num  . " " . $this->settings->word('meetings_have_been_texted'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
            } else {
                $twiml->say($this->settings->word('meeting_information_found_listing_the_top') . " "
                    . $results_count_num . " " . $this->settings->word('results'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
            }
        } else {
            if ($meeting_results->originalListCount == 0) {
            } elseif (count($filtered_list) == 0) {
            }
        }

        if (!json_decode($this->settings->get("sms_ask")) && !json_decode($this->settings->get("sms_disable"))) {
            $twiml->say($this->settings->word('search_results_by_sms'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
        }

        $results_counter = 0;
        for ($i = 0; $i < count($filtered_list); $i++) {
            $results = $this->getResultsString($filtered_list[$i]);

            if (!$isFromSmsGateway && !$suppress_voice_results) {
                $twiml->pause()->setLength(1);
                $twiml->say($this->settings->word('number') . " " . ($results_counter + 1))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
                $twiml->say($results['meeting_name'])
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
                $twiml->pause()->setLength(1);
                $twiml->say($this->settings->word('starts_at') . " " . $results['timestamp'])
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));

                if ($this->settings->has('include_format_details') && count($this->settings->get('include_format_details')) > 0) {
                    for ($fd = 0; $fd < count($results['format_details']); $fd++) {
                        $twiml->pause()->setLength(1);
                        $twiml->say($this->settings->word('number') . " " . $results['format_details'][$fd]->description_string)
                            ->setVoice($this->settings->voice())
                            ->setLanguage($this->settings->get("language"));
                    }
                }

                for ($ll = 0; $ll < count($results['location']); $ll++) {
                    $twiml->pause()->setLength(1);
                    $twiml->say($results['location'][$ll])
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get("language"));
                }

                if ($this->settings->has("say_links") && json_decode($this->settings->get("say_links"))) {
                    for ($fl = 0; $fl < count($results['links']); $fl++) {
                        $twiml->pause()->setLength(1);
                        $twiml->say($results['links'][$fl])
                            ->setVoice($this->settings->voice())
                            ->setLanguage($this->settings->get("language"));
                    }
                }

                for ($vmai = 0; $vmai < count($results['virtual_meeting_additional_info']); $vmai++) {
                    $twiml->pause()->setLength(1);
                    $twiml->say($results['virtual_meeting_additional_info'][$vmai])
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get("language"));
                }

                if ($request->has("Debug")) {
                    $twiml->say(json_encode($filtered_list[$i]))
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get("language"));
                }
            }

            $results_counter++;
            if ($results_counter == $results_count) {
                break;
            }
        }

        if (!$sms_disable && $this->settings->has('sms_summary_page') && json_decode($this->settings->get('sms_summary_page'))) {
            $voice_url = "https://" . $request->server('HTTP_HOST') . $request->server('PHP_SELF');
            if (strpos(basename($voice_url), ".php")) {
                $webhook_url = substr($voice_url, 0, strrpos($voice_url, "/"));
            } elseif (strpos($voice_url, "?")) {
                $webhook_url = substr($voice_url, 0, strrpos($voice_url, "?"));
            } else {
                $webhook_url = $voice_url;
            }

            $message = sprintf("Meeting Results, click here: %s/msr/%s/%s", $webhook_url, $latitude, $longitude);

            if (json_decode($this->settings->get("sms_ask")) && !$isFromSmsGateway) {
                array_push($sms_messages, $message);
            } else {
                $this->twilio->sendSms($message);
            }
        } elseif (!$sms_disable) {
            $results_counter = 0;
            for ($i = 0; $i < count($filtered_list); $i++) {
                $results = $this->getResultsString($filtered_list[$i]);
                $location_line = implode(", ", $results['location']);
                $message = $results['meeting_name'] . $text_space . $results['timestamp'] . $comma_space . $location_line;

                if (strlen($results['distance_details']) > 0) {
                    $message .= " " . $results['distance_details'];
                }

                foreach ($results['format_details'] as $format_detail) {
                    $message .= "\n" . $format_detail->description_string;
                }

                foreach ($results['location_links'] as $location_link) {
                    $message .= " " . $location_link;
                }

                foreach ($results['links'] as $link) {
                    $message .= "\n" . $link;
                }

                foreach ($results['virtual_meeting_additional_info'] as $additional_info) {
                    $message .= "\n" . $additional_info;
                }

                if (json_decode($this->settings->get("sms_combine")) || (json_decode($this->settings->get("sms_ask")) && !$isFromSmsGateway)) {
                    array_push($sms_messages, $message);
                } else {
                    $this->twilio->sendSms($message);
                }

                $results_counter++;
                if ($results_counter == $results_count) {
                    break;
                }
            }

            if (json_decode($this->settings->get("sms_combine")) && !json_decode($this->settings->get("sms_ask"))) {
                $this->twilio->sendSms(implode("\n\n", $sms_messages));
            }
        }

        if (!$isFromSmsGateway && count($filtered_list) > 0) {
            $twiml->pause()->setLength(2);
            if (!$sms_disable && !$suppress_voice_results && $this->settings->get("sms_ask") && count($sms_messages) > 0) {
                $gather = $twiml->gather()
                    ->setNumDigits(1)
                    ->setTimeout(10)
                    ->setSpeechTimeout("auto")
                    ->setInput($this->settings->getInputType())
                    ->setAction("post-call-action.php?Payload=" . urlencode(json_encode($sms_messages)))
                    ->setMethod("GET");
                $gather->say($this->settings->getPressWord() . " " . $this->settings->word("one") . " " . $this->settings->word('if_you_would_like_these_results_texted_to_you'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
                if (json_decode($this->settings->get('infinite_searching'))) {
                    $gather->say($this->settings->getPressWord() . " " . $this->settings->word("two") . " " . $this->settings->word('if_you_would_like_to_search_again') . "..." . $this->settings->getPressWord() . " " . $this->settings->word("three") . " " . $this->settings->word('if_you_would_like_to_do_both'))
                        ->setVoice($this->settings->voice())
                        ->setLanguage($this->settings->get("language"));
                }
            } elseif (json_decode($this->settings->get('infinite_searching'))) {
                $gather = $twiml->gather()
                    ->setNumDigits(1)
                    ->setTimeout(10)
                    ->setSpeechTimeout("auto")
                    ->setAction("post-call-action.php")
                    ->setMethod("GET");
                $gather->say($this->settings->getPressWord() . " " . $this->settings->word("two") . " " . $this->settings->word('if_you_would_like_to_search_again'))
                    ->setVoice($this->settings->voice())
                    ->setLanguage($this->settings->get("language"));
            }

            $twiml->say($this->settings->word('thank_you_for_calling_goodbye'))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get("language"));
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    private function getResultsString($filtered_list)
    {
        $results_string = array(
            "meeting_name" => str_replace("&", "&amp;", $filtered_list->meeting_name),
            "timestamp" => str_replace("&", "&amp;", $this->settings->word('days_of_the_week')[$filtered_list->weekday_tinyint]
                . ' ' . (new DateTime($filtered_list->start_time))->format($this->settings->get('time_format'))),
            "location" => array(),
            "distance_details" => "",
            "location_links" => array(),
            "links" => array(),
            "format_details" => array(),
            "virtual_meeting_additional_info" => array()
        );

        if (!in_array("TC", explode(",", $filtered_list->formats))) {
            if ($this->settings->has('include_location_text') && json_decode($this->settings->get('include_location_text'))) {
                array_push($results_string["location"], str_replace("&", "&amp;", $filtered_list->location_text));
            }

            array_push($results_string["location"], str_replace("&", "&amp;", $filtered_list->location_street
                . ($filtered_list->location_municipality !== "" ? ", " . $filtered_list->location_municipality : "")
                . ($filtered_list->location_province !== "" ? ", " . $filtered_list->location_province : "")));

            if ($this->settings->has('include_distance_details')) {
                if ($this->settings->get('include_distance_details') == "mi") {
                    $results_string["distance_details"] = sprintf("(%s mi)", round($filtered_list->distance_in_miles));
                } elseif ($this->settings->get('include_distance_details') == "km") {
                    $results_string["distance_details"] = sprintf("(%s km)", round($filtered_list->distance_in_km));
                }
            }

            if ($this->settings->has('include_map_link') && json_decode($this->settings->get('include_map_link'))) {
                array_push($results_string["location_links"], sprintf("https://maps.google.com/maps?q=%s,%s&hl=%s", $filtered_list->latitude, $filtered_list->longitude, $GLOBALS['short_language']));
            }
        }

        if (in_array("VM", explode(",", $filtered_list->formats)) || in_array("HY", explode(",", $filtered_list->formats))) {
            if (isset($filtered_list->virtual_meeting_link) && strlen($filtered_list->virtual_meeting_link) > 0) {
                array_push($results_string["links"], str_replace("&", "&amp;", $filtered_list->virtual_meeting_link));
            }

            if (isset($filtered_list->phone_meeting_number) && strlen($filtered_list->phone_meeting_number) > 0) {
                array_push($results_string["links"], sprintf("tel:%s", str_replace("&", "&amp;", $filtered_list->phone_meeting_number)));
            }

            if (isset($filtered_list->virtual_meeting_additional_info) && strlen($filtered_list->virtual_meeting_additional_info) > 0) {
                array_push($results_string["virtual_meeting_additional_info"], str_replace("&", "&amp;", $filtered_list->virtual_meeting_additional_info));
            }
        }

        if ($this->settings->has('include_format_details') && count($this->settings->get('include_format_details')) > 0) {
            $include_format_details = $this->settings->get('include_format_details');
            foreach ($include_format_details as $include_format_detail) {
                foreach ($filtered_list->format_details as $format_detail) {
                    if ($format_detail->key_string === $include_format_detail) {
                        $results_string["format_details"][] = $format_detail;
                    }
                }
            }
        }

        return $results_string;
    }
}
