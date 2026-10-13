<?php

namespace App\Simulator\Scenarios;

class ScenarioRegistry
{
    /** @return array<string, Scenario> */
    public static function all(): array
    {
        $scenarios = [];
        foreach ([
            new MeetingSearchCallScenario(),
            new HelplineVolunteerScenario(),
            new SmsMeetingSearchScenario(),
        ] as $scenario) {
            $scenarios[$scenario->name()] = $scenario;
        }

        return $scenarios;
    }
}
