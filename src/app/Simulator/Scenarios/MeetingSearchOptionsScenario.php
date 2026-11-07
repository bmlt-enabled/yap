<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * Zip code search through address-lookup with the two settings that shape the
 * results:
 *
 * - meeting_result_sort: yap searches "today" and, when that is short of
 *   result_count_max, "tomorrow", then starts the list at the sort day. By
 *   default that is today; set to tomorrow's weekday, tomorrow's meetings must
 *   come first.
 * - meeting_search_radius: becomes the BMLT geo_width. A radius smaller than the
 *   distance to the nearest meeting must find nothing.
 *
 * Every webhook carries Timestamp, which pins "today" to a Tuesday and turns
 * off the time-of-day filter, so the run does not depend on the clock.
 */
class MeetingSearchOptionsScenario extends BaseScenario
{
    /** A Tuesday. yap searches its weekday (3) and the next (4, Wednesday). */
    private const TIMESTAMP = '2024-02-20 12:00:00';

    private const TODAY = 'Tuesday';

    private const TOMORROW = 'Wednesday';

    private const TOMORROW_WEEKDAY = 4;

    /** Miles. The scenario location's nearest meeting must be farther than this. */
    private const SMALL_RADIUS = 5;

    private const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    public function name(): string
    {
        return 'meeting-search-options';
    }

    public function description(): string
    {
        return 'Call: address-lookup results honor meeting_result_sort and meeting_search_radius';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        // Enough results that yap also searches tomorrow, and the caller hears both days.
        $both = ['override_result_count_max' => 100];

        $days = $this->daysHeard($this->search($simulator, $options, $both));
        $this->expectOrder($days, self::TODAY, self::TOMORROW, 'the default sort (today first)');

        $days = $this->daysHeard($this->search($simulator, $options, $both + [
            'override_meeting_result_sort' => self::TOMORROW_WEEKDAY,
        ]));
        $this->expectOrder($days, self::TOMORROW, self::TODAY, 'meeting_result_sort=' . self::TOMORROW_WEEKDAY);

        $call = $this->search($simulator, $options, ['override_meeting_search_radius' => self::SMALL_RADIUS]);
        if (!$call->heard('no results found')) {
            throw new SimulationFailed(sprintf(
                "Expected no meetings within meeting_search_radius=%d miles of %s,%s, but the caller heard:\n  %s",
                self::SMALL_RADIUS,
                $options->latitude,
                $options->longitude,
                implode("\n  ", $call->transcript()),
            ));
        }

        return [
            sprintf('address-lookup found %s; default sort read %s meetings before %s', $options->location, self::TODAY, self::TOMORROW),
            sprintf('meeting_result_sort=%d read %s meetings first', self::TOMORROW_WEEKDAY, self::TOMORROW),
            sprintf('meeting_search_radius=%d found no meetings', self::SMALL_RADIUS),
        ];
    }

    private function search(TwilioSimulator $simulator, ScenarioOptions $options, array $query): SimulatedCall
    {
        $call = $simulator->startCall(
            $options->from,
            $options->to,
            array_merge($options->locationOverrides(), $query),
            everyWebhook: ['Timestamp' => self::TIMESTAMP],
        )
            ->press('2')   // meetings
            ->press('2')   // zip code
            ->press($options->location)
            ->expectHeard('searching meeting information for ' . $options->location);

        return $call->hangUp();
    }

    /** @return list<string> the weekday of each result read, in order */
    private function daysHeard(SimulatedCall $call): array
    {
        $days = [];
        foreach ($call->transcript() as $said) {
            if (preg_match('/^starts at (' . implode('|', self::DAYS) . ')\b/i', $said, $m)) {
                $days[] = ucfirst(strtolower($m[1]));
            }
        }

        return $days;
    }

    /** @param list<string> $days */
    private function expectOrder(array $days, string $first, string $then, string $what): void
    {
        $blocks = array_values(array_unique($days));
        if ($blocks !== [$first, $then]) {
            throw new SimulationFailed(sprintf(
                'Expected %s to read all %s meetings, then all %s meetings, but the results were in this order: %s',
                $what,
                $first,
                $then,
                implode(', ', $days) ?: '(no results)',
            ));
        }
    }
}
