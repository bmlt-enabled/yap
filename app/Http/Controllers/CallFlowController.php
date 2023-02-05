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
use SpecialPhoneNumber;
use Twilio\Rest\Voice;
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
            "gender-routing.php",
            null,
            [VolunteerGender::MALE, VolunteerGender::FEMALE, VolunteerGender::NO_PREFERENCE],
            skip_output: true
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
        $address = getIvrResponse($request, skip_output: true);
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
            sprintf("province-voice-input.php?SearchType=%s", $search_type),
            null,
            range(1, count(setting('province_lookup_list'))),
            skip_output: true
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

    public function voicemail(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        getServiceBodyCallHandling(setting("service_body_id"));
        $promptset_name = str_replace("-", "_", getWordLanguage()) . "_voicemail_greeting";

        $twiml = new VoiceResponse();
        if (has_setting($promptset_name)) {
            $twiml->play(setting($promptset_name));
        } else {
            $say = word("please_leave_a_message_after_the_tone").", ".word("hang_up_when_finished");
            $twiml->say($say)
                ->setVoice(voice())
                ->setLanguage(setting("language"));
        }

        $recordingStatusCallback = "voicemail-complete.php?service_body_id=".setting("service_body_id").
            "&caller_id=".urlencode($request->query("caller_id"))."&caller_number=".
            urlencode($request->query("Caller")).getSessionLink(true);

        $twiml->record()
            ->setPlayBeep(true)
            ->setMaxLength(120)
            ->setTimeout(15)
            ->setRecordingStatusCallback($recordingStatusCallback)
            ->setRecordingStatusCallbackMethod("GET");

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
        $response = getIvrResponse($request, "index.php", null, getPossibleDigits('digit_map_location_search_method'));
        if ($response == null) {
            return;
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
}
