<?php

namespace Tests\Fakes;

use App\Constants\TwilioCallStatus;
use DateTime;
use Illuminate\Support\Str;

/**
 * Stateful in-memory Twilio account covering the REST surface the app touches.
 * Duck-typed stdClass objects stand in for SDK instances.
 */
class FakeTwilioAccount
{
    /** @var array<string, array<string, mixed>> */
    public array $calls = [];

    /** @var array<string, array<string, mixed>> */
    public array $conferences = [];

    /** @var list<array<string, mixed>> */
    public array $messages = [];

    /** @var list<array<string, mixed>> outbound legs created but not yet answered */
    public array $pendingLegs = [];

    /** @var list<array<string, mixed>> calls($sid)->update([...]) audit trail */
    public array $callUpdates = [];

    public int $conferenceReadsBeforeVisible = 0;

    private int $conferenceReadCount = 0;

    public function resetConferenceReadCount(): void
    {
        $this->conferenceReadCount = 0;
    }

    public function registerInboundCall(
        string $callSid,
        string $from,
        string $to,
        string $phoneNumberSid,
    ): void {
        $this->calls[$callSid] = [
            'sid' => $callSid,
            'to' => $to,
            'from' => $from,
            'status' => TwilioCallStatus::INPROGRESS,
            'url' => null,
            'statusCallback' => null,
            'options' => [],
            'startTime' => new DateTime('2023-01-26T18:00:00'),
            'endTime' => new DateTime('2023-01-26T18:15:00'),
            'phoneNumberSid' => $phoneNumberSid,
        ];
    }

    public function registerIncomingPhoneNumber(
        string $phoneNumberSid,
        string $phoneNumber,
        ?string $statusCallback = 'status.php',
    ): void {
        $this->incomingPhoneNumbers[$phoneNumberSid] = [
            'sid' => $phoneNumberSid,
            'phoneNumber' => $phoneNumber,
            'statusCallback' => $statusCallback,
        ];
    }

    /** @var array<string, array<string, mixed>> */
    private array $incomingPhoneNumbers = [];

    /** @var array<string, object> */
    private array $phoneLookups = [];

    public function setPhoneLookup(string $number, object $result): void
    {
        $this->phoneLookups[$number] = $result;
    }

    public function callList(): object
    {
        $account = $this;

        return new class ($account) {
            public function __construct(private FakeTwilioAccount $account)
            {
            }

            public function create(string $to, string $from, array $options = []): object
            {
                $sid = 'CA' . Str::uuid()->toString();
                $record = [
                    'sid' => $sid,
                    'to' => $to,
                    'from' => $from,
                    'status' => TwilioCallStatus::QUEUED,
                    'url' => $options['url'] ?? null,
                    'statusCallback' => $options['statusCallback'] ?? null,
                    'options' => $options,
                    'startTime' => new DateTime(),
                    'endTime' => null,
                    'phoneNumberSid' => null,
                ];
                $this->account->calls[$sid] = $record;
                $this->account->pendingLegs[] = $record;

                return (object) ['sid' => $sid];
            }
        };
    }

    public function callContext(string $sid): object
    {
        $account = $this;

        return new class ($account, $sid) {
            public function __construct(
                private FakeTwilioAccount $account,
                private string $sid,
            ) {
            }

            public function fetch(): object
            {
                $call = $this->account->calls[$this->sid] ?? null;
                if ($call === null) {
                    throw new \RuntimeException("Unknown call sid: {$this->sid}");
                }

                return (object) $call;
            }

            public function update(array $data): object
            {
                $this->account->callUpdates[] = array_merge(['sid' => $this->sid], $data);
                if (isset($this->account->calls[$this->sid])) {
                    foreach ($data as $key => $value) {
                        $this->account->calls[$this->sid][$key] = $value;
                    }
                }

                return (object) ['sid' => $this->sid];
            }
        };
    }

    public function conferenceList(): object
    {
        $account = $this;

        return new class ($account) {
            public function __construct(private FakeTwilioAccount $account)
            {
            }

            public function read(array $filters = []): array
            {
                $this->account->conferenceReadCount++;
                if ($this->account->conferenceReadCount <= $this->account->conferenceReadsBeforeVisible) {
                    return [];
                }

                $results = [];
                foreach ($this->account->conferences as $sid => $conference) {
                    if (isset($filters['friendlyName']) && $conference['friendlyName'] !== $filters['friendlyName']) {
                        continue;
                    }
                    if (isset($filters['status']) && $conference['status'] !== $filters['status']) {
                        continue;
                    }
                    $results[] = (object) [
                        'sid' => $sid,
                        'friendlyName' => $conference['friendlyName'],
                        'status' => $conference['status'],
                    ];
                }

                return $results;
            }
        };
    }

    public function conferenceContext(string $sid): object
    {
        $account = $this;

        return new class ($account, $sid) {
            public $participants;

            public function __construct(
                private FakeTwilioAccount $account,
                private string $sid,
            ) {
                $this->participants = new class ($account, $sid) {
                    public function __construct(
                        private FakeTwilioAccount $account,
                        private string $sid,
                    ) {
                    }

                    public function read(): array
                    {
                        $conference = $this->account->conferences[$this->sid] ?? null;
                        if ($conference === null) {
                            return [];
                        }

                        return array_map(
                            fn (array $participant) => (object) ['callSid' => $participant['callSid']],
                            $conference['participants'],
                        );
                    }
                };
            }
        };
    }

    public function createConference(string $friendlyName): string
    {
        $sid = 'CF' . Str::uuid()->toString();
        $this->conferences[$sid] = [
            'sid' => $sid,
            'friendlyName' => $friendlyName,
            'status' => 'in-progress',
            'participants' => [],
        ];

        return $sid;
    }

    public function addConferenceParticipant(string $friendlyName, string $callSid): void
    {
        foreach ($this->conferences as &$conference) {
            if ($conference['friendlyName'] !== $friendlyName) {
                continue;
            }
            foreach ($conference['participants'] as $participant) {
                if ($participant['callSid'] === $callSid) {
                    return;
                }
            }
            $conference['participants'][] = ['callSid' => $callSid];
        }
    }

    public function removeConferenceParticipant(string $friendlyName, string $callSid): void
    {
        foreach ($this->conferences as &$conference) {
            if ($conference['friendlyName'] !== $friendlyName) {
                continue;
            }
            $conference['participants'] = array_values(array_filter(
                $conference['participants'],
                fn (array $participant) => $participant['callSid'] !== $callSid,
            ));
        }
    }

    public function conferenceSidForFriendlyName(string $friendlyName): ?string
    {
        foreach ($this->conferences as $sid => $conference) {
            if ($conference['friendlyName'] === $friendlyName) {
                return $sid;
            }
        }

        return null;
    }

    public function messageList(): object
    {
        $account = $this;

        return new class ($account) {
            public function __construct(private FakeTwilioAccount $account)
            {
            }

            public function create(string $to, array $options = []): object
            {
                $this->account->messages[] = [
                    'to' => $to,
                    'from' => $options['from'] ?? null,
                    'body' => $options['body'] ?? null,
                    'options' => $options,
                ];

                return (object) ['sid' => 'SM' . Str::uuid()->toString()];
            }
        };
    }

    public function lookupsV1(): object
    {
        $account = $this;

        return new class ($account) {
            public function __construct(private FakeTwilioAccount $account)
            {
            }

            public function phoneNumbers(string $number): object
            {
                $account = $this->account;

                return new class ($account, $number) {
                    public function __construct(
                        private FakeTwilioAccount $account,
                        private string $number,
                    ) {
                    }

                    public function fetch(): object
                    {
                        return $this->account->phoneLookups[$this->number]
                            ?? (object) ['phoneNumber' => $this->number];
                    }
                };
            }
        };
    }

    public function incomingPhoneNumberContext(string $sid): object
    {
        $account = $this;

        return new class ($account, $sid) {
            public function __construct(
                private FakeTwilioAccount $account,
                private string $sid,
            ) {
            }

            public function fetch(): object
            {
                $record = $this->account->incomingPhoneNumbers[$this->sid] ?? [
                    'sid' => $this->sid,
                    'phoneNumber' => '+12125551212',
                    'statusCallback' => 'status.php',
                ];

                return $this->account->hydrateIncomingPhoneNumber($record);
            }
        };
    }

    /**
     * Twilio's IncomingPhoneNumberInstance stringifies to the E.164 number; the
     * app logs it with sprintf("%s", $instance) in CallFlowController::index().
     */
    public function hydrateIncomingPhoneNumber(array $record): object
    {
        return new class ($record) {
            public string $sid;

            public string $phoneNumber;

            public ?string $statusCallback;

            public function __construct(array $record)
            {
                $this->sid = $record['sid'];
                $this->phoneNumber = $record['phoneNumber'];
                $this->statusCallback = $record['statusCallback'] ?? null;
            }

            public function __toString(): string
            {
                return $this->phoneNumber;
            }
        };
    }

    /** @return list<string> */
    public function dialedNumbers(): array
    {
        return array_map(fn (array $leg) => $leg['to'], $this->pendingLegs);
    }
}
