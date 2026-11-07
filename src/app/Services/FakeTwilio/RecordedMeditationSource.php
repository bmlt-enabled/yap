<?php

namespace App\Services\FakeTwilio;

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

/**
 * Serves the daily readings from recorded pages instead of na.org.
 *
 * Only the transport is replaced. The returned fetchers are fetch-meditation's
 * own EnglishJFT/EnglishSPAD with fetchFromUrl() overridden, so the library's
 * source fallback and HTML parsing, and all of ReadingService, still run.
 *
 * The other languages' fetchers call HttpUtility statically and have no such
 * seam, so every language is served through the English parser.
 *
 * Fake Twilio mode binds this (see FakeTwilioMode) so a simulated call that asks
 * for the JFT or SPAD hears a known page and never depends on na.org being up.
 * The Pest suite's Tests\Fakes\FakeMeditationSource extends it.
 */
class RecordedMeditationSource extends MeditationSource
{
    public const READINGS_DIR = 'tests/Fixtures/http/readings';

    /**
     * URL => recorded body (string) or HTTP status (int) to fail with.
     *
     * @var array<string, string|int>
     */
    public array $responses;

    /** @var list<string> */
    public array $fetchedUrls = [];

    public function __construct(?string $directory = null)
    {
        $directory ??= base_path(self::READINGS_DIR);
        $jft = self::read("{$directory}/jft-en.html");
        $spad = self::read("{$directory}/spad-en.html");

        // EnglishJFT/EnglishSPAD try these in order and fall back to the second.
        $this->responses = [
            'https://jft.na.org' => $jft,
            'https://na.org/jftna/' => $jft,
            'https://spad.na.org' => $spad,
            'https://na.org/spadna/' => $spad,
        ];
    }

    /** Whether the recorded pages are present (they are not in the release zip). */
    public static function available(?string $directory = null): bool
    {
        $directory ??= base_path(self::READINGS_DIR);

        return is_file("{$directory}/jft-en.html") && is_file("{$directory}/spad-en.html");
    }

    public function jft(JFTSettings $settings): JFT
    {
        return new class (new JFTSettings(JFTLanguage::English), $this) extends EnglishJFT {
            public function __construct(JFTSettings $settings, private RecordedMeditationSource $source)
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
        return new class (new SPADSettings(SPADLanguage::English), $this) extends EnglishSPAD {
            public function __construct(SPADSettings $settings, private RecordedMeditationSource $source)
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
            throw new RuntimeException(static::class . " has no response for {$url}");
        }

        $response = $this->responses[$url];
        if (is_int($response)) {
            // The message HttpUtility::guzzleGet() throws for a non-2xx/3xx.
            throw new \Exception('Received non-acceptable status code: ' . $response);
        }

        return $response;
    }

    private static function read(string $path): string
    {
        if (!is_file($path)) {
            throw new RuntimeException("Missing recorded reading: {$path}");
        }

        return file_get_contents($path);
    }
}
