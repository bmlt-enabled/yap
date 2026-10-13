<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * A texter sends their zip code to the SMS gateway and gets meeting results
 * back as outbound SMS, captured by the fake Twilio client.
 */
class SmsMeetingSearchScenario implements Scenario
{
    public function name(): string
    {
        return 'sms-meeting-search';
    }

    public function description(): string
    {
        return 'SMS: texting a zip code to the gateway returns meeting results by SMS';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $mark = $simulator->lastOutboundId();

        $simulator->sendSms($options->from, $options->to, $options->location, $options->locationOverrides());

        $texts = array_values(array_filter(
            $simulator->outbound('messages.create', $mark),
            fn (array $request) => ($request['data']['To'] ?? null) === $options->from,
        ));
        if ($texts === []) {
            throw new SimulationFailed("Expected meeting results texted back to {$options->from}, but no SMS was sent.");
        }

        foreach ($texts as $text) {
            $simulator->log('  ✉ ' . str_replace("\n", ' / ', (string) ($text['data']['Body'] ?? '')));
        }

        return [sprintf('%d meeting result SMS sent to %s from %s', count($texts), $options->from, $texts[0]['data']['From'] ?? '?')];
    }
}
