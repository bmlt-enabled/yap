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

    public function __construct(array $settings = [])
    {
        $this->callSid = 'CA' . Str::uuid()->toString();
        $this->phoneNumberSid = 'PN' . Str::uuid()->toString();
        [$this->utility, $this->twilio] = setupFakeTwilioService();
        foreach ($settings as $key => $value) {
            $this->utility->settings->set($key, $value);
        }
    }

    public function startCall(string $fromNumber, string $toNumber, string $method = 'GET'): self
    {
        $this->callData = [
            'CallSid' => $this->callSid,
            'Called' => $toNumber,
            'From' => $fromNumber,
            'To' => $toNumber,
        ];

        $this->twilio->registerInboundCall($this->callSid, $fromNumber, $toNumber, $this->phoneNumberSid);
        $this->twilio->registerIncomingPhoneNumber($this->phoneNumberSid, $toNumber);

        $this->lastResponse = test()->call($method, '/index.php', $this->callData);
        $this->lastResponse->assertStatus(200);

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

    public function volunteerRejects(int $n): self
    {
        return $this->answerVolunteer($n, '2');
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
        $update = $this->twilio->callUpdates[0] ?? null;
        if ($update === null || !isset($update['url'])) {
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

    private function dispatchWebhook(string $url, array $params = []): TestResponse
    {
        [$path, $query] = $this->normalizeWebhookUrl($url);
        $data = array_merge($this->callData, $query, $params);

        return test()->call('GET', $path, $data);
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
