<?php

namespace App\Simulator\Scenarios;

use App\Constants\CycleAlgorithm;
use App\Constants\DataType;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;
use App\Structures\ServiceBodyCallHandling;

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
class HelplineVolunteerScenario implements Scenario
{
    public const DEFAULT_SEED_VOLUNTEER = '+15555551234';

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
            $this->seed($options);
        }

        $mark = $simulator->lastOutboundId();
        $caller = $simulator->startCall($options->from, $options->to, array_merge($options->locationOverrides(), [
            'override_service_body_id' => $options->serviceBodyId,
        ]))->press('1'); // digit_map_search_type: someone to talk to

        $conference = $caller->dialedConference();
        if ($conference === null) {
            throw new SimulationFailed("Expected the caller to be dialed into a helpline conference:\n" . $caller->twiml()->raw);
        }

        $caller->joinConference();
        $leg = $this->nextVolunteerLeg($simulator, $mark, $options);
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

        $voicemail = $this->exhaustVolunteers($simulator, $leg, $mark, $options);
        $caller->request($voicemail['data']['Method'] ?? 'POST', $voicemail['data']['Url']);
        $caller->pending('Record');
        $verified[] = 'no answer → caller redirected to voicemail and prompted to record';
        $caller->hangUp();

        return $verified;
    }

    private function nextVolunteerLeg(TwilioSimulator $simulator, int $after, ScenarioOptions $options): SimulatedCall
    {
        $legs = $simulator->outboundCalls($after);
        if ($legs === []) {
            throw new SimulationFailed(
                "Expected yap to dial a volunteer after the caller joined the conference, but no call was placed. "
                . "Is volunteer routing enabled for service body {$options->serviceBodyId} with a volunteer on shift right now? "
                . "(--seed creates one when the simulator shares the instance's database.)"
            );
        }

        $leg = $legs[0];
        if ($options->volunteer !== null && self::digits($leg->to) !== self::digits($options->volunteer)) {
            throw new SimulationFailed("Expected volunteer {$options->volunteer} to be dialed, but yap dialed {$leg->to}.");
        }

        return $leg;
    }

    /**
     * Let every volunteer leg ring out until yap gives up and sends the caller to
     * voicemail, which it does by updating the caller's live call with a new Url.
     *
     * @return array<string, mixed> the calls.update request carrying the voicemail Url
     */
    private function exhaustVolunteers(TwilioSimulator $simulator, SimulatedCall $leg, int $mark, ScenarioOptions $options): array
    {
        $answered = [];
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $leg->noAnswer();
            $answered[$leg->sid] = true;

            foreach ($simulator->outbound('calls.update', $mark) as $update) {
                if (str_contains((string) ($update['data']['Url'] ?? ''), 'voicemail')) {
                    return $update;
                }
            }

            $next = array_values(array_filter(
                $simulator->outboundCalls($mark),
                fn (SimulatedCall $candidate) => !isset($answered[$candidate->sid]),
            ));
            if ($next === []) {
                break;
            }
            $leg = $next[0];
        }

        throw new SimulationFailed(
            'Expected yap to redirect the caller to voicemail once no volunteer answered, but it never did. '
            . "Service body {$options->serviceBodyId} needs a call strategy that ends in voicemail."
        );
    }

    private function seed(ScenarioOptions $options): void
    {
        $serviceBodyId = (int) $options->serviceBodyId;

        $callHandling = new ServiceBodyCallHandling();
        $callHandling->service_body_id = $serviceBodyId;
        $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
        $callHandling->volunteer_routing_enabled = true;
        $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

        $shifts = [];
        foreach (range(1, 7) as $day) {
            $shifts[] = ['day' => $day, 'tz' => 'UTC', 'start_time' => '12:00 AM', 'end_time' => '11:59 PM'];
        }

        // Replace only this service body's call handling and volunteers; leave
        // any other configuration it has alone.
        ConfigData::where('service_body_id', $serviceBodyId)
            ->whereIn('data_type', [DataType::YAP_CALL_HANDLING_V2, DataType::YAP_VOLUNTEERS_V2])
            ->delete();
        ConfigData::createServiceBodyCallHandling($serviceBodyId, $callHandling);
        ConfigData::createVolunteers($serviceBodyId, [[
            'volunteer_name' => 'Simulated Volunteer',
            'volunteer_phone_number' => $options->volunteer ?? self::DEFAULT_SEED_VOLUNTEER,
            'volunteer_gender' => VolunteerGender::UNSPECIFIED,
            'volunteer_responder' => 0,
            'volunteer_notes' => 'Seeded by php artisan yap:simulate',
            'volunteer_enabled' => true,
            'volunteer_shift_schedule' => base64_encode(json_encode($shifts)),
        ]]);
    }

    private static function digits(string $number): string
    {
        return ltrim(preg_replace('/\D/', '', $number), '1');
    }
}
