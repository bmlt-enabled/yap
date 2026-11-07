<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\TwilioSimulator;

/**
 * Meeting search by voice instead of zip code, three ways:
 *
 * 1. city-or-county-voice-input: say the city; the province comes from
 *    toll_province_bias.
 * 2. province-voice-input: with province_lookup on, say the province first.
 * 3. province-lookup-list-response: with a province_lookup_list, press the
 *    province's number instead.
 *
 * Each spoken answer is posted as a SpeechResult, and voice-input-result hands
 * "<city>, <province>" to address-lookup, which must geocode it and search.
 */
class VoiceLocationSearchScenario extends BaseScenario
{
    private const CITY = 'Geneva';

    public function name(): string
    {
        return 'voice-location-search';
    }

    public function description(): string
    {
        return 'Call: meeting search by spoken city, spoken province, and province list (speech <Gather>s)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $city = self::CITY . ', NY';
        $this->byVoice($simulator, $options, $city, ['override_toll_province_bias' => 'NY'])
            ->speak(self::CITY)
            ->expectHeard('searching meeting information for ' . $this->heardAs($options, $city))
            ->expectHeard('starts at')
            ->hangUp();

        $city = self::CITY . ', New York';
        $this->byVoice($simulator, $options, $city, ['override_province_lookup' => 'true'])
            ->expectHeard('state or province')
            ->speak('New York')
            ->speak(self::CITY)
            ->expectHeard('searching meeting information for ' . $this->heardAs($options, $city))
            ->expectHeard('starts at')
            ->hangUp();

        $this->byVoice($simulator, $options, $city, [
            'override_province_lookup' => 'true',
            'override_province_lookup_list' => ['Ontario', 'New York'],
        ])
            ->expectHeard('for Ontario press one')
            ->expectHeard('for New York press two')
            ->press('2')
            ->speak(self::CITY)
            ->expectHeard('searching meeting information for ' . $this->heardAs($options, $city))
            ->expectHeard('starts at')
            ->hangUp();

        return [
            'spoken city + toll_province_bias → address-lookup "Geneva, NY" → meeting results',
            'spoken province, then city → "Geneva, New York" → meeting results',
            'province list (press 2 for New York), then spoken city → meeting results',
        ];
    }

    /** Call, press 2 for meetings and 1 for "by city or county", up to the first speech prompt. */
    private function byVoice(TwilioSimulator $simulator, ScenarioOptions $options, string $location, array $query): SimulatedCall
    {
        return $simulator->startCall($options->from, $options->to, array_merge($options->locationOverrides($location), $query))
            ->press('2')   // digit_map_search_type: meetings
            ->press('1');  // digit_map_location_search_method: city or county, by voice
    }

    /** Google spells out the place it found, so with --real-geocoding only the city is predictable. */
    private function heardAs(ScenarioOptions $options, string $location): string
    {
        return $options->realGeocoding ? self::CITY : $location;
    }
}
