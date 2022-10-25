<?php

namespace App\Http\Controllers;

use App\Constants\SearchType;
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

        return response()->view("gather", [
            "inputType" => getInputType(),
            "numDigits" => setting('postal_code_length'),
            "action" => $action,
            "voice" => voice(),
            "language" => setting('language'),
            "sayText" => sprintf("%s %s", $enterWord, word("zip_code"))
        ])->header("Content-Type", "text/xml");
    }
}
