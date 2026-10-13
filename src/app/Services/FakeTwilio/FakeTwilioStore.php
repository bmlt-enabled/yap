<?php

namespace App\Services\FakeTwilio;

use App\Constants\TwilioCallStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Database-backed state for fake Twilio mode.
 *
 * Webhooks for a single call arrive as separate HTTP requests (often separate
 * PHP processes), so the fake account cannot live in memory the way the Pest
 * harness's FakeTwilioAccount does. Everything the fake REST client needs to
 * answer later requests, and everything the simulator wants to assert on, is
 * kept in the fake_twilio_* tables.
 */
class FakeTwilioStore
{
    public const REQUESTS = 'fake_twilio_requests';
    public const CALLS = 'fake_twilio_calls';
    public const PARTICIPANTS = 'fake_twilio_conference_participants';

    public static function enabled(): bool
    {
        return !app()->environment('production') && (bool) config('twilio.fake_mode', false);
    }

    public function recordRequest(
        string $method,
        string $url,
        string $resource,
        array $params,
        array $data,
        int $status,
        ?array $response,
    ): void {
        DB::table(self::REQUESTS)->insert([
            'method' => $method,
            'url' => $url,
            'resource' => $resource,
            'params' => json_encode($params),
            'data' => json_encode($data),
            'response_status' => $status,
            'response' => json_encode($response),
            'created_at' => Carbon::now(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function requests(int $afterId = 0, ?string $resource = null): array
    {
        $query = DB::table(self::REQUESTS)->where('id', '>', $afterId)->orderBy('id');
        if ($resource !== null) {
            $query->where('resource', $resource);
        }

        return $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'method' => $row->method,
            'url' => $row->url,
            'resource' => $row->resource,
            'params' => json_decode($row->params ?? '[]', true) ?: [],
            'data' => json_decode($row->data ?? '[]', true) ?: [],
            'response_status' => (int) $row->response_status,
            'response' => json_decode($row->response ?? 'null', true),
            'created_at' => (string) $row->created_at,
        ])->all();
    }

    public function reset(): void
    {
        DB::table(self::REQUESTS)->delete();
        DB::table(self::CALLS)->delete();
        DB::table(self::PARTICIPANTS)->delete();
    }

    /** @return array<string, mixed>|null */
    public function call(string $sid): ?array
    {
        $row = DB::table(self::CALLS)->where('sid', $sid)->first();

        return $row === null ? null : (array) $row;
    }

    /**
     * Insert or update a call. Unspecified fields keep their stored values.
     *
     * @param array<string, mixed> $attributes
     */
    public function saveCall(string $sid, array $attributes): array
    {
        $now = Carbon::now();
        $existing = $this->call($sid);

        if ($existing === null) {
            DB::table(self::CALLS)->insert(array_merge([
                'sid' => $sid,
                'status' => TwilioCallStatus::INPROGRESS,
                'direction' => 'inbound',
                'created_at' => $now,
                'updated_at' => $now,
            ], $attributes));
        } elseif ($attributes !== []) {
            DB::table(self::CALLS)->where('sid', $sid)->update(array_merge($attributes, ['updated_at' => $now]));
        }

        return $this->call($sid);
    }

    public function joinConference(string $friendlyName, string $callSid): void
    {
        $now = Carbon::now();
        DB::table(self::PARTICIPANTS)->insertOrIgnore([
            'friendly_name' => $friendlyName,
            'call_sid' => $callSid,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function leaveConference(string $friendlyName, string $callSid): void
    {
        DB::table(self::PARTICIPANTS)
            ->where('friendly_name', $friendlyName)
            ->where('call_sid', $callSid)
            ->delete();
    }

    /** @return list<string> conference friendly names that currently have participants */
    public function activeConferences(?string $friendlyName = null): array
    {
        $query = DB::table(self::PARTICIPANTS)->select('friendly_name')->distinct();
        if ($friendlyName !== null) {
            $query->where('friendly_name', $friendlyName);
        }

        return $query->pluck('friendly_name')->all();
    }

    /** @return list<string> participant call sids, in join order */
    public function participants(string $friendlyName): array
    {
        return DB::table(self::PARTICIPANTS)
            ->where('friendly_name', $friendlyName)
            ->orderBy('id')
            ->pluck('call_sid')
            ->all();
    }

    /**
     * Conference SIDs are derived from the friendly name so a conference needs no
     * row of its own: it exists exactly while it has participants.
     */
    public static function conferenceSid(string $friendlyName): string
    {
        return 'CF' . md5($friendlyName);
    }

    public function friendlyNameForConferenceSid(string $conferenceSid): ?string
    {
        foreach ($this->activeConferences() as $friendlyName) {
            if (self::conferenceSid($friendlyName) === $conferenceSid) {
                return $friendlyName;
            }
        }

        return null;
    }

    public static function newSid(string $prefix): string
    {
        return $prefix . bin2hex(random_bytes(16));
    }
}
