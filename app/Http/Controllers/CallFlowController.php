<?php

namespace App\Http\Controllers;

use AlertId;
use App\Constants\EventId;
use App\Constants\SearchType;
use App\Constants\VolunteerGender;
use App\Models\CallRecord;
use App\Models\RecordType;
use CallRole;
use Exception;
use LocationSearchMethod;
use ReadingType;
use SpecialPhoneNumber;
use Twilio\Rest\Voice;
use Twilio\TwiML\Messaging\Message;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Http\Request;
use VolunteerRoutingParameters;
use VolunteerType;

class CallFlowController extends Controller
{
    public function index(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';

        log_debug("version: " . $GLOBALS['version']);
        $digit = getDigitResponse($request, 'language_selections', 'Digits');

        $twiml = new VoiceResponse();
        if (strlen(setting('language_selections')) > 0) {
            if ($digit == null) {
                $twiml->redirect("lng-selector.php");
                return response($twiml)->header("Content-Type", "text/xml");
            } else {
                $selected_language = explode(",", setting('language_selections'))[intval($digit) - 1];
                $_SESSION["override_word_language"] = $selected_language;
                $_SESSION["override_gather_language"] = $selected_language;
                $_SESSION["override_language"] = $selected_language;
                include_once __DIR__.'/../../../lang/'.getWordLanguage().'.php';
            }
        }

        if ($request->has('CallSid')) {
            $phoneNumberSid = $GLOBALS['twilioClient']->calls($request->query('CallSid'))->fetch()->phoneNumberSid;
            $incomingPhoneNumber = $GLOBALS['twilioClient']->incomingPhoneNumbers($phoneNumberSid)->fetch();

            if ($incomingPhoneNumber->statusCallback == null
                || !str_exists($incomingPhoneNumber->statusCallback, "status.php")) {
                insertAlert(AlertId::STATUS_CALLBACK_MISSING, $incomingPhoneNumber->phoneNumber);
            }
        }

        if ($request->has("override_service_body_id")) {
            getServiceBodyCallHandling($request->query("override_service_body_id"));
        }

        $promptset_name = str_replace("-", "_", getWordLanguage()) . "_greeting";
        if (has_setting("extension_dial") && json_decode(setting("extension_dial"))) {
            $gather = $twiml->gather()
                ->setLanguage(setting('gather_language'))
                ->setInput("dtmf")
                ->setFinishOnKey("#")
                ->setTimeout("10")
                ->setAction("service-body-ext-response.php")
                ->setMethod("GET");
            $gather->say("Enter the service body ID, followed by the pound sign.");
        } else {
            $gather = $twiml->gather()
                ->setLanguage(setting('gather_language'))
                ->setInput(getInputType())
                ->setNumDigits("1")
                ->setTimeout("10")
                ->setSpeechTimeout("auto")
                ->setAction("input-method.php")
                ->setMethod("GET");
            $gather->pause()->setLength(setting('initial_pause'));
            if (has_setting($promptset_name)) {
                $gather->play(setting($promptset_name));
            } else {
                if (!$request->has("Digits")) {
                    $gather->say(setting('title'))
                        ->setVoice(voice())
                        ->setLanguage(setting("language"));
                }

                $searchTypeSequence = getDigitMapSequence('digit_map_search_type');

                foreach ($searchTypeSequence as $digit => $type) {
                    if ($type == SearchType::VOLUNTEERS) {
                        $gather->say(getPressWord() . " " . getWordForNumber($digit) . " " . word('to_find') . " " . word('someone_to_talk_to'))
                            ->setVoice(voice())
                            ->setLanguage(setting("language"));
                    } elseif ($type == SearchType::MEETINGS) {
                        $gather->say(getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . word('meetings'))
                            ->setVoice(voice())
                            ->setLanguage(setting("language"));
                    } elseif ($type == SearchType::JFT) {
                        $gather->say(getPressWord() . " " . getWordForNumber($digit) . " " . word('to_listen_to_the_just_for_today'))
                            ->setVoice(voice())
                            ->setLanguage(setting("language"));
                    } elseif ($type == SearchType::SPAD) {
                        $gather->say(getPressWord() . " " . getWordForNumber($digit) . " " . word('to_listen_to_the_spad'))
                            ->setVoice(voice())
                            ->setLanguage(setting("language"));
                    }
                }
            }
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function inputMethod(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $response = getIvrResponse(
            $request,
            getPossibleDigits('digit_map_search_type')
        );
        $twiml = new VoiceResponse();
        if ($response == null) {
            $twiml->say(word('you_might_have_invalid_entry'))
                ->setVoice(voice())
                ->setLanguage(setting('language'));
            $twiml->redirect("index.php");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $searchType = getDigitResponse($request, 'digit_map_search_type', 'Digits');
        $playTitle = $request->has('PlayTitle') ? $request->query('PlayTitle') : 0;

        if ($searchType == SearchType::MEETINGS) {
            insertCallEventRecord(EventId::MEETING_SEARCH);
        } elseif ($searchType == SearchType::JFT) {
            insertCallEventRecord(EventId::JFT_LOOKUP);
        } elseif ($searchType == SearchType::SPAD) {
            insertCallEventRecord(EventId::SPAD_LOOKUP);
        }

        if (($searchType == SearchType::VOLUNTEERS || $searchType == SearchType::MEETINGS)
            && json_decode(setting('disable_postal_code_gather'))) {
            $twiml->redirect("input-method-result.php?SearchType=" . $searchType . "&Digits=1")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($searchType == SearchType::VOLUNTEERS) {
            if (isset($_SESSION['override_service_body_id'])) {
                $twiml->redirect("helpline-search.php?Called=" . $request->query("Called") . getSessionLink(true))
                    ->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml");
            }

            $searchDescription = word('someone_to_talk_to');
        } elseif ($searchType == SearchType::MEETINGS) {
            if (!strpos(setting('custom_query'), '{LATITUDE}') || !strpos(setting('custom_query'), '{LONGITUDE}')) {
                $twiml->redirect("meeting-search.php?Called=" . $request->query("Called"))
                    ->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml");
            }

            $searchDescription = word('meetings');
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
        } elseif ($searchType == SearchType::CUSTOM_EXTENSIONS && count(setting('custom_extensions')) > 0) {
            $twiml->redirect("custom-ext.php")
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $gather = $twiml->gather()
            ->setLanguage(setting('gather_language'))
            ->setInput(getInputType())
            ->setNumDigits("1")
            ->setTimeout("10")
            ->setSpeechTimeout("auto")
            ->setAction("input-method-result.php?SearchType=".$searchType)
            ->setMethod("GET");
        if ($playTitle == "1") {
            $gather->say(setting("title"))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
        }

        if ($request->has("Retry")) {
            $retry_message = $request->has("RetryMessage") ? $request->query("RetryMessage") : word("could_not_find_location_please_retry_your_entry");
            $gather->say($retry_message)
                ->setVoice(voice())
                ->setLanguage(setting("language"));
            $gather->pause()->setLength("1");
        }

        if ($searchDescription == word('meetings') && !json_decode(setting("sms_ask")) && !json_decode(setting("sms_disable"))) {
            $gather->say(word('search_results_by_sms'))
                ->setVoice(voice())
                ->setLanguage(setting('language'));
        }

        $locationSearchMethodSequence = getDigitMapSequence('digit_map_location_search_method');
        foreach ($locationSearchMethodSequence as $digit => $method) {
            if ($method == LocationSearchMethod::VOICE) {
                $gather->say(getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('city_or_county'))
                ->setVoice(voice())
                ->setLanguage(setting('language'));
            } elseif ($method == LocationSearchMethod::DTMF) {
                $gather->say(getPressWord() . " " . getWordForNumber($digit) . " " . word('to_search_for') . " " . $searchDescription . " " . word('by') . " " . word('zip_code'))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            } elseif ($method == SearchType::JFT && $searchType == SearchType::MEETINGS) {
                $gather->say(getWordForNumber($digit) . " " . word('to_listen_to_the_just_for_today'))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            } elseif ($method == SearchType::SPAD && $searchType == SearchType::MEETINGS) {
                $gather->say(getWordForNumber($digit) . " " . word('to_listen_to_the_spad'))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            }
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function zipinput(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $searchType = $request->query("SearchType");
        if ($searchType == SearchType::VOLUNTEERS) {
            $action = sprintf("helpline-search.php?SearchType=%s", $searchType);
        } else {
            $action = sprintf("address-lookup.php?SearchType=%s", $searchType);
        }
        $enterWord = (has_setting('speech_gathering') && json_decode(setting('speech_gathering'))
            ? word('please_enter_or_say_your_digit') : word('please_enter_your_digit'));

        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage(setting("gather_language"))
            ->setInput(getInputType())
            ->setNumDigits(setting('postal_code_length'))
            ->setTimeout(10)
            ->setAction($action)
            ->setMethod("GET");

        if (str_contains(getInputType(), "speech")) {
            $gather->setSpeechTimeout("auto");
        }

        $gather->say(sprintf("%s %s", $enterWord, word('zip_code')))
            ->setVoice(voice())
            ->setLanguage(setting('language'));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function customext()
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage(setting("gather_language"))
            ->setInput(getInputType())
            ->setFinishOnKey("#")
            ->setTimeout(15)
            ->setAction("custom-ext-dialer.php")
            ->setMethod("GET");

        $gather->play(setting(str_replace("-", "_", getWordLanguage()) . "_custom_extensions_greeting"));
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function cityorcountyinput(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $province = json_decode(setting('province_lookup')) ? $request->query("SpeechResult") : "";
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage(setting('gather_language'))
            ->setInput("speech")
            ->setHints(setting('gather_hints'))
            ->setTimeout(10)
            ->setSpeechTimeout("auto")
            ->setAction(sprintf(
                "voice-input-result.php?SearchType=%s&Province=%s",
                $request->query("SearchType"),
                urlencode($province)
            ))
            ->setMethod('GET');
        $gather->say(sprintf("%s %s", word('please_say_the_name_of_the'), word('city_or_county')))
            ->setVoice(voice())
            ->setLanguage(setting('language'));

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
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $gender = getIvrResponse(
            $request,
            [VolunteerGender::MALE, VolunteerGender::FEMALE, VolunteerGender::NO_PREFERENCE],
        );
        $twiml = new VoiceResponse();
        if ($gender == null) {
            $twiml->say(word('you_might_have_invalid_entry'))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
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
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $province = (has_setting('province_lookup')
        && json_decode(setting('province_lookup')) ? $request->query('Province') : getProvince());
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
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $address = getIvrResponse($request);
        $coordinates = getCoordinatesForAddress($address);
        insertCallEventRecord(
            EventId::MEETING_SEARCH_LOCATION_GATHERED,
            (object)['gather' => $address, 'coordinates' => $coordinates ?? null]
        );
        $twiml = new VoiceResponse();
        if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
            $twiml->redirect(sprintf("input-method.php?Digits=%s&Retry=1", $request->query('SearchType')))
                ->setMethod('GET');
        } else {
            $twiml->say(sprintf("%s %s", word('searching_meeting_information_for'), $coordinates->location))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
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
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $exploded_result = explode("\|", setting("helpline_fallback"));
        $phone_number = isset($exploded_result[0]) ? $exploded_result[0] : "";
        $extension = isset($exploded_result[1]) ? $exploded_result[1] : "w";
        $twiml = new VoiceResponse();
        $twiml->say(sprintf(
            "%s... %s... %s.",
            word('there_seems_to_be_a_problem'),
            word('please_wait_while_we_connect_your_call'),
            word('please_stand_by')
        ))->setVoice(voice())->setLanguage(setting("language"));
        $twiml->dial()->number($phone_number, ['sendDigits' => $extension]);

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function customextdialer(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $twiml = new VoiceResponse();
        $dial = $twiml->dial()->setCallerId($request->query("Called"));
        $dial->number(setting('custom_extensions')[str_replace("#", "", $request->query('Digits'))]);
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function dialback(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage(setting("gather_language"))
            ->setInput("dtmf")
            ->setTimeout(15)
            ->setFinishOnKey("#")
            ->setAction("dialback-dialer.php")
            ->setMethod("GET");
        $gather->say("Please enter the dialback pin, followed by the pound sign.")
            ->setVoice(voice())
            ->setLanguage(setting("language"));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function dialbackDialer(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $dialbackPinValid = isDialbackPinValid($request->query("Digits"));
        $twiml = new VoiceResponse();
        if ($dialbackPinValid) {
            $twiml->say(word('please_wait_while_we_connect_your_call'))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
            $twiml->dial($request->query("Digits"))
                ->setCallerId($request->query("Called"));
        } else {
            $twiml->say("Invalid pin entry")
                ->setVoice(voice())
                ->setLanguage(setting("language"));
            $twiml->pause()->setLength(2);
            $twiml->redirect("index.php");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function genderrouting(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $gender_no_preference = (setting("gender_no_preference")
            ? sprintf(", %s %s %s", getPressWord(), word('three'), word('speak_no_preference'))
            : "");
        $twiml = new VoiceResponse();
        $gather = $twiml->gather()
            ->setLanguage(setting('gather_language'))
            ->setHints(setting('gather_hints'))
            ->setInput(getInputType())
            ->setTimeout(10)
            ->setSpeechTimeout("auto")
            ->setAction(sprintf("gender-routing-response.php?SearchType=%s", $request->query("SearchType")))
            ->setMethod('GET');

        $gather->say(sprintf(
            "%s %s %s, %s %s %s%s",
            getPressWord(),
            word('one'),
            word('to_speak_to_a_man'),
            getPressWord(),
            word('two'),
            word('to_speak_to_a_woman'),
            $gender_no_preference
        ))
            ->setVoice(voice())
            ->setLanguage(setting('language'));
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function provincelookuplistresponse(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $search_type = $request->query("SearchType");
        $province_lookup_item = getIvrResponse(
            $request,
            range(1, count(setting('province_lookup_list')))
        );
        $twiml = new VoiceResponse();
        if ($province_lookup_item == null) {
            $twiml->say(word('you_might_have_invalid_entry'))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
            $twiml->redirect(sprintf("province-voice-input.php?SearchType=%s", $search_type))
                ->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        insertCallEventRecord(
            EventId::PROVINCE_LOOKUP_LIST,
            (object)['province_lookup_list' => setting('province_lookup_list')[$province_lookup_item - 1]]
        );

        $twiml->redirect(sprintf(
            "city-or-county-voice-input.php?SearchType=%s&SpeechResult=%s",
            $search_type,
            urlencode(setting('province_lookup_list')[$province_lookup_item - 1])
        ));

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function statusCallback(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $callRecord = new CallRecord();
        $callRecord->callSid = $request->query('CallSid');
        $callRecord->to_number = $request->query('Called');
        $callRecord->from_number = $request->query('Caller');
        $callRecord->duration = intval($request->query('CallDuration'));

        if ($request->query("TimestampNow")) {
            $start_time = date("Y-m-d H:i:s");
            $end_time = date("Y-m-d H:i:s");
        } else {
            require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';
            $twilioRecords = $GLOBALS['twilioClient']->calls($callRecord->callSid)->fetch();
            $start_time = $twilioRecords->startTime->format("Y-m-d H:i:s");
            $end_time = $twilioRecords->endTime->format("Y-m-d H:i:s");
        }
        $callRecord->start_time = $start_time;
        $callRecord->end_time = $end_time;
        $callRecord->type = RecordType::PHONE;
        $callRecord->payload = json_encode($request->query->all());

        insertCallRecord($callRecord);

        $twiml = new VoiceResponse();
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function postCallAction(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';
        $sms_messages = $request->query('Payload') ? json_decode(urldecode($request->query('Payload'))) : [];
        $digits = getIvrResponse($request);

        $twiml = new VoiceResponse();
        if (($digits == 1 || $digits == 3) && count($sms_messages) > 0) {
            if (setting("sms_combine")) {
                $GLOBALS['twilioClient']->messages->create(
                    $request->query('From'),
                    array("from" => $request->query('To'),
                        "body" => implode(
                            "\n\n",
                            $sms_messages
                        ))
                );
            } else {
                for ($i = 0; $i < count($sms_messages); $i++) {
                    $GLOBALS['twilioClient']->messages->create(
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
            $twiml->say(word('thank_you_for_calling_goodbye'))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function languageSelector(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $twiml = new VoiceResponse();
        if (!has_setting('language_selections')) {
            $twiml->say("language gateway options are not set, please refer to the documentation to utilize this feature.");
            $twiml->hangup();
        } else {
            $language_selection_options = explode(",", setting('language_selections'));
            $twiml->pause()->setLength(setting('initial_pause'));
            $gather = $twiml->gather()
                ->setLanguage(setting('gather_language'))
                ->setInput(getInputType())
                ->setNumDigits(1)
                ->setTimeout(10)
                ->setSpeechTimeout("auto")
                ->setAction("index.php")
                ->setMethod("GET");
            for ($i = 0; $i < count($language_selection_options); $i++) {
                include __DIR__ . '/../../../lang/' . $language_selection_options[$i] . '.php';
                $message = sprintf(
                    "%s %s %s %s",
                    word('for'),
                    word('language_title'),
                    getPressWord(),
                    getWordForNumber($i + 1)
                );
                $gather->say($message)
                    ->setVoice(voice(str_replace("-", "_", $language_selection_options[$i])))
                    ->setLanguage($language_selection_options[$i]);
            }
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function provinceVoiceInput(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $province_lookup_list = setting('province_lookup_list');
        $twiml = new VoiceResponse();
        if (count($province_lookup_list) > 0) {
            for ($i = 0; $i < count($province_lookup_list); $i++) {
                $say = sprintf(
                    "%s %s %s %s",
                    word('for'),
                    $province_lookup_list[$i],
                    getPressWord(),
                    getWordForNumber($i + 1)
                );
                $gather = $twiml->gather()
                    ->setLanguage(setting("gather_language"))
                    ->setHints(setting('gather_hints'))
                    ->setInput(getInputType())
                    ->setNumDigits(1)
                    ->setTimeout(10)
                    ->setSpeechTimeout("auto")
                    ->setAction("province-lookup-list-response.php?SearchType=".$request->query("SearchType"))
                    ->setMethod("GET");
                $gather->say($say)
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
            }
        } else {
            $say = sprintf("%s %s", word('please_say_the_name_of_the'), word('state_or_province'));
            $gather = $twiml->gather()
                ->setLanguage(setting("gather_language"))
                ->setHints(setting('gather_hints'))
                ->setInput("speech")
                ->setTimeout(10)
                ->setSpeechTimeout("auto")
                ->setAction("city-or-county-voice-input.php?SearchType=".$request->query("SearchType"))
                ->setMethod("GET");
            $gather->say($say)
                ->setVoice(voice())
                ->setLanguage(setting("language"));
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineAnswerResponse(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';

        $twiml = new VoiceResponse();
        if ($request->query('Digits') == "1") {
            $conferences = $GLOBALS['twilioClient']->conferences
                ->read(array ("friendlyName" => $request->query('conference_name') ));
            $participants = $GLOBALS['twilioClient']->conferences($conferences[0]->sid)->participants->read();

            if (count($participants) == 2) {
                error_log("Enough volunteers have joined.  Hanging up this volunteer.");
                $twiml->say(word('volunteer_has_already_joined_the_call_goodbye'))
                    ->setVoice(voice())->setLanguage(setting('language'));
                $twiml->hangup();
            } else {
                insertCallEventRecord(EventId::VOLUNTEER_IN_CONFERENCE, (object)["to_number" => $request->query('Called')]);
                $dial = $twiml->dial();
                $dial->conference($request->query("conference_name"))
                    ->setStatusCallbackMethod("GET")
                    ->setStatusCallbackEvent("join")
                    ->setStartConferenceOnEnter("true")
                    ->setEndConferenceOnExit("true")
                    ->setBeep("false");
            }
        } else {
            insertCallEventRecord(
                EventId::VOLUNTEER_REJECTED,
                (object)["digits" => $request->query('Digits'),
                    "to_number" => $request->query('Called')]
            );
            incrementNoAnswerCount();
            setConferenceParticipant($request->query('conference_name'), $request->query('CallSid'), CallRole::VOLUNTEER);
            log_debug("They rejected the call.");
            $twiml->hangup();
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineOutdialResponse(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';

        $conferences = $GLOBALS['twilioClient']->conferences
            ->read(array ("friendlyName" => $request->query('conference_name') ));
        $participants = $GLOBALS['twilioClient']->conferences($conferences[0]->sid)->participants->read();

        $twiml = new VoiceResponse();
        if (count($participants) == 2) {
            setConferenceParticipant($request->query('conference_name'), $request->query('CallSid'), CallRole::VOLUNTEER);
            error_log("Enough volunteers have joined.  Hanging up this volunteer.");
            $twiml->say(word('volunteer_has_already_joined_the_call_goodbye'))
                ->setVoice(voice())->setLanguage(setting('language'));
            $twiml->hangup();
        } elseif (count($participants) > 0) {
            insertCallEventRecord(EventId::VOLUNTEER_ANSWERED, (object)['to_number' => $request->query('Called')]);
            setConferenceParticipant($request->query('conference_name'), $request->query('CallSid'), CallRole::VOLUNTEER);
            error_log("Volunteer picked up or put to their voicemail, asking if they want to take the call, timing out after 15 seconds of no response.");
            if (has_setting('volunteer_auto_answer') && setting('volunteer_auto_answer')) {
                $twiml->redirect("helpline-answer-response.php?Digits=1&conference_name=".$request->query('conference_name')."&service_body_id=" . $request->query('service_body_id') . getSessionLink(true))
                ->setMethod("GET");
            } else {
                $gather = $twiml->gather()
                    ->setActionOnEmptyResult(true)
                    ->setNumDigits("1")
                    ->setTimeout("15")
                    ->setAction("helpline-answer-response.php?conference_name=".$request->query('conference_name')."&service_body_id=" . $request->query('service_body_id') . getSessionLink(true))
                    ->setMethod("GET");
                $gather->say(word('you_have_a_call_from_the_helpline'))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            }
        } else {
            setConferenceParticipant($request->query('conference_name'), $request->query('CallSid'), CallRole::CALLER);
            insertCallEventRecord(EventID::VOLUNTEER_ANSWERED_BUT_CALLER_HUP, (object)['to_number' => $request->query('Called')]);
            error_log("The caller hungup.");
            $twiml->say(word('the_caller_hungup'))
                ->setVoice(voice())->setLanguage(setting('language'));
            $twiml->hangup();
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineSms(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';
        try {
            if ($request->has("OriginalCallerId")) {
                $original_caller_id = $request->query("OriginalCallerId");
            }

            $service_body = getServiceBodyCoverage($request->query("Latitude"), $request->query("Longitude"));
            $serviceBodyCallHandling   = getServiceBodyCallHandling($service_body->id);
            $tracker                   = $request->has("tracker") ? 0 : $request->query("tracker");

            if ($serviceBodyCallHandling->sms_routing_enabled) {
                $volunteer_routing_parameters = new VolunteerRoutingParameters();
                $volunteer_routing_parameters->service_body_id = $serviceBodyCallHandling->service_body_id;
                $volunteer_routing_parameters->tracker = $tracker;
                $volunteer_routing_parameters->cycle_algorithm = $serviceBodyCallHandling->sms_strategy;
                $volunteer_routing_parameters->volunteer_type = VolunteerType::SMS;
                $phone_numbers = explode(',', getHelplineVolunteer($volunteer_routing_parameters)->phoneNumber);

                $GLOBALS['twilioClient']->messages->create(
                    $original_caller_id,
                    array(
                        "body" => word('your_request_has_been_received'),
                        "from" => $request->query('To')
                    )
                );

                foreach ($phone_numbers as $phone_number) {
                    if ($phone_number == SpecialPhoneNumber::UNKNOWN) {
                        $phone_number = $serviceBodyCallHandling->primary_contact_number;
                    }

                    if ($phone_number != "") {
                        $GLOBALS['twilioClient']->messages->create(
                            $phone_number,
                            array(
                                "body" => sprintf(
                                    "%s: %s %s %s",
                                    word('helpline'),
                                    word('someone_is_
                                    requesting_sms_help_from'),
                                    $original_caller_id,
                                    word('please_call_or_text_them_back')
                                ),
                                "from" => $request->query('To')
                            )
                        );
                    } else {
                        log_debug("No phone number was found and no fallback number in primary_contact_number was found.");
                    }
                }
            } else {
                log_debug(sprintf("SMS Helpline capability not enabled for service body id: %s", $service_body->id));
            }
        } catch (Exception $e) {
            log_debug($e);
            $GLOBALS['twilioClient']->messages->create(
                $original_caller_id,
                array(
                    "body" => word('could_not_find_a_volunteer'),
                    "from" => $request->query('To')
                )
            );
        }

        $twiml = new VoiceResponse();
        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function inputMethodResult(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $twiml = new VoiceResponse();
        $response = getIvrResponse($request, getPossibleDigits('digit_map_location_search_method'));
        if ($response == null) {
            $twiml->say(word('you_might_have_invalid_entry'))
                ->setVoice(voice())
                ->setLanguage(setting('language'));
            $twiml->redirect("index.php");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        $locationSearchMethod = getDigitResponse($request, 'digit_map_location_search_method', 'Digits');
        if ($locationSearchMethod == SearchType::JFT) {
            $twiml->redirect("fetch-jft.php")->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        } elseif ($locationSearchMethod == SearchType::SPAD) {
            $twiml->redirect("fetch-spad.php")->setMethod("GET");
            return response($twiml)->header("Content-Type", "text/xml");
        }

        if (has_setting('province_lookup') && json_decode(setting('province_lookup'))) {
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
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';

        $callRecord = new CallRecord();
        $callRecord->callSid = $request->query('SmsSid');
        $callRecord->to_number = $request->query('To');
        $callRecord->from_number = $request->query('From');
        $callRecord->duration = 0;
        $callRecord->start_time = date("Y-m-d H:i:s");
        $callRecord->end_time = date("Y-m-d H:i:s");
        $callRecord->type = RecordType::SMS;
        $callRecord->payload = json_encode($_REQUEST);

        insertCallRecord($callRecord);

        checkSMSBlackhole();

        $address = $request->get('Body');
        if (str_exists($address, ',')) {
            $coordinates = getCoordinatesForAddress($address);
        } else {
            $coordinates = getCoordinatesForAddress($address . "," . getProvince());
        }

        $twiml = new VoiceResponse();
        $sms_helpline_keyword = setting("sms_helpline_keyword");
        if (str_exists(strtoupper($address), strtoupper($sms_helpline_keyword))) {
            if (strlen(trim(str_replace(strtoupper($sms_helpline_keyword), "", strtoupper($address)))) > 0) {
                $twiml->redirect("helpline-sms.php?OriginalCallerId=" . $request->get("From") . "&To=" . $request->get("To") . "&Latitude=" . $coordinates->latitude . "&Longitude=" . $coordinates->longitude)
                    ->setMethod("GET");
            } else {
                $message = word('please_send_a_message_formatting_as') . " " . $sms_helpline_keyword . ", " . word('followed_by_your_location') . " " . word('for') . " " .  word('someone_to_talk_to');
                $GLOBALS['twilioClient']->messages->create($request->get("From"), array("from" => $request->get("To"), "body" => $message));
            }
        } elseif (json_decode(setting('jft_option')) && str_exists(strtoupper($address), strtoupper('jft'))) {
            $reading_chunks = get_reading(ReadingType::JFT, true);
            for ($i = 0; $i < count($reading_chunks); $i++) {
                $GLOBALS['twilioClient']->messages->create($request->get("From"), array("from" => $request->get("To"), "body" => $reading_chunks[$i]));
            }
        } elseif (json_decode(setting('spad_option')) && str_exists(strtoupper($address), strtoupper('spad'))) {
            $reading_chunks = get_reading(ReadingType::SPAD, true);
            for ($i = 0; $i < count($reading_chunks); $i++) {
                $GLOBALS['twilioClient']->messages->create($request->get("From"), array("from" => $request->get("To"), "body" => $reading_chunks[$i]));
            }
        } else {
            insertCallEventRecord(EventId::MEETING_SEARCH_SMS);
            insertCallEventRecord(
                EventId::MEETING_SEARCH_LOCATION_GATHERED,
                (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
            );
            $twiml->redirect("meeting-search.php?SearchType=" . getDigitForAction('digit_map_search_type', SearchType::VOLUNTEERS) . "&Latitude=" . $coordinates->latitude . "&Longitude=" . $coordinates->longitude)
                ->setMethod("GET");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function meetingSearch(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';
        $twiml = new VoiceResponse();

        $latitude = $request->has("Latitude") ? $request->get('Latitude') : null;
        $longitude = $request->has("Longitude") ? $request->get('Longitude') : null;

        try {
            $suppress_voice_results = has_setting('suppress_voice_results') && json_decode(setting('suppress_voice_results'));
            $sms_disable = has_setting('sms_disable') && json_decode(setting('sms_disable'));
            $results_count = has_setting('result_count_max') ? intval(setting('result_count_max')) : 5;
            $meeting_results = getMeetings($latitude, $longitude, $results_count, null, null);
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
                $twiml->say(word('no_results_found') . "... " .
                    word('you_might_have_invalid_entry') . "... " . word('try_again'))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
                $twiml->redirect("input-method.php?Digits=2")
                    ->setMethod("GET");
            } elseif (count($filtered_list) == 0) {
                $twiml->say(word('there_are_no_other_meetings_for_today') . ".... " . word('try_again'))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
                $twiml->redirect("input-method.php?Digits=2")
                    ->setMethod("GET");
            } elseif ($suppress_voice_results) {
                $twiml->say($results_count_num  . " " . word('meetings_have_been_texted'))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
            } else {
                $twiml->say(word('meeting_information_found_listing_the_top') . " "
                    . $results_count_num . " " . word('results'))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
            }
        } else {
            if ($meeting_results->originalListCount == 0) {
            } elseif (count($filtered_list) == 0) {
            }
        }

        $results_counter = 0;
        for ($i = 0; $i < count($filtered_list); $i++) {
            $results = getResultsString($filtered_list[$i]);

            if (!$isFromSmsGateway && !$suppress_voice_results) {
                $twiml->pause()->setLength(1);
                $twiml->say(word('number') . " " . ($results_counter + 1))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
                $twiml->say($results['meeting_name'])
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
                $twiml->pause()->setLength(1);
                $twiml->say(word('starts_at') . " " . $results['timestamp'])
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));

                if (has_setting('include_format_details') && count(setting('include_format_details')) > 0) {
                    for ($fd = 0; $fd < count($results['format_details']); $fd++) {
                        $twiml->pause()->setLength(1);
                        $twiml->say(word('number') . " " . $results['format_details'][$fd]->description_string)
                            ->setVoice(voice())
                            ->setLanguage(setting("language"));
                    }
                }

                for ($ll = 0; $ll < count($results['location']); $ll++) {
                    $twiml->pause()->setLength(1);
                    $twiml->say($results['location'][$ll])
                        ->setVoice(voice())
                        ->setLanguage(setting("language"));
                }

                if (has_setting("say_links") && json_decode(setting("say_links"))) {
                    for ($fl = 0; $fl < count($results['links']); $fl++) {
                        $twiml->pause()->setLength(1);
                        $twiml->say($results['links'][$fl])
                            ->setVoice(voice())
                            ->setLanguage(setting("language"));
                    }
                }

                for ($vmai = 0; $vmai < count($results['virtual_meeting_additional_info']); $vmai++) {
                    $twiml->pause()->setLength(1);
                    $twiml->say($results['virtual_meeting_additional_info'][$vmai])
                        ->setVoice(voice())
                        ->setLanguage(setting("language"));
                }

                if ($request->has("Debug")) {
                    $twiml->say(json_encode($filtered_list[$i]))
                        ->setVoice(voice())
                        ->setLanguage(setting("language"));
                }
            }

            $results_counter++;
            if ($results_counter == $results_count) {
                break;
            }
        }

        if (!$sms_disable && has_setting('sms_summary_page') && json_decode(setting('sms_summary_page'))) {
            $voice_url = "https://" . $request->server('HTTP_HOST') . $request->server('PHP_SELF');
            if (strpos(basename($voice_url), ".php")) {
                $webhook_url = substr($voice_url, 0, strrpos($voice_url, "/"));
            } elseif (strpos($voice_url, "?")) {
                $webhook_url = substr($voice_url, 0, strrpos($voice_url, "?"));
            } else {
                $webhook_url = $voice_url;
            }

            $message = sprintf("Meeting Results, click here: %s/msr/%s/%s", $webhook_url, $latitude, $longitude);

            if (json_decode(setting("sms_ask")) && !$isFromSmsGateway) {
                array_push($sms_messages, $message);
            } else {
                sendSms($message);
            }
        } elseif (!$sms_disable) {
            $results_counter = 0;
            for ($i = 0; $i < count($filtered_list); $i++) {
                $results = getResultsString($filtered_list[$i]);
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

                if (json_decode(setting("sms_combine")) || (json_decode(setting("sms_ask")) && !$isFromSmsGateway)) {
                    array_push($sms_messages, $message);
                } else {
                    sendSms($message);
                }

                $results_counter++;
                if ($results_counter == $results_count) {
                    break;
                }
            }

            if (json_decode(setting("sms_combine")) && !json_decode(setting("sms_ask"))) {
                sendSms(implode("\n\n", $sms_messages));
            }
        }

        if (!$isFromSmsGateway && count($filtered_list) > 0) {
            $twiml->pause()->setLength(2);
            if (!$sms_disable && !$suppress_voice_results && setting("sms_ask") && count($sms_messages) > 0) {
                $gather = $twiml->gather()
                    ->setNumDigits(1)
                    ->setTimeout(10)
                    ->setSpeechTimeout("auto")
                    ->setInput(getInputType())
                    ->setAction("post-call-action.php?Payload=" . urlencode(json_encode($sms_messages)))
                    ->setMethod("GET");
                $gather->say(getPressWord() . " " . word("one") . " " . word('if_you_would_like_these_results_texted_to_you'))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
                if (json_decode(setting('infinite_searching'))) {
                    $gather->say(getPressWord() . " " . word("two") . " " . word('if_you_would_like_to_search_again') . "..." . getPressWord() . " " . word("three") . " " . word('if_you_would_like_to_do_both'))
                        ->setVoice(voice())
                        ->setLanguage(setting("language"));
                }
            } elseif (json_decode(setting('infinite_searching'))) {
                $gather = $twiml->gather()
                    ->setNumDigits(1)
                    ->setTimeout(10)
                    ->setSpeechTimeout("auto")
                    ->setAction("post-call-action.php")
                    ->setMethod("GET");
                $gather->say(getPressWord() . " " . word("two") . " " . word('if_you_would_like_to_search_again'))
                    ->setVoice(voice())
                    ->setLanguage(setting("language"));
            }

            $twiml->say(word('thank_you_for_calling_goodbye'))
                ->setVoice(voice())
                ->setLanguage(setting("language"));
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }

    public function helplineSearch(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        require_once __DIR__ . '/../../../legacy/_includes/twilio-client.php';
        $twiml = new VoiceResponse();
        $dial_string = "";

        if (!$request->has('ForceNumber')) {
            if (isset($_SESSION["override_service_body_id"])) {
                $service_body_obj = getServiceBody(setting("service_body_id"));
            } else {
                $address = isset($_SESSION['Address']) ? $_SESSION['Address'] : getIvrResponse();
                if ($address == null) {
//            $twiml->say(word('you_might_have_invalid_entry'))
//                ->setVoice(voice())
//                ->setLanguage(setting('language'));
//            $twiml->redirect("index.php");
//            return response($twiml)->header("Content-Type", "text/xml");
                }
                $coordinates  = getCoordinatesForAddress($address);
                try {
                    if (!isset($coordinates->latitude) && !isset($coordinates->longitude)) {
                        throw new Exception("Couldn't find an address for that location.");
                    }

                    $service_body_obj = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
                } catch (Exception $e) {
                    $twiml->redirect("input-method.php?Digits=" . $request->get("SearchType") . "&Retry=1&RetryMessage=" . urlencode($e->getMessage()))
                        ->setMethod("GET");
                    return response($twiml)->header("Content-Type", "text/xml");
                }
            }

            $location    = $service_body_obj->name;
            if (isset($service_body_obj->helpline)) {
                $dial_string = explode(":", $service_body_obj->helpline)[0];
            } else {
                $dial_string = has_setting("fallback_number") ? setting("fallback_number") : "0000000000";
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
            && (isset($_SESSION["override_service_body_id"]) || isServiceBodyHelplingCallInternallyRoutable($coordinates->latitude, $coordinates->longitude))) {
            $serviceBodyCallHandling = getServiceBodyCallHandling($service_body_id);
        }

        if (isset($address)) {
            insertCallEventRecord(
                EventId::VOLUNTEER_SEARCH,
                (object)['gather' => $address, 'coordinates' => isset($coordinates) ? $coordinates : null]
            );
        } else {
            insertCallEventRecord(EventId::VOLUNTEER_SEARCH);
        }

        if ($service_body_id > 0 && isset($serviceBodyCallHandling)
            && $serviceBodyCallHandling->volunteer_routing_enabled) {
            if ($serviceBodyCallHandling->gender_routing_enabled && !isset($_SESSION['Gender'])) {
                if (isset($address)) {
                    $_SESSION["Address"] = $address;
                }

                $searchType = $request->get("SearchType") ?? "-1";
                $twiml->redirect("gender-routing.php?SearchType=" . urlencode($searchType))->setMethod("GET");
                return response($twiml)->header("Content-Type", "text/xml");
            } elseif ($serviceBodyCallHandling->volunteer_routing_redirect
                && $serviceBodyCallHandling->volunteer_routing_redirect_id > 0) {
                $calculated_service_body_id = $serviceBodyCallHandling->volunteer_routing_redirect_id;
                $serviceBodyCallHandling = getServiceBodyCallHandling($calculated_service_body_id);
            } else {
                $calculated_service_body_id = $service_body_id;
            }

            if (setting("announce_servicebody_volunteer_routing")) {
                $twiml->say(sprintf("%s... %s %s", word('please_stand_by'), word('relocating_your_call_to'), $location))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            } else {
                $twiml->say(word('please_wait_while_we_connect_your_call'))
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            }

            $dial = $twiml->dial();
            $dial->conference(getConferenceName($calculated_service_body_id))
                ->setWaitUrl($serviceBodyCallHandling->moh_count == 1 ? $serviceBodyCallHandling->moh : "playlist.php?items=" . $serviceBodyCallHandling->moh)
                ->setStatusCallback("helpline-dialer.php?service_body_id=" . $calculated_service_body_id . "&Caller=" . $request->get('Called') . getSessionLink(true))
                ->setStartConferenceOnEnter("false")
                ->setEndConferenceOnExit("true")
                ->setStatusCallbackMethod("GET")
                ->setStatusCallbackEvent("start join end leave")
                ->setWaitMethod("GET")
                ->setBeep("false");
        } elseif ($phone_number != "") {
            if (!$request->has("ForceNumber")) {
                $twiml->say(word('please_stand_by') . "... " . word('relocating_your_call_to') . "... " . $location)
                    ->setVoice(voice())
                    ->setLanguage(setting('language'));
            } elseif ($request->has("ForceNumber")) {
                if ($captcha) {
                    $gather = $twiml->gather()
                        ->setLanguage(setting('gather_language'))
                        ->setHints(setting('gather_hints'))
                        ->setInput("dtmf")
                        ->setTimeout(15)
                        ->setNumDigits(1)
                        ->setAction("helpline-search.php?CaptchaVerified=1&ForceNumber=" . urlencode($request->get('ForceNumber')) . getSessionLink(true) . " " . $waiting_message ? "&amp;WaitingMessage=1" : "");

                    $gather->say(setting('title') .  "..." . word('press_any_key_to_continue'))
                        ->setVoice(voice())
                        ->setLanguage(setting('language'));
                    $twiml->hangup();
                } elseif ($waiting_message) {
                    $twiml->say(!$captcha_verified ? setting('title') : "" .  word('please_wait_while_we_connect_your_call'))
                        ->setVoice(voice())
                        ->setLanguage(setting('language'));
                }
            }
            insertCallEventRecord(EventId::HELPLINE_ROUTE, (object)["helpline_number" => $phone_number, "extension" => $extension]);
            $dial = $twiml->dial();
            $dial->number($phone_number)
                ->setSendDigits($extension);
        } else {
            $twiml->redirect("input-method.php?Digits=" . urlencode($_REQUEST["SearchType"]) . "&Retry=1&RetryMessage=" . urlencode(word('the_location_you_entered_is_not_found')))
                ->setMethod("GET");
        }

        return response($twiml)->header("Content-Type", "text/xml");
    }
}
