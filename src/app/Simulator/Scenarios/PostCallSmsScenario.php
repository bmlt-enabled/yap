<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * With sms_ask on, meeting results are only texted if the caller asks for them
 * after hearing them, at post-call-action:
 *
 * - press 1: the results are texted, one SMS each, and the call ends;
 * - with sms_combine and infinite_searching too, press 3 ("do both"): one
 *   combined SMS, and the caller is taken back to the start to search again.
 */
class PostCallSmsScenario extends BaseScenario
{
    public function name(): string
    {
        return 'post-call-sms';
    }

    public function description(): string
    {
        return 'Call: results texted only when the caller asks afterwards (sms_ask, post-call-action)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        [$call, $mark] = $this->results($simulator, $options, ['override_sms_ask' => 'true']);
        $call->press('1')->expectHeard('thank you for calling');
        $texts = $simulator->texts($mark, $options->from);
        if ($texts === [] || !$call->isOver()) {
            throw new SimulationFailed('Expected pressing 1 to text the results and end the call; ' . count($texts) . ' SMS sent.');
        }
        $call->hangUp();

        [$call, $mark] = $this->results($simulator, $options, [
            'override_sms_ask' => 'true',
            'override_sms_combine' => 'true',
            'override_infinite_searching' => 'true',
        ]);
        $call->expectHeard('press three if you would like to do both');
        $since = count($call->transcript());
        $call->press('3');
        $combined = $simulator->texts($mark, $options->from);
        if (count($combined) !== 1) {
            throw new SimulationFailed('Expected pressing 3 with sms_combine to send one combined SMS, ' . count($combined) . ' were sent.');
        }
        $call->expectHeard('press two to search for meetings', $since);
        $call->hangUp();

        return [
            'sms_ask: nothing texted until asked; press 1 → ' . count($texts) . ' result SMS, then goodbye',
            'sms_combine + infinite_searching: press 3 → one combined SMS, then back to the main menu',
        ];
    }

    /**
     * Search by zip code up to the post-call prompt, checking nothing has been
     * texted yet.
     *
     * @return array{0: SimulatedCall, 1: int} the call, and the request id to look for SMS after
     */
    private function results(TwilioSimulator $simulator, ScenarioOptions $options, array $query): array
    {
        $mark = $simulator->lastOutboundId();
        $call = $this->call($simulator, $options, $query)
            ->press('2')   // meetings
            ->press('2')   // zip code
            ->press($options->location)
            ->expectHeard('press one if you would like these results to be texted to you');

        if ($simulator->texts($mark, $options->from) !== []) {
            throw new SimulationFailed('With sms_ask on, results were texted before the caller asked for them.');
        }

        return [$call, $mark];
    }
}
