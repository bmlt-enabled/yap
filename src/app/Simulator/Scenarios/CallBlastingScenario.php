<?php

namespace App\Simulator\Scenarios;

use App\Constants\CycleAlgorithm;
use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * The blasting strategy rings every on-shift volunteer at once. The first to
 * answer and press 1 joins the caller; anyone who answers after that is told a
 * volunteer already joined and is hung up on; a leg that rings out is ignored
 * (only all of them ringing out sends the caller to voicemail).
 */
class CallBlastingScenario extends BaseScenario
{
    use DrivesHelplineCalls;

    private const VOLUNTEERS = ['+15555550901', '+15555550902', '+15555550903'];

    protected bool $needsSeed = true;

    protected bool $needsServiceBody = true;

    public function name(): string
    {
        return 'call-blasting';
    }

    public function description(): string
    {
        return 'Call: blasting rings every volunteer; the first to answer wins, later ones are turned away';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $this->requireOptions($options);
        ScenarioSeeder::helpline($this->serviceBodyId($options), ['call_strategy' => CycleAlgorithm::BLASTING], array_map(
            fn (string $number) => ScenarioSeeder::volunteer("Blasting Volunteer {$number}", $number),
            self::VOLUNTEERS,
        ));

        $mark = $simulator->lastOutboundId();
        $caller = $this->callHelpline($simulator, $options);
        $conference = $caller->dialedConference();
        $legs = $this->volunteerLegs($simulator, $mark, count(self::VOLUNTEERS));
        $dialed = array_map(fn (SimulatedCall $leg) => $leg->to, $legs);
        sort($dialed);
        if ($dialed !== self::VOLUNTEERS) {
            throw new SimulationFailed('Expected blasting to ring ' . implode(', ', self::VOLUNTEERS) . ' at once, it rang ' . implode(', ', $dialed));
        }

        [$silent, $first, $late] = $legs;
        $first->answer()->expectHeard('you have a call from the helpline')->press('1');
        if ($first->dialedConference() !== $conference) {
            throw new SimulationFailed("Expected the first volunteer to answer to be dialed into {$conference}:\n" . $first->twiml()->raw);
        }
        $first->joinConference();

        $late->answer()->expectHeard('a volunteer has already joined the call');
        if (!$late->isOver()) {
            throw new SimulationFailed("Expected yap to hang up on a volunteer who answered after another joined:\n" . $late->twiml()->raw);
        }

        $silent->noAnswer();
        if ($this->voicemailRedirect($simulator, $mark) !== null) {
            throw new SimulationFailed('One of three blasted volunteers ringing out sent the caller to voicemail, though another had answered.');
        }

        $first->hangUp();
        $caller->hangUp();

        return [
            'all three volunteers rung at once: ' . implode(', ', $dialed),
            "{$first->to} answered first, pressed 1 and joined {$conference}",
            "{$late->to} answered later and was told a volunteer already joined",
            "{$silent->to} rang out without sending the caller to voicemail",
        ];
    }
}
