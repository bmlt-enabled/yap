<?php

namespace App\Http\Controllers;

use App\Constants\SearchType;
use App\Constants\VolunteerGender;
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

        return response()->view("gather.say", [
            "inputType" => getInputType(),
            "numDigits" => setting('postal_code_length'),
            "action" => $action,
            "voice" => voice(),
            "timeout" => 10,
            "gatherLanguage" => setting('gather_language'),
            "language" => setting('language'),
            "sayText" => sprintf("%s %s", $enterWord, word('zip_code'))
        ])->header("Content-Type", "text/xml");
    }

    public function customext()
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        return response()->view("gather.play", [
            "inputType" => getInputType(),
            "numDigits" => setting('postal_code_length'),
            "action" => "custom-ext-dialer.php",
            "voice" => voice(),
            "timeout" => 15,
            "gatherLanguage" => setting('gather_language'),
            "language" => setting('language'),
            "playUrl" => setting(str_replace("-", "_", getWordLanguage()) . "_custom_extensions_greeting")
        ])->header("Content-Type", "text/xml");
    }

    public function cityorcountyinput(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $province = json_decode(setting('province_lookup')) ? $request->query("SpeechResult") : "";
        return response()->view("gather.say", [
            "inputType" => "speech",
            "action" => sprintf(
                "voice-input-result.php?SearchType=%s&Province=%s",
                $request->query("SearchType"),
                urlencode($province)
            ),
            "hints" => setting('gather_hints'),
            "voice" => voice(),
            "timeout" => 15,
            "gatherLanguage" => setting('gather_language'),
            "language" => setting('language'),
            "sayText" => sprintf("%s %s", word('please_say_the_name_of_the'), word('city_or_county'))
        ])->header("Content-Type", "text/xml");
    }

    public function servicebodyextresponse(Request $request)
    {
        return response()->view("redirect", [
            "redirectUrl" => sprintf("helpline-search.php?override_service_body_id=%s", $request->query('Digits'))
        ])->header("Content-Type", "text/xml");
    }

    public function genderroutingresponse(Request $request)
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $gender = getIvrResponse(
            "gender-routing.php",
            null,
            [VolunteerGender::MALE, VolunteerGender::FEMALE, VolunteerGender::NO_PREFERENCE]
        );
        if ($gender == null) {
            return response()->view("redirect", [
                "voice" => voice(),
                "language" => setting("language"),
                "redirectUrl" => "gender-routing.php",
                "sayText" => word('you_might_have_invalid_entry')
            ])->header("Content-Type", "text/xml");
        } else {
            $_SESSION['Gender'] = $gender;
            return response()->view("redirect", [
                "redirectUrl" => sprintf("helpline-search.php?SearchType=%s", $request->query('SearchType'))
            ])->header("Content-Type", "text/xml");
        }
    }
}
