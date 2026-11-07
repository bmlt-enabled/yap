<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * A volunteer misses a helpline call and calls the caller back with the
 * dialback PIN yap texted them (sms_dialback_options=1). From the helpline's
 * menu they press 9, enter the PIN, and dialback-dialer must dial the original
 * caller from the helpline's number. A wrong PIN must not dial anyone.
 */
class DialbackScenario extends BaseScenario
{
    use DrivesHelplineCalls;

    private const VOLUNTEER = '+15555550601';

    protected bool $needsSeed = true;

    protected bool $needsServiceBody = true;

    public function name(): string
    {
        return 'dialback';
    }

    public function description(): string
    {
        return 'Call: volunteer dials back a missed caller with the PIN from their SMS notification (dialback, dialback-dialer)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $this->requireOptions($options);
        ScenarioSeeder::helpline($this->serviceBodyId($options), ['volunteer_sms_notification' => 'send_sms'], [
            ScenarioSeeder::volunteer('Dialback Volunteer', self::VOLUNTEER),
        ]);

        $mark = $simulator->lastOutboundId();
        $caller = $this->callHelpline($simulator, $options, ['override_sms_dialback_options' => 1]);
        $this->volunteerLegs($simulator, $mark);
        $notification = (string) $this->onlyText($simulator, $mark, self::VOLUNTEER, 'volunteer notification')['data']['Body'];
        if (!preg_match('/PIN:\s*(\d+)/', $notification, $m)) {
            throw new SimulationFailed("Expected the volunteer's SMS notification to carry a dialback PIN:\n  {$notification}");
        }
        $pin = $m[1];
        $caller->hangUp(); // the call is recorded once it ends, which is what the PIN looks up

        $dialback = $simulator->startCall(self::VOLUNTEER, $options->to)
            ->press('9')   // digit_map_search_type: dialback
            ->expectHeard('please enter the dialback pin')
            ->press($pin)
            ->expectHeard('please wait while we connect your call');
        $dial = $dialback->pending('Dial');
        if ($dialback->dialedNumbers() !== [$options->from] || !TwilioSimulator::sameNumber((string) $dial->attr('callerId'), $options->to)) {
            throw new SimulationFailed(sprintf(
                "Expected PIN %s to dial the original caller %s from the helpline %s:\n%s",
                $pin,
                $options->from,
                $options->to,
                $dialback->twiml()->raw,
            ));
        }
        $dialback->hangUp();

        $wrong = $simulator->startCall(self::VOLUNTEER, $options->to)->press('9');
        $since = count($wrong->transcript());
        $wrong->press(str_pad((string) (((int) $pin + 1) % 10000000), 7, '0', STR_PAD_LEFT))
            ->expectHeard('invalid pin entry', $since);
        if ($wrong->isWaitingOn('Dial')) {
            throw new SimulationFailed("A wrong dialback PIN dialed someone:\n" . $wrong->twiml()->raw);
        }
        $wrong->hangUp();

        return [
            'volunteer ' . self::VOLUNTEER . " texted dialback PIN {$pin} when yap dialed them",
            "9 + PIN {$pin} → dialed the original caller {$options->from} from {$options->to}",
            'a wrong PIN → "Invalid pin entry", nothing dialed',
        ];
    }
}
