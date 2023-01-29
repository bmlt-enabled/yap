<?php

namespace App\Http\Controllers;

use App\Constants\EventId;
use App\Constants\SearchType;
use App\Constants\VolunteerGender;
use Twilio\Rest\Voice;
use Twilio\TwiML\VoiceResponse;
use Illuminate\Http\Request;

class CallFlowController extends Controller
{
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
        && json_decode(setting('province_lookup')) ? $_REQUEST['Province'] : getProvince());
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
        $address = getIvrResponse(skip_output: true);
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
}
