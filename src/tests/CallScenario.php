<?php

namespace Tests;

use App\Constants\TwilioCallStatus;
use App\Models\RecordEvent;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use Tests\Fakes\FakeTwilioAccount;
use Tests\Support\TwimlExpectations;

class CallScenario extends TwilioCallTestBuilder
{
    public FakeTwilioAccount $twilio;

    private ?string $conferenceName = null;

    private ?string $conferenceStatusCallback = null;

    private bool $useCookies = true;

    public function __construct(array $settings = [])
    {
        $this->callSid = 'CA' . Str::uuid()->toString();
        $this->phoneNumberSid = 'PN' . Str::uuid()->toString();
        [$this->utility, $this->twilio] = setupFakeTwilioService();
        foreach ($settings as $key => $value) {
            $this->utility->settings->set($key, $value);
        }
    }

    public function withoutCookies(): self
    {
        $this->useCookies = false;

        return $this;
    }

    public function withCallData(array $data): self
    {
        $this->callData = array_merge($this->callData, $data);

        return $this;
    }

    /** @return array<string, mixed> */
    public function getCallData(): array
    {
        return $this->callData;
    }

    public function getCallSid(): string
    {
        return $this->callSid;
    }

    public function callEndpoint(string $path, array $params = []): self
    {
        $this->lastResponse = $this->call('GET', $path, array_merge($this->callData, $params));
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function hideConferenceFromApi(): self
    {
        $this->twilio->conferences = [];

        return $this;
    }

    public function startCall(string $fromNumber, string $toNumber, string $method = 'GET'): self
    {
        $this->callData = array_merge([
            'CallSid' => $this->callSid,
            'Called' => $toNumber,
            'From' => $fromNumber,
            'To' => $toNumber,
        ], $this->callData);

        $this->twilio->registerInboundCall($this->callSid, $fromNumber, $toNumber, $this->phoneNumberSid);
        $this->twilio->registerIncomingPhoneNumber($this->phoneNumberSid, $toNumber);

        $this->lastResponse = $this->call($method, '/index.php', $this->callData);
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function navigateToHelplineConference(bool $genderRouting = false, ?int $genderDigit = 2): self
    {
        $this->pressDigits('1')->followRedirect();

        if ($genderRouting) {
            $this->followRedirect()
                ->pressDigits((string) $genderDigit)
                ->followRedirect();
        }

        return $this;
    }

    public function lastTwiml(): string
    {
        return $this->lastResponse->getContent();
    }

    public function joinConference(): self
    {
        $twiml = $this->lastResponse->getContent();
        $conferenceName = TwimlExpectations::assertDialsConference($twiml);
        $statusCallback = TwimlExpectations::getAttributeFromTag($twiml, 'Conference', 'statusCallback');

        $this->conferenceName = $conferenceName;
        $this->conferenceStatusCallback = html_entity_decode($statusCallback, ENT_QUOTES);

        $this->twilio->createConference($conferenceName);
        $this->twilio->addConferenceParticipant($conferenceName, $this->callSid);
        $this->twilio->resetConferenceReadCount();

        $this->lastResponse = $this->dispatchWebhook(
            $statusCallback,
            [
                'StatusCallbackEvent' => 'participant-join',
                'SequenceNumber' => '1',
                'FriendlyName' => $conferenceName,
                'CallSid' => $this->callSid,
                'SearchType' => '1',
            ],
        );
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function answerVolunteer(int $n, string $digits = '1'): self
    {
        $leg = $this->pendingLeg($n);
        $this->lastResponse = $this->dispatchWebhook(
            $leg['url'],
            [
                'CallSid' => $leg['sid'],
                'Called' => $leg['to'],
            ],
        );
        $this->lastResponse->assertStatus(200);

        $twiml = $this->lastResponse->getContent();
        if (str_contains($twiml, '<Redirect')) {
            $xml = simplexml_load_string($twiml);
            $redirectElements = $xml->xpath('//Redirect');
            $redirect = (string) $redirectElements[0];
            $this->lastResponse = $this->dispatchWebhook(
                $redirect,
                [
                    'CallSid' => $leg['sid'],
                    'Called' => $leg['to'],
                    'Digits' => $digits,
                ],
            );
            $this->lastResponse->assertStatus(200);
            $twiml = $this->lastResponse->getContent();
        } elseif (str_contains($twiml, '<Gather')) {
            $action = TwimlExpectations::getAttributeFromTag($twiml, 'Gather', 'action');
            $this->lastResponse = $this->dispatchWebhook(
                $action,
                [
                    'CallSid' => $leg['sid'],
                    'Called' => $leg['to'],
                    'Digits' => $digits,
                ],
            );
            $this->lastResponse->assertStatus(200);
            $twiml = $this->lastResponse->getContent();
        }

        if (str_contains($twiml, '<Conference')) {
            $conferenceName = trim((string) simplexml_load_string($twiml)->xpath('//Conference')[0]);
            $this->twilio->addConferenceParticipant($conferenceName, $leg['sid']);
        }

        return $this;
    }

    public function fetchVolunteerOutdialTwiml(int $n): string
    {
        $leg = $this->pendingLeg($n);
        $this->lastResponse = $this->dispatchWebhook(
            $leg['url'],
            [
                'CallSid' => $leg['sid'],
                'Called' => $leg['to'],
            ],
        );
        $this->lastResponse->assertStatus(200);

        return $this->lastResponse->getContent();
    }

    public function volunteerRejects(int $n): self
    {
        $this->answerVolunteer($n, '2');

        $leg = $this->pendingLeg($n);
        if (!str_contains((string) $leg['statusCallback'], 'noop=1')) {
            $this->volunteerCallEnded($n);
        }

        return $this;
    }

    public function volunteerNoAnswer(int $n, string $status = TwilioCallStatus::NOANSWER): self
    {
        $leg = $this->pendingLeg($n);
        $this->lastResponse = $this->dispatchWebhook(
            $leg['statusCallback'],
            [
                'CallSid' => $leg['sid'],
                'Called' => $leg['to'],
                'CallStatus' => $status,
            ],
        );
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function volunteerCallEnded(int $n, string $status = TwilioCallStatus::COMPLETED): self
    {
        $leg = $this->pendingLeg($n);
        $this->lastResponse = $this->dispatchWebhook(
            $leg['statusCallback'],
            [
                'CallSid' => $leg['sid'],
                'Called' => $leg['to'],
                'CallStatus' => $status,
                'FriendlyName' => $this->conferenceName,
            ],
        );
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function applyVolunteerOutcome(int $n, string $outcome): self
    {
        return match ($outcome) {
            'answered' => $this->answerVolunteer($n),
            'rejected' => $this->volunteerRejects($n),
            'no-answer' => $this->volunteerNoAnswer($n),
            'busy' => $this->volunteerNoAnswer($n, TwilioCallStatus::BUSY),
            'failed' => $this->volunteerNoAnswer($n, TwilioCallStatus::FAILED),
            default => throw new \InvalidArgumentException("Unknown volunteer outcome: {$outcome}"),
        };
    }

    public function blastAllNoAnswer(): self
    {
        $count = count($this->twilio->pendingLegs);
        for ($n = 1; $n <= $count; $n++) {
            $this->volunteerNoAnswer($n);
        }

        return $this;
    }

    public function callerHangsUp(): self
    {
        $conference = $this->activeConference();
        $this->twilio->removeConferenceParticipant($conference['friendlyName'], $this->callSid);

        $this->lastResponse = $this->dispatchWebhook(
            $conference['statusCallback'],
            [
                'StatusCallbackEvent' => 'participant-leave',
                'FriendlyName' => $conference['friendlyName'],
                'CallSid' => $this->callSid,
            ],
        );
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function followCallerRedirect(): self
    {
        $update = collect($this->twilio->callUpdates)
            ->reverse()
            ->first(fn (array $entry) => isset($entry['url']));

        if ($update === null) {
            throw new \RuntimeException('No call redirect was recorded.');
        }

        $this->lastResponse = $this->dispatchWebhook(
            $update['url'],
            [
                'CallSid' => $this->callSid,
                'Called' => $this->callData['Called'] ?? '',
            ],
        );
        $this->lastResponse->assertStatus(200);

        return $this;
    }

    public function assertDialedInOrder(array $numbers): self
    {
        expect($this->twilio)->toHaveDialedInOrder($numbers);

        return $this;
    }

    public function assertRedirectedToVoicemail(): self
    {
        expect($this->twilio)->toHaveRedirectedCall('voicemail.php');

        return $this;
    }

    public function assertHungUp(string $callSid): self
    {
        $match = collect($this->twilio->callUpdates)->first(
            fn (array $update) => ($update['sid'] ?? null) === $callSid
                && ($update['status'] ?? null) === TwilioCallStatus::COMPLETED
        );
        Assert::assertNotNull($match, "Expected call {$callSid} to be hung up.");

        return $this;
    }

    /** @return array{friendlyName: string, statusCallback: string} */
    private function activeConference(): array
    {
        if ($this->conferenceName === null || $this->conferenceStatusCallback === null) {
            throw new \RuntimeException('No active conference found.');
        }

        return [
            'friendlyName' => $this->conferenceName,
            'statusCallback' => $this->conferenceStatusCallback,
        ];
    }

    private function pendingLeg(int $n): array
    {
        $index = $n - 1;
        if (!isset($this->twilio->pendingLegs[$index])) {
            throw new \RuntimeException("No pending volunteer leg at index {$n}.");
        }

        return $this->twilio->pendingLegs[$index];
    }

    protected function call($method, string $uri, array $data): TestResponse
    {
        if (!$this->useCookies) {
            return test()->call($method, $uri, $data, [], [], [], null);
        }

        return parent::call($method, $uri, $data);
    }

    private function dispatchWebhook(string $url, array $params = []): TestResponse
    {
        [$path, $query] = $this->normalizeWebhookUrl($url);
        $data = array_merge($this->callData, $query, $params);

        return $this->call('GET', $path, $data);
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function normalizeWebhookUrl(string $url): array
    {
        if (!str_contains($url, '://')) {
            $parts = parse_url($url);
            $path = $parts['path'] ?? $url;
            $query = [];
            if (isset($parts['query'])) {
                parse_str(html_entity_decode($parts['query']), $query);
            }

            return [$path, $query];
        }

        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';
        $query = [];
        if (isset($parsed['query'])) {
            parse_str(html_entity_decode($parsed['query']), $query);
        }

        return [$path, $query];
    }

    public function assertHasEvent(int $eventId): self
    {
        $callSids = [$this->callSid];
        foreach ($this->twilio->pendingLegs as $leg) {
            $callSids[] = $leg['sid'];
        }

        $event = RecordEvent::whereIn('callsid', array_unique($callSids))
            ->where('event_id', $eventId)
            ->first();
        Assert::assertNotNull(
            $event,
            "Expected to find event with ID {$eventId} for scenario calls " . implode(', ', $callSids),
        );

        return $this;
    }
}
