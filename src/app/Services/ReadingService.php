<?php

namespace App\Services;

use App\Constants\ReadingType;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use FetchMeditation\JFTEntry;
use FetchMeditation\JFTSettings;
use FetchMeditation\JFTLanguage;
use FetchMeditation\SPADEntry;
use FetchMeditation\SPADLanguage;
use FetchMeditation\SPADSettings;

class ReadingService extends Service
{
    protected HttpService $http;
    protected MeditationSource $source;

    public function __construct(HttpService $http, MeditationSource $source)
    {
        parent::__construct(App::make(SettingsService::class));
        $this->http = $http;
        $this->source = $source;
    }

    public function get($reading = ReadingType::JFT, $sms = false): array
    {
        return match ($reading) {
            ReadingType::JFT => $this->getJFT($sms),
            ReadingType::SPAD => $this->getSPAD($sms),
            default => throw new \InvalidArgumentException("Invalid reading type: $reading")
        };
    }

    protected function getJFT($sms = false): array
    {
        $wordLanguage = $this->settings->get('word_language');
        $settings = match ($wordLanguage) {
            'en-US', 'en-AU' => new JFTSettings(JFTLanguage::English),
            'pt-BR', 'pt-PT' => new JFTSettings(JFTLanguage::Portuguese),
            'es-ES', 'es-US' => new JFTSettings(JFTLanguage::Spanish),
            'fr-FR', 'fr-CA' => new JFTSettings(JFTLanguage::French),
            default => throw new \InvalidArgumentException("Unsupported language: $wordLanguage")
        };

        return $this->toParts('JFT', $this->source->jft($settings)->fetch());
    }

    protected function getSPAD($sms = false): array
    {
        $wordLanguage = $this->settings->get('word_language');
        $settings = match ($wordLanguage) {
            'en-US', 'en-AU' => new SPADSettings(SPADLanguage::English),
            'pt-BR', 'pt-PT' => new SPADSettings(SPADLanguage::English),
            'es-ES', 'es-US' => new SPADSettings(SPADLanguage::English),
            'fr-FR', 'fr-CA' => new SPADSettings(SPADLanguage::English),
            default => throw new \InvalidArgumentException("Unsupported language: $wordLanguage")
        };

        return $this->toParts('SPAD', $this->source->spad($settings)->fetch());
    }

    /**
     * Flatten a reading into the lines that get spoken.
     *
     * When every source fails, the English fetchers return a JSON error string
     * instead of an entry. Reading properties off that string used to throw, so
     * a feed outage became a 500 mid-call. Return no lines instead and
     * let the caller decide what an empty reading sounds like.
     */
    private function toParts(string $reading, mixed $data): array
    {
        if (!$data instanceof JFTEntry && !$data instanceof SPADEntry) {
            Log::warning("Could not fetch the {$reading} reading", [
                'response' => is_string($data) ? $data : get_debug_type($data),
            ]);
            return [];
        }

        return collect([
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
            ->values()
            ->toArray();
    }
}
