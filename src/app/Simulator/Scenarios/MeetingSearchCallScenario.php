<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * Caller presses 2 for meetings, 2 to search by zip code, keys in the zip, and
 * hears meeting results read back from the real BMLT root server; the same
 * results go out by SMS through the fake Twilio client.
 */
class MeetingSearchCallScenario implements Scenario
{
    public function name(): string
    {
        return 'meeting-search';
    }

    public function description(): string
    {
        return 'Call: meeting search by zip code (IVR → BMLT → results read back and texted)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $mark = $simulator->lastOutboundId();

        $call = $simulator->startCall($options->from, $options->to, $options->locationOverrides())
            ->press('2')   // digit_map_search_type: meetings
            ->press('2')   // digit_map_location_search_method: zip code
            ->press($options->location)
            ->expectHeard('searching meeting information for ' . $options->location);

        foreach ($call->transcript() as $said) {
            if (stripos($said, 'no results found') !== false) {
                throw new SimulationFailed(
                    "The meeting search found no meetings near {$options->latitude},{$options->longitude}. "
                    . "Pick a --location/--latitude/--longitude the instance's BMLT root server has meetings near."
                );
            }
        }

        $texts = array_filter(
            $simulator->outbound('messages.create', $mark),
            fn (array $request) => ($request['data']['To'] ?? null) === $options->from,
        );
        if ($texts === []) {
            throw new SimulationFailed("Expected the meeting results to be texted to {$options->from}, but no SMS was sent.");
        }

        $call->hangUp();

        return [
            'IVR reached meeting results for ' . $options->location,
            sprintf('%d meeting result SMS sent to %s', count($texts), $options->from),
        ];
    }
}
