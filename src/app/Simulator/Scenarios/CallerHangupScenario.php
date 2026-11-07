<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * The caller gives up while yap is still getting a volunteer on the line
 * (#1644). Whether the volunteer had already answered and was about to press 1,
 * or had not picked up yet, they must hear that the caller hung up and be hung
 * up on, not be dropped into an empty conference.
 */
class CallerHangupScenario extends BaseScenario
{
    use DrivesHelplineCalls;

    private const VOLUNTEER = '+15555551200';

    protected bool $needsSeed = true;

    protected bool $needsServiceBody = true;

    public function name(): string
    {
        return 'caller-hangup-before-accept';
    }

    public function description(): string
    {
        return 'Call: caller hangs up before the volunteer presses 1, or before they answer (#1644)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $this->requireOptions($options);
        ScenarioSeeder::helpline($this->serviceBodyId($options), [], [
            ScenarioSeeder::volunteer('Simulated Volunteer', self::VOLUNTEER),
        ]);

        $mark = $simulator->lastOutboundId();
        $caller = $this->callHelpline($simulator, $options);
        $leg = $this->volunteerLegs($simulator, $mark)[0];
        $leg->answer()->expectHeard('you have a call from the helpline');
        $caller->hangUp();
        $since = count($leg->transcript());
        $leg->press('1')->expectHeard('the caller hung up', $since);
        if (!$leg->isOver()) {
            throw new SimulationFailed("Expected yap to hang up on the volunteer once the caller was gone:\n" . $leg->twiml()->raw);
        }

        $mark = $simulator->lastOutboundId();
        $caller = $this->callHelpline($simulator, $options);
        $leg = $this->volunteerLegs($simulator, $mark)[0];
        $caller->hangUp();
        $leg->answer()->expectHeard('the caller hung up')->expectNotHeard('you have a call from the helpline');
        if (!$leg->isOver()) {
            throw new SimulationFailed("Expected yap to hang up on the volunteer once the caller was gone:\n" . $leg->twiml()->raw);
        }

        return [
            'volunteer answered, caller hung up, volunteer pressed 1 → told the caller hung up and hung up on',
            'caller hung up before the volunteer answered → volunteer told the caller hung up and hung up on',
        ];
    }
}
