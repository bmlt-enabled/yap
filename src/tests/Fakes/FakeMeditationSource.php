<?php

namespace Tests\Fakes;

use App\Services\MeditationSource;
use FetchMeditation\EnglishJFT;
use FetchMeditation\EnglishSPAD;
use FetchMeditation\JFT;
use FetchMeditation\JFTLanguage;
use FetchMeditation\JFTSettings;
use FetchMeditation\SPAD;
use FetchMeditation\SPADLanguage;
use FetchMeditation\SPADSettings;
use RuntimeException;
use Tests\FakeHttp;

/**
 * Serves the daily readings from recorded pages instead of na.org.
 *
 * Only the transport is faked. The returned fetchers are fetch-meditation's own
 * EnglishJFT/EnglishSPAD with fetchFromUrl() overridden, so the library's
 * source fallback and HTML parsing, and all of ReadingService, still run.
 *
 * The other languages' fetchers call HttpUtility statically and have no such
 * seam, so every language is served through the English parser. The language
 * ReadingService asked for is recorded in $requested, which is how tests check
 * the word_language mapping.
 *
 * tests/Pest.php binds one of these for every Feature test, so a test that
 * reaches a reading can never fetch the live feed.
 */
class FakeMeditationSource extends MeditationSource
{
    /**
     * URL => recorded body (string) or HTTP status (int) to fail with.
     *
     * @var array<string, string|int>
     */
    public array $responses;

    /** @var list<JFTLanguage|SPADLanguage> */
    public array $requested = [];

    /** @var list<string> */
    public array $fetchedUrls = [];

    public function __construct()
    {
        $jft = FakeHttp::fixture('readings/jft-en.html');
        $spad = FakeHttp::fixture('readings/spad-en.html');

        // EnglishJFT/EnglishSPAD try these in order and fall back to the second.
        $this->responses = [
            'https://jft.na.org' => $jft,
            'https://na.org/jftna/' => $jft,
            'https://spad.na.org' => $spad,
            'https://na.org/spadna/' => $spad,
        ];
    }

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

        return new class (new JFTSettings(JFTLanguage::English), $this) extends EnglishJFT {
            public function __construct(JFTSettings $settings, private FakeMeditationSource $source)
            {
                parent::__construct($settings);
            }

            protected function fetchFromUrl(string $url, array $params = []): string
            {
                return $this->source->body($url);
            }
        };
    }

    public function spad(SPADSettings $settings): SPAD
    {
        $this->requested[] = $settings->language;

        return new class (new SPADSettings(SPADLanguage::English), $this) extends EnglishSPAD {
            public function __construct(SPADSettings $settings, private FakeMeditationSource $source)
            {
                parent::__construct($settings);
            }

            protected function fetchFromUrl(string $url, array $params = []): string
            {
                return $this->source->body($url);
            }
        };
    }

    /**
     * What HttpUtility::httpGet() would have returned or thrown for $url.
     */
    public function body(string $url): string
    {
        $this->fetchedUrls[] = $url;

        if (!array_key_exists($url, $this->responses)) {
            throw new RuntimeException("FakeMeditationSource has no response for {$url}");
        }

        $response = $this->responses[$url];
        if (is_int($response)) {
            // The message HttpUtility::guzzleGet() throws for a non-2xx/3xx.
            throw new \Exception('Received non-acceptable status code: ' . $response);
        }

        return $response;
    }
}
