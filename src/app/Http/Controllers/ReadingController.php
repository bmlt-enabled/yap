<?php

namespace App\Http\Controllers;

use App\Constants\ReadingType;
use App\Services\ReadingService;
use App\Services\SettingsService;
use Twilio\TwiML\VoiceResponse;

class ReadingController extends Controller
{
    protected ReadingService $reading;
    protected SettingsService $settings;

    public function __construct(ReadingService $reading, SettingsService $settings)
    {
        $this->reading = $reading;
        $this->settings = $settings;
    }

    public function jft(ReadingService $reading, SettingsService $settings)
    {
        $jft_array = $this->reading->get(ReadingType::JFT);
        $twiml = new VoiceResponse();
        foreach ($jft_array as $item) {
            $twiml->say(str_replace("&nbsp;", " ", $item))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
        }
        return response($twiml)->header("Content-Type", "text/xml; charset=utf-8");
    }

    public function spad()
    {
        $spad_array = $this->reading->get(ReadingType::SPAD);
        $twiml = new VoiceResponse();
        foreach ($spad_array as $item) {
            $twiml->say(str_replace("&nbsp;", " ", $item))
                ->setVoice($this->settings->voice())
                ->setLanguage($this->settings->get('language'));
        }
        return response($twiml)->header("Content-Type", "text/xml; charset=utf-8");
    }
}
