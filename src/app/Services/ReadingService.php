<?php

namespace App\Services;

use App\Constants\ReadingType;
use App\Utilities\Sms;
use Illuminate\Support\Facades\App;
use FetchMeditation\JFTSettings;
use FetchMeditation\JFTLanguage;
use FetchMeditation\JFT;
use FetchMeditation\SPADLanguage;
use FetchMeditation\SPADSettings;
use FetchMeditation\SPAD;

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
        return match($reading) {
            ReadingType::JFT => $this->getJFT($sms),
            ReadingType::SPAD => $this->getSPAD($sms),
            default => throw new \InvalidArgumentException("Invalid reading type: $reading")
        };
    }

    protected function getJFT($sms = false): array
    {
        $wordLanguage = $this->settings->get('word_language');
        $settings = match($wordLanguage) {
            'en-US', 'en-AU' => new JFTSettings(JFTLanguage::English),
            'pt-BR', 'pt-PT' => new JFTSettings(JFTLanguage::Portuguese),
            'es-ES', 'es-US' => new JFTSettings(JFTLanguage::Spanish),
            'fr-FR', 'fr-CA' => new JFTSettings(JFTLanguage::French),
            default => throw new \InvalidArgumentException("Unsupported language: $wordLanguage")
        };

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

        return $parts->toArray();
    }

    protected function getSPAD($sms = false): array
    {
        $wordLanguage = $this->settings->get('word_language');
        $settings = match($wordLanguage) {
            'en-US', 'en-AU' => new SPADSettings(SPADLanguage::English),
            'pt-BR', 'pt-PT' => new SPADSettings(SPADLanguage::English),
            'es-ES', 'es-US' => new SPADSettings(SPADLanguage::English),
            'fr-FR', 'fr-CA' => new SPADSettings(SPADLanguage::English),
            default => throw new \InvalidArgumentException("Unsupported language: $wordLanguage")
        };

        $spad = SPAD::getInstance($settings);
        $data = $spad->fetch();
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

        return $parts->toArray();
    }
}
