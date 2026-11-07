<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * Caller presses 1 for the helpline and is held in a conference while yap dials
 * a volunteer. With --outcome=answer the volunteer picks up, accepts, and joins
 * the caller; with --outcome=no-answer the volunteer never picks up and yap
 * redirects the caller to voicemail.
 *
 * Needs a service body with volunteer routing and an on-shift volunteer.
 * --seed creates both directly in the database of the app running the command,
 * which is only the target's database when the simulator runs against itself
 * (local docker, CI).
 */
class HelplineVolunteerScenario extends BaseScenario
{
    use DrivesHelplineCalls;

    public const DEFAULT_SEED_VOLUNTEER = '+15555551234';

    protected bool $needsServiceBody = true;

    public function name(): string
    {
        return 'helpline-volunteer';
    }

    public function description(): string
    {
        return 'Call: helpline volunteer routing (conference → volunteer answers, or no-answer → voicemail)';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        if ($options->serviceBodyId === null) {
            throw new SimulationFailed('helpline-volunteer needs --service-body=<id> of a service body with volunteer routing.');
        }
        if (!in_array($options->outcome, ['answer', 'no-answer'], true)) {
            throw new SimulationFailed("Unknown --outcome {$options->outcome}; use answer or no-answer.");
        }

        if ($options->seed) {
            ScenarioSeeder::helpline($this->serviceBodyId($options), [], [
                ScenarioSeeder::volunteer('Simulated Volunteer', $options->volunteer ?? self::DEFAULT_SEED_VOLUNTEER),
            ]);
        }

        $mark = $simulator->lastOutboundId();
        $caller = $this->callHelpline($simulator, $options);
        $conference = $caller->dialedConference();
        $leg = $this->volunteerLegs($simulator, $mark)[0];
        if ($options->volunteer !== null && !TwilioSimulator::sameNumber($leg->to, $options->volunteer)) {
            throw new SimulationFailed("Expected volunteer {$options->volunteer} to be dialed, but yap dialed {$leg->to}.");
        }
        $verified = ["caller held in conference {$conference}", "volunteer {$leg->to} dialed"];

        if ($options->outcome === 'answer') {
            $leg->answer()->press('1'); // "press 1 to accept this call"
            if ($leg->dialedConference() !== $conference) {
                throw new SimulationFailed("Expected the volunteer to be dialed into {$conference}:\n" . $leg->twiml()->raw);
            }
            $leg->joinConference();
            $verified[] = "volunteer accepted and joined {$conference}";

            $leg->hangUp();
            $caller->hangUp();

            return $verified;
        }

        $this->followRedirect($caller, $this->exhaustVolunteers($simulator, $leg, $mark));
        $caller->pending('Record');
        $verified[] = 'no answer → caller redirected to voicemail and prompted to record';
        $caller->hangUp();

        return $verified;
    }
}
