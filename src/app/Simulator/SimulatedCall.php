<?php

namespace App\Simulator;

use App\Constants\TwilioCallStatus;

/**
 * One call leg as Twilio would run it: the inbound caller, or an outbound leg
 * the app placed to a volunteer.
 *
 * After every webhook the leg executes the returned TwiML until it reaches a
 * verb that waits on the person on the phone (<Gather>, <Dial>, <Record>) or
 * the call ends. Scenarios then act for that person: press(), speak(),
 * joinConference(), hangUp().
 */
class SimulatedCall
{
    private ?Twiml $twiml = null;

    private ?string $currentUrl = null;

    private string $status;

    private ?string $conference = null;

    /** @var list<string> every <Say> heard on this leg, in order */
    private array $transcript = [];

    public function __construct(
        private TwilioSimulator $simulator,
        public readonly string $sid,
        public readonly string $from,
        public readonly string $to,
        public readonly string $direction,
        private ?string $answerUrl = null,
        private string $answerMethod = 'POST',
        private ?string $statusCallback = null,
        private string $statusCallbackMethod = 'POST',
    ) {
        $this->status = $direction === 'inbound' ? TwilioCallStatus::INPROGRESS : TwilioCallStatus::QUEUED;
    }

    /**
     * The parameters Twilio sends on every voice webhook for this leg.
     *
     * @param array<string, scalar|array> $extra
     * @return array<string, scalar|array>
     */
    public function params(array $extra = []): array
    {
        return array_merge([
            'AccountSid' => $this->simulator->accountSid(),
            'ApiVersion' => '2010-04-01',
            'CallSid' => $this->sid,
            'CallStatus' => $this->status,
            'Direction' => $this->direction,
            'From' => $this->from,
            'Caller' => $this->from,
            'To' => $this->to,
            'Called' => $this->to,
        ], $extra);
    }

    /**
     * Fetch TwiML for this leg from $url and run it.
     *
     * @param array<string, scalar|array> $extra
     */
    public function request(string $method, string $url, array $extra = []): self
    {
        $response = $this->simulator->webhook($method, $url, $this->params($extra), $this->sid);
        if ($response->status !== 200) {
            throw new SimulationFailed(sprintf(
                "%s %s returned HTTP %d for call %s:\n%s",
                $method,
                $url,
                $response->status,
                $this->sid,
                $response->body,
            ));
        }

        $this->currentUrl = $response->url;
        $this->twiml = Twiml::parse($response->body);
        foreach ($this->twiml->says() as $say) {
            $this->transcript[] = $say;
            $this->simulator->log("  🔊 {$say}");
        }

        return $this->run();
    }

    /**
     * Execute verbs until one waits on the caller. <Say>/<Play>/<Pause> are
     * already in the transcript; a <Redirect> is followed immediately; running
     * off the end of the document hangs up, exactly as on Twilio.
     */
    private function run(): self
    {
        foreach ($this->twiml->verbs as $verb) {
            switch ($verb->name) {
                case 'Gather':
                case 'Dial':
                case 'Record':
                    return $this;
                case 'Hangup':
                case 'Reject':
                    return $this->ended();
                case 'Redirect':
                    return $this->request($verb->attr('method', 'POST'), $this->resolve($verb->text));
            }
        }

        return $this->ended();
    }

    private function ended(): self
    {
        $this->status = TwilioCallStatus::COMPLETED;
        $this->simulator->log("☎ {$this->sid} ended");

        return $this;
    }

    private function resolve(string $reference): string
    {
        return UrlResolver::resolve($this->currentUrl ?? $this->simulator->url('/'), $reference);
    }

    private function gather(string $input): TwimlVerb
    {
        $gather = $this->pending('Gather');
        $inputs = explode(' ', $gather->attr('input', 'dtmf'));
        if (!in_array($input, $inputs, true)) {
            throw new SimulationFailed("The <Gather> on {$this->currentUrl} does not accept {$input} input:\n{$this->twiml()->raw}");
        }

        return $gather;
    }

    /** Key in digits at the current <Gather>. */
    public function press(string $digits): self
    {
        $gather = $this->gather('dtmf');
        $this->simulator->log("  ⌨ {$digits}");

        return $this->request(
            $gather->attr('method', 'POST'),
            $this->resolve($gather->attr('action', $this->currentUrl)),
            ['Digits' => $digits],
        );
    }

    /** Answer the current <Gather> by voice. */
    public function speak(string $speech, float $confidence = 0.9): self
    {
        $gather = $this->gather('speech');
        $this->simulator->log("  🗣 {$speech}");

        return $this->request(
            $gather->attr('method', 'POST'),
            $this->resolve($gather->attr('action', $this->currentUrl)),
            ['SpeechResult' => $speech, 'Confidence' => (string) $confidence],
        );
    }

    /** Let the current <Gather> time out; Twilio carries on with the next verb. */
    public function noInput(): self
    {
        $this->twiml = $this->twiml()->after($this->pending('Gather'));

        return $this->run();
    }

    /** Pick up an outbound leg the app placed, fetching its TwiML from the Url it was created with. */
    public function answer(): self
    {
        if ($this->answerUrl === null) {
            throw new SimulationFailed("Call {$this->sid} has no answer Url to fetch.");
        }

        $this->status = TwilioCallStatus::INPROGRESS;
        $this->simulator->markCallStatus($this->sid, $this->from, $this->to, $this->status);
        $this->simulator->log("☎ {$this->to} answers {$this->sid}");

        return $this->request($this->answerMethod, $this->answerUrl);
    }

    /** An outbound leg that never connects: report it to the leg's status callback. */
    public function noAnswer(string $status = TwilioCallStatus::NOANSWER): self
    {
        $this->status = $status;
        $this->simulator->markCallStatus($this->sid, $this->from, $this->to, $status);
        $this->simulator->log("☎ {$this->to} {$status} on {$this->sid}");
        $this->sendStatusCallback();

        return $this;
    }

    /**
     * Join the <Conference> this leg is dialing. If the conference subscribes to
     * join events, deliver participant-join to its statusCallback like Twilio.
     */
    public function joinConference(): self
    {
        $conference = $this->pending('Dial')->child('Conference');
        if ($conference === null) {
            throw new SimulationFailed("Call {$this->sid} is not dialing a conference:\n{$this->twiml()->raw}");
        }

        $this->conference = $conference->text;
        $this->simulator->joinConference($this->conference, $this->sid);
        $this->conferenceEvent($conference, 'join', 'participant-join');

        return $this;
    }

    /** The person on this leg hangs up. */
    public function hangUp(): self
    {
        if ($this->conference !== null) {
            $this->simulator->leaveConference($this->conference, $this->sid);
            $conference = $this->twiml?->first('Dial')?->child('Conference');
            if ($conference !== null) {
                $this->conferenceEvent($conference, 'leave', 'participant-leave');
            }
            $this->conference = null;
        }

        $this->status = TwilioCallStatus::COMPLETED;
        $this->simulator->markCallStatus($this->sid, $this->from, $this->to, $this->status);
        $this->simulator->log("☎ {$this->sid} hangs up");
        $this->sendStatusCallback();

        return $this;
    }

    private function conferenceEvent(TwimlVerb $conference, string $event, string $callbackEvent): void
    {
        $callback = $conference->attr('statusCallback');
        $events = explode(' ', $conference->attr('statusCallbackEvent', ''));
        if ($callback === null || !in_array($event, $events, true)) {
            return;
        }

        $response = $this->simulator->webhook(
            $conference->attr('statusCallbackMethod', 'POST'),
            $this->resolve($callback),
            $this->params([
                'ConferenceSid' => 'CF' . md5($conference->text),
                'FriendlyName' => $conference->text,
                'StatusCallbackEvent' => $callbackEvent,
                'SequenceNumber' => (string) $this->simulator->nextConferenceSequence($conference->text),
            ]),
            $this->sid,
        );
        $this->expectOk($response, "conference {$callbackEvent} callback");
    }

    private function sendStatusCallback(): void
    {
        if ($this->statusCallback === null) {
            return;
        }

        $response = $this->simulator->webhook(
            $this->statusCallbackMethod,
            $this->statusCallback,
            $this->params(['CallDuration' => '0']),
            $this->sid,
        );
        $this->expectOk($response, 'status callback');
    }

    private function expectOk(SimulatorResponse $response, string $what): void
    {
        if ($response->status !== 200) {
            throw new SimulationFailed("The {$what} {$response->url} returned HTTP {$response->status}:\n{$response->body}");
        }
    }

    public function twiml(): Twiml
    {
        if ($this->twiml === null) {
            throw new SimulationFailed("Call {$this->sid} has not fetched any TwiML yet.");
        }

        return $this->twiml;
    }

    /** The verb this leg is waiting on, which must be $name. */
    public function pending(string $name): TwimlVerb
    {
        $verb = $this->status === TwilioCallStatus::COMPLETED ? null : $this->twiml()->first($name);
        if ($verb === null) {
            throw new SimulationFailed(sprintf(
                "Expected call %s to be waiting on <%s>, but it is %s. Last TwiML from %s:\n%s",
                $this->sid,
                $name,
                $this->status === TwilioCallStatus::COMPLETED ? 'over' : 'not',
                $this->currentUrl,
                $this->twiml()->raw,
            ));
        }

        return $verb;
    }

    public function isWaitingOn(string $name): bool
    {
        return $this->status !== TwilioCallStatus::COMPLETED && $this->twiml?->has($name);
    }

    public function dialedConference(): ?string
    {
        return $this->isWaitingOn('Dial') ? $this->twiml()->first('Dial')->child('Conference')?->text : null;
    }

    public function isOver(): bool
    {
        return $this->status === TwilioCallStatus::COMPLETED;
    }

    /** @return list<string> */
    public function transcript(): array
    {
        return $this->transcript;
    }

    /** Assert that something said on this leg so far contains $needle (case-insensitive). */
    public function expectHeard(string $needle): self
    {
        foreach ($this->transcript as $say) {
            if (stripos($say, $needle) !== false) {
                return $this;
            }
        }

        throw new SimulationFailed(sprintf(
            "Expected call %s to hear \"%s\". It heard:\n  %s",
            $this->sid,
            $needle,
            implode("\n  ", $this->transcript) ?: '(nothing)',
        ));
    }
}
