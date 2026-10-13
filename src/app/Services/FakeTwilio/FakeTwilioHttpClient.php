<?php

namespace App\Services\FakeTwilio;

use App\Constants\TwilioCallStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Http\Client;
use Twilio\Http\Response;

/**
 * A Twilio transport that never leaves the box.
 *
 * Plugged into Twilio\Rest\Client when fake mode is on (see TwilioService), so
 * the SDK, its URL building and its response hydration all run for real; only
 * the HTTP hop to api.twilio.com is replaced. Every request is recorded in
 * fake_twilio_requests and answered with a plausible JSON payload built from the
 * state in FakeTwilioStore.
 *
 * Only the REST surface yap actually uses is modelled. Anything else gets a
 * Twilio-shaped 404 so an unexpected API call fails loudly in the simulator
 * rather than silently succeeding.
 */
class FakeTwilioHttpClient implements Client
{
    private const API_PATH = '#^/2010-04-01/Accounts/(?<account>[^/]+)/(?<rest>.+)$#';

    public function __construct(private FakeTwilioStore $store)
    {
    }

    public function request(
        string $method,
        string $url,
        array $params = [],
        array $data = [],
        array $headers = [],
        ?string $user = null,
        ?string $password = null,
        ?int $timeout = null,
        ?AuthStrategy $authStrategy = null
    ): Response {
        $method = strtoupper($method);
        [$resource, $status, $body] = $this->route($method, $url, $params, $data);

        $this->store->recordRequest($method, $url, $resource, $params, $data, $status, $body);

        return new Response($status, $body === null ? null : json_encode($body));
    }

    /**
     * @return array{0: string, 1: int, 2: array<string, mixed>|null}
     */
    private function route(string $method, string $url, array $params, array $data): array
    {
        $host = (string) parse_url($url, PHP_URL_HOST);
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (str_starts_with($host, 'lookups.') && preg_match('#^/v1/PhoneNumbers/(?<number>[^/]+)$#', $path, $m)) {
            return ['lookups.fetch', 200, $this->lookup(rawurldecode($m['number']))];
        }

        if (!preg_match(self::API_PATH, $path, $m)) {
            return $this->notFound('unknown', $url);
        }

        $account = $m['account'];
        $rest = $m['rest'];

        return match (true) {
            $rest === 'Calls.json' && $method === 'POST' => ['calls.create', 201, $this->createCall($account, $data)],
            (bool) preg_match('#^Calls/(?<sid>[^/]+)\.json$#', $rest, $c) => $this->callResource($method, $account, $c['sid'], $data, $url),
            $rest === 'Messages.json' && $method === 'POST' => ['messages.create', 201, $this->createMessage($account, $data)],
            $rest === 'Conferences.json' && $method === 'GET' => ['conferences.read', 200, $this->readConferences($account, $params)],
            (bool) preg_match('#^Conferences/(?<sid>[^/]+)/Participants\.json$#', $rest, $c) && $method === 'GET'
                => ['participants.read', 200, $this->readParticipants($account, $c['sid'])],
            (bool) preg_match('#^IncomingPhoneNumbers/(?<sid>[^/]+)\.json$#', $rest, $c) && $method === 'GET'
                => ['incoming_phone_numbers.fetch', 200, $this->incomingPhoneNumber($account, $c['sid'], null)],
            $rest === 'IncomingPhoneNumbers.json' && $method === 'GET'
                => ['incoming_phone_numbers.read', 200, $this->readIncomingPhoneNumbers($account)],
            (bool) preg_match('#^Recordings/[^/]+\.json$#', $rest) && $method === 'DELETE' => ['recordings.delete', 204, null],
            default => $this->notFound('unknown', $url),
        };
    }

    /** @return array{0: string, 1: int, 2: array<string, mixed>} */
    private function callResource(string $method, string $account, string $sid, array $data, string $url): array
    {
        if ($method === 'GET') {
            $call = $this->store->call($sid);

            return $call === null
                ? $this->notFound('calls.fetch', $url)
                : ['calls.fetch', 200, $this->callPayload($account, $call)];
        }

        if ($method !== 'POST') {
            return $this->notFound('unknown', $url);
        }

        $call = $this->store->call($sid);
        if ($call === null) {
            return $this->notFound('calls.update', $url);
        }

        $changes = [];
        if (isset($data['Status'])) {
            $changes['status'] = $data['Status'];
        }
        if (isset($data['Url'])) {
            $changes['url'] = $data['Url'];
        }
        $call = $this->store->saveCall($sid, $changes);

        // A call that is ended leaves whatever conference it was in, as on Twilio.
        if (in_array($call['status'], [TwilioCallStatus::COMPLETED, 'canceled'], true)) {
            foreach ($this->store->activeConferences() as $friendlyName) {
                $this->store->leaveConference($friendlyName, $sid);
            }
        }

        return ['calls.update', 200, $this->callPayload($account, $call)];
    }

    private function createCall(string $account, array $data): array
    {
        $sid = FakeTwilioStore::newSid('CA');
        $options = $data;
        unset($options['To'], $options['From']);

        $call = $this->store->saveCall($sid, [
            'from' => $data['From'] ?? null,
            'to' => $data['To'] ?? null,
            'status' => TwilioCallStatus::QUEUED,
            'direction' => 'outbound-api',
            'url' => $data['Url'] ?? null,
            'status_callback' => $data['StatusCallback'] ?? null,
            'options' => json_encode($options),
        ]);

        return $this->callPayload($account, $call);
    }

    private function callPayload(string $account, array $call): array
    {
        $created = Carbon::parse($call['created_at']);

        return [
            'sid' => $call['sid'],
            'account_sid' => $account,
            'from' => $call['from'],
            'to' => $call['to'],
            'status' => $call['status'],
            'direction' => $call['direction'],
            'phone_number_sid' => $call['phone_number_sid'],
            'start_time' => $created->toRfc2822String(),
            'end_time' => $call['status'] === TwilioCallStatus::COMPLETED
                ? Carbon::parse($call['updated_at'])->toRfc2822String()
                : null,
            'date_created' => $created->toRfc2822String(),
            'date_updated' => Carbon::parse($call['updated_at'])->toRfc2822String(),
            'uri' => "/2010-04-01/Accounts/{$account}/Calls/{$call['sid']}.json",
        ];
    }

    private function createMessage(string $account, array $data): array
    {
        $sid = FakeTwilioStore::newSid('SM');
        $now = Carbon::now()->toRfc2822String();

        return [
            'sid' => $sid,
            'account_sid' => $account,
            'from' => $data['From'] ?? null,
            'to' => $data['To'] ?? null,
            'body' => $data['Body'] ?? null,
            'status' => 'queued',
            'direction' => 'outbound-api',
            'num_segments' => '1',
            'date_created' => $now,
            'date_updated' => $now,
            'uri' => "/2010-04-01/Accounts/{$account}/Messages/{$sid}.json",
        ];
    }

    private function readConferences(string $account, array $params): array
    {
        $status = $params['Status'] ?? null;
        $conferences = [];

        // A conference only exists while it has participants, so every one we
        // know about is in-progress; a filter for any other status matches nothing.
        if ($status === null || $status === 'in-progress') {
            foreach ($this->store->activeConferences($params['FriendlyName'] ?? null) as $friendlyName) {
                $sid = FakeTwilioStore::conferenceSid($friendlyName);
                $conferences[] = [
                    'sid' => $sid,
                    'account_sid' => $account,
                    'friendly_name' => $friendlyName,
                    'status' => 'in-progress',
                    'uri' => "/2010-04-01/Accounts/{$account}/Conferences/{$sid}.json",
                ];
            }
        }

        return $this->page('conferences', "/2010-04-01/Accounts/{$account}/Conferences.json", $conferences);
    }

    private function readParticipants(string $account, string $conferenceSid): array
    {
        $friendlyName = $this->store->friendlyNameForConferenceSid($conferenceSid);
        $participants = [];

        foreach ($friendlyName === null ? [] : $this->store->participants($friendlyName) as $callSid) {
            $participants[] = [
                'account_sid' => $account,
                'call_sid' => $callSid,
                'conference_sid' => $conferenceSid,
                'status' => 'connected',
                'muted' => false,
                'hold' => false,
            ];
        }

        return $this->page(
            'participants',
            "/2010-04-01/Accounts/{$account}/Conferences/{$conferenceSid}/Participants.json",
            $participants,
        );
    }

    private function incomingPhoneNumber(string $account, string $sid, ?string $phoneNumber): array
    {
        if ($phoneNumber === null) {
            $call = DB::table(FakeTwilioStore::CALLS)->where('phone_number_sid', $sid)->first();
            $phoneNumber = $call?->to;
        }

        return [
            'sid' => $sid,
            'account_sid' => $account,
            'phone_number' => $phoneNumber,
            'friendly_name' => $phoneNumber,
            // Point at the real status webhook so CallFlowController does not
            // raise a "misconfigured phone number" alert for every simulated call.
            'status_callback' => url('status.php'),
            'voice_url' => url('index.php'),
            'sms_url' => url('sms-gateway.php'),
            'uri' => "/2010-04-01/Accounts/{$account}/IncomingPhoneNumbers/{$sid}.json",
        ];
    }

    private function readIncomingPhoneNumbers(string $account): array
    {
        $numbers = DB::table(FakeTwilioStore::CALLS)
            ->whereNotNull('phone_number_sid')
            ->select('phone_number_sid', 'to')
            ->distinct()
            ->get()
            ->map(fn ($row) => $this->incomingPhoneNumber($account, $row->phone_number_sid, $row->to))
            ->all();

        return $this->page('incoming_phone_numbers', "/2010-04-01/Accounts/{$account}/IncomingPhoneNumbers.json", $numbers);
    }

    private function lookup(string $number): array
    {
        return [
            'phone_number' => $number,
            'national_format' => $number,
            'country_code' => 'US',
            'carrier' => ['type' => 'mobile', 'name' => 'Fake Twilio Carrier'],
            'url' => "https://lookups.twilio.com/v1/PhoneNumbers/{$number}",
        ];
    }

    private function page(string $key, string $uri, array $records): array
    {
        return [
            $key => $records,
            'page' => 0,
            'page_size' => 50,
            'start' => 0,
            'end' => max(count($records) - 1, 0),
            'first_page_uri' => $uri,
            'next_page_uri' => null,
            'previous_page_uri' => null,
            'uri' => $uri,
        ];
    }

    /** @return array{0: string, 1: int, 2: array<string, mixed>} */
    private function notFound(string $resource, string $url): array
    {
        return [$resource, 404, [
            'code' => 20404,
            'message' => "The requested resource {$url} was not found",
            'more_info' => 'https://www.twilio.com/docs/errors/20404',
            'status' => 404,
        ]];
    }
}
