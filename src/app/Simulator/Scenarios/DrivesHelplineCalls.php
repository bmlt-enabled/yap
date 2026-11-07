<?php

namespace App\Simulator\Scenarios;

use App\Simulator\SimulatedCall;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * The helpline half of a call: the caller asks for someone to talk to, is held
 * in a conference, and yap dials volunteers as outbound legs.
 */
trait DrivesHelplineCalls
{
    /**
     * Call the helpline for --service-body, press 1 for someone to talk to and,
     * once yap dials the caller into a conference, join it (which is what makes
     * yap start dialing volunteers).
     *
     * @param array<string, mixed> $query override_* settings for this call
     * @param ?callable(SimulatedCall): SimulatedCall $beforeConference answers any
     *        prompt between the menu and the conference, e.g. gender routing
     */
    protected function callHelpline(
        TwilioSimulator $simulator,
        ScenarioOptions $options,
        array $query = [],
        ?callable $beforeConference = null,
        ?string $from = null,
    ): SimulatedCall {
        $caller = $simulator->startCall($from ?? $options->from, $options->to, array_merge($options->locationOverrides(), [
            'override_service_body_id' => $options->serviceBodyId,
        ], $query))->press('1'); // digit_map_search_type: someone to talk to

        if ($beforeConference !== null) {
            $caller = $beforeConference($caller);
        }

        if ($caller->dialedConference() === null) {
            throw new SimulationFailed("Expected the caller to be dialed into a helpline conference:\n" . $caller->twiml()->raw);
        }

        return $caller->joinConference();
    }

    /**
     * Volunteer legs yap placed since request id $after.
     *
     * @return list<SimulatedCall>
     */
    protected function volunteerLegs(TwilioSimulator $simulator, int $after, int $atLeast = 1): array
    {
        $legs = $simulator->outboundCalls($after);
        if (count($legs) < $atLeast) {
            throw new SimulationFailed(sprintf(
                'Expected yap to dial at least %d volunteer(s) after the caller joined the conference, but it placed %d call(s). '
                . 'Is volunteer routing enabled for the service body with volunteers on shift right now? '
                . '(--seed sets that up when the simulator shares the instance\'s database.)',
                $atLeast,
                count($legs),
            ));
        }

        return $legs;
    }

    /**
     * Let every volunteer leg ring out until yap gives up and sends the caller to
     * voicemail, which it does by updating the caller's live call with a new Url.
     *
     * @return array<string, mixed> the calls.update request carrying the voicemail Url
     */
    protected function exhaustVolunteers(TwilioSimulator $simulator, SimulatedCall $leg, int $mark): array
    {
        $rang = [];
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $leg->noAnswer();
            $rang[$leg->sid] = true;

            $voicemail = $this->voicemailRedirect($simulator, $mark);
            if ($voicemail !== null) {
                return $voicemail;
            }

            $next = array_values(array_filter(
                $simulator->outboundCalls($mark),
                fn (SimulatedCall $candidate) => !isset($rang[$candidate->sid]),
            ));
            if ($next === []) {
                break;
            }
            $leg = $next[0];
        }

        throw new SimulationFailed(
            'Expected yap to redirect the caller to voicemail once no volunteer answered, but it never did. '
            . 'The service body needs a call strategy that ends in voicemail.'
        );
    }

    /**
     * The calls.update yap made to send the caller to voicemail, if any.
     *
     * @return ?array<string, mixed>
     */
    protected function voicemailRedirect(TwilioSimulator $simulator, int $mark): ?array
    {
        foreach ($simulator->outbound('calls.update', $mark) as $update) {
            if (str_contains((string) ($update['data']['Url'] ?? ''), 'voicemail')) {
                return $update;
            }
        }

        return null;
    }

    /** Follow yap's redirect of the caller's live call, as Twilio would. */
    protected function followRedirect(SimulatedCall $caller, array $update): SimulatedCall
    {
        return $caller->request($update['data']['Method'] ?? 'POST', $update['data']['Url']);
    }
}
