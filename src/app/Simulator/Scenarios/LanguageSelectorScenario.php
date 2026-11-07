<?php

namespace App\Simulator\Scenarios;

use App\Simulator\TwilioSimulator;

/**
 * With language_selections set, the call starts at lng-selector, which offers
 * each language in that language. The caller picks Spanish and everything after
 * that, the menu and the meeting results, is in Spanish.
 */
class LanguageSelectorScenario extends BaseScenario
{
    public function name(): string
    {
        return 'language-selector';
    }

    public function description(): string
    {
        return 'Call: language selector (lng-selector) → press 2 for Spanish → Spanish menu and meeting results';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $call = $this->call($simulator, $options, ['override_language_selections' => 'en-US,es-US'])
            ->expectHeard('for english press one')
            ->expectHeard('para español presione dos');

        $since = count($call->transcript());
        $call->press('2')
            ->expectHeard('presione dos buscar reuniones', $since)
            ->expectNotHeard('press two to search for meetings', $since);

        $call->press('2')   // reuniones
            ->press('2')    // código postal
            ->press($options->location)
            ->expectHeard('empieza en', $since);
        $call->hangUp();

        return [
            'lng-selector offered English and Spanish, each in its own language',
            'after pressing 2 the menu was in Spanish',
            'meeting results were read in Spanish',
        ];
    }
}
