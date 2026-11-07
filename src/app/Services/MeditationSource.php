<?php

namespace App\Services;

use FetchMeditation\JFT;
use FetchMeditation\JFTSettings;
use FetchMeditation\SPAD;
use FetchMeditation\SPADSettings;

/**
 * Where ReadingService gets its daily-reading fetchers from.
 *
 * bmlt/fetch-meditation builds its own Guzzle client, so the Http facade (and
 * Http::preventStrayRequests()) never sees its requests. Resolving the fetchers
 * through this class gives the test suite one seam to swap for a fixture-backed
 * fake (Tests\Fakes\FakeMeditationSource) without mocking ReadingService itself.
 */
class MeditationSource
{
    public function jft(JFTSettings $settings): JFT
    {
        return JFT::getInstance($settings);
    }

    public function spad(SPADSettings $settings): SPAD
    {
        return SPAD::getInstance($settings);
    }
}
