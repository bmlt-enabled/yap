<?php

namespace Tests\Fakes;

use App\Services\FakeTwilio\RecordedMeditationSource;
use FetchMeditation\JFT;
use FetchMeditation\JFTLanguage;
use FetchMeditation\JFTSettings;
use FetchMeditation\SPAD;
use FetchMeditation\SPADLanguage;
use FetchMeditation\SPADSettings;

/**
 * Serves the daily readings from the recorded pages in Fixtures/http/readings,
 * with knobs for tests: fail a source, or check which edition was asked for.
 *
 * The recording and parsing live in RecordedMeditationSource, which fake Twilio
 * mode also uses. The language ReadingService asked for is recorded in
 * $requested, which is how tests check the word_language mapping.
 *
 * tests/Pest.php binds one of these for every Feature test, so a test that
 * reaches a reading can never fetch the live feed.
 */
class FakeMeditationSource extends RecordedMeditationSource
{
    /** @var list<JFTLanguage|SPADLanguage> */
    public array $requested = [];

    /**
     * Make every source for a reading answer with $response.
     */
    public function failAll(string $reading, string|int $response): self
    {
        foreach ($this->sourcesFor($reading) as $url) {
            $this->responses[$url] = $response;
        }

        return $this;
    }

    public function respond(string $url, string|int $response): self
    {
        $this->responses[$url] = $response;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function sourcesFor(string $reading): array
    {
        return match ($reading) {
            'jft' => ['https://jft.na.org', 'https://na.org/jftna/'],
            'spad' => ['https://spad.na.org', 'https://na.org/spadna/'],
        };
    }

    public function jft(JFTSettings $settings): JFT
    {
        $this->requested[] = $settings->language;

        return parent::jft($settings);
    }

    public function spad(SPADSettings $settings): SPAD
    {
        $this->requested[] = $settings->language;

        return parent::spad($settings);
    }
}
