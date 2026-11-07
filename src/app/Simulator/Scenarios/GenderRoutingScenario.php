<?php

namespace App\Simulator\Scenarios;

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * With gender routing on, helpline-search asks the caller who they want to talk
 * to before dialing anyone. Under every call strategy, pressing 2 must dial
 * only the woman volunteer, and pressing 3 (no preference, offered when
 * gender_no_preference is on) may dial any of them: the first volunteer under
 * the linear strategies, all three when blasting.
 */
class GenderRoutingScenario extends BaseScenario
{
    use DrivesHelplineCalls;

    /** In shift order: the unspecified volunteer is first, so "first dialed" means something. */
    private const VOLUNTEERS = [
        'Unspecified' => ['+15555550801', VolunteerGender::UNSPECIFIED],
        'Man' => ['+15555550802', VolunteerGender::MALE],
        'Woman' => ['+15555550803', VolunteerGender::FEMALE],
    ];

    private const STRATEGIES = [
        'linear cycle and voicemail' => CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL,
        'linear loop forever' => CycleAlgorithm::LINEAR_LOOP_FOREVER,
        'random cycle and voicemail' => CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL,
        'random loop forever' => CycleAlgorithm::RANDOM_LOOP_FOREVER,
        'blasting' => CycleAlgorithm::BLASTING,
    ];

    protected bool $needsSeed = true;

    protected bool $needsServiceBody = true;

    public function name(): string
    {
        return 'gender-routing';
    }

    public function description(): string
    {
        return 'Call: gender routing (press 2 → woman, press 3 → either) under every call strategy';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $this->requireOptions($options);

        $volunteers = [];
        foreach (self::VOLUNTEERS as $name => [$number, $gender]) {
            $volunteers[] = ScenarioSeeder::volunteer($name, $number, $gender);
        }
        $everyone = array_column(self::VOLUNTEERS, 0);
        $woman = self::VOLUNTEERS['Woman'][0];

        $verified = [];
        $caller = null;
        foreach (self::STRATEGIES as $label => $strategy) {
            ScenarioSeeder::helpline($this->serviceBodyId($options), ['gender_routing' => 1, 'call_strategy' => $strategy], $volunteers);

            [$caller, $dialed] = $this->choose($simulator, $options, '2');
            if (array_diff($dialed, [$woman]) !== []) {
                throw new SimulationFailed("{$label}: pressing 2 for a woman dialed " . implode(', ', $dialed) . ", expected only {$woman}.");
            }

            [$caller, $dialed] = $this->choose($simulator, $options, '3');
            $expected = match ($strategy) {
                CycleAlgorithm::BLASTING => $everyone,
                CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL, CycleAlgorithm::LINEAR_LOOP_FOREVER => [$everyone[0]],
                default => null, // random: any one of them
            };
            $ok = $expected === null
                ? count($dialed) === 1 && array_diff($dialed, $everyone) === []
                : $dialed === $expected;
            if (!$ok) {
                throw new SimulationFailed(sprintf(
                    '%s: pressing 3 for no preference dialed %s, expected %s.',
                    $label,
                    implode(', ', $dialed),
                    $expected === null ? 'one of ' . implode(', ', $everyone) : implode(', ', $expected),
                ));
            }

            $verified[] = sprintf('%s: 2 → %s; 3 → %s', $label, $woman, implode(', ', $dialed));
        }
        $caller?->hangUp();

        return $verified;
    }

    /**
     * Call, press 1 for the helpline and $digit at the gender prompt, and wait
     * for the caller to be in the conference.
     *
     * @return array{0: SimulatedCall, 1: list<string>} the caller, and the numbers yap dialed
     */
    private function choose(TwilioSimulator $simulator, ScenarioOptions $options, string $digit): array
    {
        $mark = $simulator->lastOutboundId();
        $caller = $this->callHelpline(
            $simulator,
            $options,
            ['override_gender_no_preference' => 'true'],
            fn (SimulatedCall $call) => $call
                ->expectHeard('press two to talk to a woman')
                ->expectHeard('press three to speak to either')
                ->press($digit),
        );
        $dialed = array_map(fn (SimulatedCall $leg) => $leg->to, $this->volunteerLegs($simulator, $mark));
        sort($dialed);

        return [$caller, $dialed];
    }
}
