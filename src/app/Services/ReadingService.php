<?php

namespace App\Services;

use App\Constants\ReadingType;
use App\Utilities\Sms;
use Illuminate\Support\Facades\App;
use FetchMeditation\JFTSettings;
use FetchMeditation\JFTLanguage;
use FetchMeditation\JFT;

class ReadingService extends Service
{
    protected HttpService $http;

    public function __construct(HttpService $http)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->http = $http;
    }

    public function get($reading = ReadingType::JFT, $sms = false): array
    {
        $wordLanguage = $this->settings->get('word_language');

        if ($wordLanguage == 'en-US' || $wordLanguage == 'en-AU') {
            $settings = new JFTSettings(JFTLanguage::English);
        } elseif ($wordLanguage == 'pt-BR' || $wordLanguage == 'pt-PT') {
            $settings = new JFTSettings(JFTLanguage::Portuguese);
        } elseif ($wordLanguage == 'es-ES' || $wordLanguage == 'es-US') {
            $settings = new JFTSettings(JFTLanguage::Spanish);
        } elseif ($wordLanguage == 'fr-FR' || $wordLanguage == 'fr-CA') {
            $settings = new JFTSettings(JFTLanguage::French);
        }

        $jft = JFT::getInstance($settings);
        $data = $jft->fetch();
        $parts = collect([
            $data->date,
            $data->title,
            $data->page,
            $data->quote,
            $data->source,
            ...collect($data->content)->map(fn($p) => strip_tags(html_entity_decode($p))),
            $data->thought,
            $data->copyright
        ])
            ->filter(fn($part) => !empty(trim($part)))
            ->values();
        $parts->join('br /br /');

        $message = $parts->toArray();
        if ($sms) {
            if (count($message) > 1) {
                for ($i = 0; $i < count($message); $i++) {
                    $jft_message = "(" .($i + 1). " of " .count($message). ")\n" .$message[$i];
                    $finalMessage[] = $jft_message;
                }
            } else {
                $finalMessage[] = $message;
            }
        }

        return $message;
    }
}
