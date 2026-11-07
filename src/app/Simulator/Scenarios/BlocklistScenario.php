<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * A caller on the blocklist is rejected before hearing anything; anyone else
 * calling the same helpline still reaches the menu.
 */
class BlocklistScenario extends BaseScenario
{
    private const BLOCKED = '+15555551000';

    public function name(): string
    {
        return 'blocklist';
    }

    public function description(): string
    {
        return 'Call: a blocklisted caller is rejected; other callers reach the menu';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $blocklist = ['override_blocklist' => self::BLOCKED . ',+15555551001'];

        $blocked = $this->call($simulator, $options, $blocklist, self::BLOCKED);
        if (!$blocked->twiml()->has('Reject') || !$blocked->isOver() || $blocked->transcript() !== []) {
            throw new SimulationFailed('Expected blocklisted ' . self::BLOCKED . " to be rejected without hearing anything:\n" . $blocked->twiml()->raw);
        }

        $allowed = $this->call($simulator, $options, $blocklist)->expectHeard('press two to search for meetings');
        $allowed->hangUp();

        return [
            self::BLOCKED . ' rejected (<Reject/>) before any prompt',
            "{$options->from} not on the blocklist → reached the menu",
        ];
    }
}
