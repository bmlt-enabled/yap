<?php

namespace App\Simulator\Scenarios;

class ScenarioRegistry
{
    /** @return array<string, Scenario> */
    public static function all(): array
    {
        $scenarios = [];
        foreach ([
            // Meeting search
            new MeetingSearchCallScenario(),
            new SmsMeetingSearchScenario(),
            new MeetingSearchOptionsScenario(),
            new VoiceLocationSearchScenario(),
            new LanguageSelectorScenario(),
            new PostCallSmsScenario(),
            new ReadingsScenario(),
            // Menu routing
            new CustomExtensionsScenario(),
            new BlocklistScenario(),
            new NoCoverageFallbackScenario(),
            // Helpline (these configure --service-body with --seed)
            new HelplineVolunteerScenario(),
            new GenderRoutingScenario(),
            new CallBlastingScenario(),
            new CallerHangupScenario(),
            new VoicemailNotificationsScenario(),
            new DialbackScenario(),
            new SmsHelplineScenario(),
        ] as $scenario) {
            $scenarios[$scenario->name()] = $scenario;
        }

        return $scenarios;
    }
}
