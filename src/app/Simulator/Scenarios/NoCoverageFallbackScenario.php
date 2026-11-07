<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * A caller asks for someone to talk to from a zip code no service body covers.
 * With fallback_number set, helpline-search dials it; without, the caller is
 * told there is no coverage and asked to try again.
 *
 * The location is fixed rather than --location: Brooklyn, NC (Wilmington),
 * where the test root server has no helpline coverage.
 */
class NoCoverageFallbackScenario extends BaseScenario
{
    private const ZIP = '28401';

    private const LATITUDE = '34.247433';

    private const LONGITUDE = '-77.9404247';

    private const FALLBACK = '+15555551100';

    public function name(): string
    {
        return 'no-coverage-fallback';
    }

    public function description(): string
    {
        return 'Call: helpline search with no coverage dials fallback_number, or asks to retry without one';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $call = $this->search($simulator, $options, ['override_fallback_number' => self::FALLBACK]);
        if (!$call->isWaitingOn('Dial') || $call->dialedNumbers() !== [self::FALLBACK]) {
            throw new SimulationFailed(sprintf(
                "Expected a helpline search from %s (no coverage) to dial fallback_number %s:\n%s",
                self::ZIP,
                self::FALLBACK,
                $call->twiml()->raw,
            ));
        }
        $call->hangUp();

        $call = $this->search($simulator, $options, []);
        $call->expectHeard("couldn't find service body coverage for that location");
        if ($call->isWaitingOn('Dial')) {
            throw new SimulationFailed("Without fallback_number, a search with no coverage dialed someone:\n" . $call->twiml()->raw);
        }
        $call->hangUp();

        return [
            self::ZIP . ' has no coverage → dialed fallback_number ' . self::FALLBACK,
            'without fallback_number → told there is no coverage and asked to retry',
        ];
    }

    private function search(TwilioSimulator $simulator, ScenarioOptions $options, array $query): SimulatedCall
    {
        return $simulator->startCall($options->from, $options->to, array_merge([
            'override_custom_geocoding' => [['location' => self::ZIP, 'latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]],
            'override_timezone_default' => ['timeZoneId' => 'America/New_York'],
        ], $query))
            ->press('1')   // someone to talk to
            ->press('2')   // by zip code
            ->press(self::ZIP);
    }
}
