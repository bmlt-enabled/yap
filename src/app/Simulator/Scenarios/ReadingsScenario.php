<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * With jft_option and spad_option on, the menu offers the Just for Today
 * (press 3) and the Spiritual Principle a Day (press 4); the location menu of a
 * meeting search offers the JFT too. fetch-jft / fetch-spad read the reading and
 * the call ends.
 *
 * In fake Twilio mode the readings come from the pages recorded in the
 * repository (RecordedMeditationSource), so this never depends on na.org.
 *
 * yap has no SMS path for the readings: the gateway treats any text without the
 * helpline keyword as a meeting search.
 */
class ReadingsScenario extends BaseScenario
{
    private const COPYRIGHT = 'NA World Services, Inc. All Rights Reserved';

    public function name(): string
    {
        return 'readings';
    }

    public function description(): string
    {
        return 'Call: JFT and SPAD readings (fetch-jft, fetch-spad) from the main and location menus';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $readings = ['override_jft_option' => 'true', 'override_spad_option' => 'true'];

        $call = $this->call($simulator, $options, $readings)
            ->expectHeard('press three to listen to the just for today')
            ->expectHeard('press four to listen to the spiritual principle a day');
        $jft = $this->expectReading($call, '3', 'Just for Today');

        $spad = $this->expectReading($this->call($simulator, $options, $readings), '4', 'Spiritual Principle a Day');

        $call = $this->call($simulator, $options, $readings)
            ->press('2')   // meetings
            ->expectHeard('press three to listen to the just for today');
        $this->expectReading($call, '3', 'Just for Today, from the location menu');

        return [
            "main menu 3 → Just for Today read ({$jft} lines)",
            "main menu 4 → Spiritual Principle a Day read ({$spad} lines)",
            'meeting search location menu 3 → Just for Today read',
        ];
    }

    /** Press $digit and expect the whole reading, then the end of the call. Returns how many lines were read. */
    private function expectReading(SimulatedCall $call, string $digit, string $reading): int
    {
        $since = count($call->transcript());
        $heard = array_slice($call->press($digit)->transcript(), $since);

        if (!$call->isOver() || count($heard) < 5 || stripos((string) end($heard), self::COPYRIGHT) === false) {
            throw new SimulationFailed(sprintf(
                "Expected the %s to be read through to its copyright line, then the call to end. After pressing %s the caller heard:\n  %s",
                $reading,
                $digit,
                implode("\n  ", $heard) ?: '(nothing)',
            ));
        }

        return count($heard);
    }
}
