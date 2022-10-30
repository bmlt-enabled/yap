<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Voice;
use Twilio\TwiML\VoiceResponse;

class FetchJFTController extends Controller
{

    public function index()
    {
        require_once __DIR__ . '/../../../legacy/_includes/functions.php';
        $jft_array = get_jft();
        $twiml = new VoiceResponse();
        foreach ($jft_array as $item) {
            $twiml->say(str_replace("&nbsp;", " ", $item))
                ->setVoice(voice())
                ->setLanguage(setting('language'));
        }
        return response($twiml)->header("Content-Type", "text/xml");
    }
}
