<?php

namespace Database\Seeders;

use App\Constants\DataType;
use App\Constants\EventId;
use App\Models\User;
use App\Services\SettingsService;
use App\Structures\RecordType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fixture data for the Playwright e2e suite (src/e2e). Runs only with
 * ENVIRONMENT=test. The service body ids are real ones on the BMLT server the
 * suite talks to (config.test.php: latest.aws.bmlt.app), so the admin UI can
 * resolve their names. The e2e specs assert on the values below; keep
 * e2e/fixtures/seed.js in step when changing them.
 */
class TestEnvironmentSeeder extends Seeder
{
    // Brooklyn Area Service and Manhattan Area Service, both under the Greater
    // New York Region (1).
    public const BROOKLYN = 1006;
    public const MANHATTAN = 1008;

    // Every seeded call SID starts with this.
    private const CALLSID_PREFIX = 'CAe2e';

    // Recording SIDs only need the RE + hex shape VoicemailRepository parses.
    public const VOICEMAILS = [
        ['callsid' => 'CAe2e0000000000000000000000000vm1', 'recording' => 'REe2e00000000000000000000000000001', 'from' => '+15005550111', 'pin' => 1111111],
        ['callsid' => 'CAe2e0000000000000000000000000vm2', 'recording' => 'REe2e00000000000000000000000000002', 'from' => '+15005550112', 'pin' => 2222222],
        ['callsid' => 'CAe2e0000000000000000000000000vm3', 'recording' => 'REe2e00000000000000000000000000003', 'from' => '+15005550113', 'pin' => 3333333],
    ];

    public function run() : void
    {
        if (getenv("ENVIRONMENT") !== "test") {
            return;
        }

        // playwright.config.js seeds on top of whatever the last run left
        // (migrate, not migrate:fresh), so start the call data over.
        foreach (['records', 'records_events', 'sessions', 'event_status'] as $table) {
            DB::table($table)->where('callsid', 'like', self::CALLSID_PREFIX . '%')->delete();
        }

        $this->seedUsers();
        $this->seedBrooklynCallHandling();
        $this->seedCallRecords();
        $this->seedVoicemails();
        $this->primeGoogleResponses();
    }

    private function seedUsers(): void
    {
        DB::statement("
            INSERT IGNORE INTO users (name, username, password, permissions, is_admin)
            VALUES ('admin', 'admin', ?, 0, 1);
        ", [User::hashPassword('admin')]);

        // A service body admin who only has rights to Brooklyn.
        DB::statement("
            INSERT IGNORE INTO users (name, username, password, permissions, is_admin, service_bodies)
            VALUES ('Brooklyn Admin', 'brooklyn', ?, 0, 0, ?);
        ", [User::hashPassword('brooklyn'), (string) self::BROOKLYN]);
    }

    /**
     * Volunteer routing for Brooklyn plus one volunteer on shift every day, so
     * the Schedules page has a routing-enabled service body and a calendar.
     */
    private function seedBrooklynCallHandling(): void
    {
        DB::table('config')->updateOrInsert([
            'service_body_id' => self::BROOKLYN,
            'parent_id' => 0,
            'data_type' => DataType::YAP_CALL_HANDLING_V2,
        ], [
            // The shape the Call Handling dialog saves (CallHandlingModel.js).
            'data' => json_encode([[
                'volunteer_routing' => 'volunteers',
                'forced_caller_id' => '',
                'volunteers_redirect_id' => 0,
                'call_timeout' => 20,
                'gender_routing' => false,
                'call_strategy' => 0,
                'volunteer_sms_notification' => 'no_sms',
                'sms_strategy' => 2,
                'primary_contact' => '',
                'primary_contact_email' => '',
                'moh' => 'https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical',
                'override_en_US_greeting' => '',
                'override_en_US_voicemail_greeting' => '',
            ]]),
        ]);

        $shifts = [];
        for ($day = 1; $day <= 7; $day++) {
            $shifts[] = [
                'day' => $day,
                'tz' => 'America/New_York',
                'start_time' => '12:00 AM',
                'end_time' => '11:59 PM',
            ];
        }

        DB::table('config')->updateOrInsert([
            'service_body_id' => self::BROOKLYN,
            'parent_id' => 0,
            'data_type' => DataType::YAP_VOLUNTEERS_V2,
        ], [
            'data' => json_encode([[
                'volunteer_name' => 'E2E Schedule Volunteer',
                'volunteer_phone_number' => '(555) 555-0100',
                'volunteer_enabled' => true,
                'volunteer_notes' => '',
                'volunteer_shift_schedule' => base64_encode(json_encode($shifts)),
            ]]),
        ]);
    }

    /**
     * Call records two days back, so the Reports page's default "last 30
     * days" range includes them in any browser timezone.
     */
    private function seedCallRecords(): void
    {
        $start = Carbon::now('UTC')->subDays(2)->startOfHour();

        // Brooklyn: a volunteer lookup by voice, with a geocoded location for the map.
        $this->record('CAe2e00000000000000000000000brk01', $start, '+15005550101', '+15005550199', 95, RecordType::PHONE);
        $this->event('CAe2e00000000000000000000000brk01', $start->copy()->addSeconds(10), EventId::VOLUNTEER_SEARCH, self::BROOKLYN, [
            'gather' => 'Brooklyn',
            'coordinates' => ['location' => 'Brooklyn, NY, USA', 'latitude' => 40.6782, 'longitude' => -73.9442],
        ]);
        $this->event('CAe2e00000000000000000000000brk01', $start->copy()->addSeconds(20), EventId::VOLUNTEER_DIALED, self::BROOKLYN, null);

        // Brooklyn: a meeting search by SMS.
        $this->record('CAe2e00000000000000000000000brk02', $start->copy()->addMinutes(30), '+15005550102', '+15005550199', 0, RecordType::SMS);
        $this->event('CAe2e00000000000000000000000brk02', $start->copy()->addMinutes(30), EventId::MEETING_SEARCH_SMS, self::BROOKLYN, null);

        // Manhattan: a meeting search by voice. Must not show up when the
        // Reports filter is set to Brooklyn.
        $this->record('CAe2e00000000000000000000000man01', $start->copy()->addHour(), '+15005550201', '+15005550299', 60, RecordType::PHONE);
        $this->event('CAe2e00000000000000000000000man01', $start->copy()->addHour()->addSeconds(10), EventId::MEETING_SEARCH, self::MANHATTAN, null);
        $this->event('CAe2e00000000000000000000000man01', $start->copy()->addHour()->addSeconds(15), EventId::MEETING_SEARCH_LOCATION_GATHERED, self::MANHATTAN, [
            'gather' => 'Manhattan',
            'coordinates' => ['location' => 'Manhattan, NY, USA', 'latitude' => 40.7831, 'longitude' => -73.9712],
        ]);
    }

    private function seedVoicemails(): void
    {
        $accountSid = app(SettingsService::class)->get('twilio_account_sid');
        $time = Carbon::now('UTC')->subDay()->startOfHour();

        foreach (self::VOICEMAILS as $i => $voicemail) {
            $at = $time->copy()->addMinutes($i * 10);
            $this->record($voicemail['callsid'], $at, $voicemail['from'], '+15005550199', 45, RecordType::PHONE);
            $this->event($voicemail['callsid'], $at->copy()->addSeconds(30), EventId::VOICEMAIL, self::BROOKLYN, [
                'url' => sprintf(
                    'https://api.twilio.com/2010-04-01/Accounts/%s/Recordings/%s',
                    $accountSid,
                    $voicemail['recording']
                ),
            ]);
            DB::table('sessions')->insert(['callsid' => $voicemail['callsid'], 'pin' => $voicemail['pin']]);
        }
    }

    /**
     * The upgrade advisor pings Google's geocoding and time zone APIs before it
     * gets to the Twilio checks, and config.test.php only has a placeholder
     * key. HttpService caches GET bodies by URL, so put OK answers in that
     * cache. The time zone URL carries a timestamp rounded to 30 minutes, so
     * prime the current and next window; each spec reseeds before it runs.
     */
    private function primeGoogleResponses(): void
    {
        $settings = app(SettingsService::class);

        cache()->put(
            'cache_' . $settings->geocodingApiUri() . '&address=' . urlencode('91409'),
            json_encode(['status' => 'OK', 'results' => []]),
            3600
        );

        foreach ([0, 1800] as $offset) {
            $timestamp = time() + $offset;
            $timestamp -= $timestamp % 1800;
            cache()->put(
                'cache_' . sprintf(
                    '%s&location=%s,%s&timestamp=%d',
                    $settings->timezoneApiUri(),
                    '34.2011137',
                    '-118.475058',
                    $timestamp
                ),
                json_encode(['status' => 'OK', 'timeZoneId' => 'America/Los_Angeles']),
                3600
            );
        }
    }

    private function record(string $callsid, Carbon $start, string $from, string $to, int $duration, int $type): void
    {
        DB::table('records')->insert([
            'callsid' => $callsid,
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $start->copy()->addSeconds($duration)->format('Y-m-d H:i:s'),
            'from_number' => $from,
            'to_number' => $to,
            'duration' => $duration,
            'type' => $type,
            'payload' => '{}',
        ]);
    }

    private function event(string $callsid, Carbon $at, int $eventId, int $serviceBodyId, ?array $meta): void
    {
        DB::table('records_events')->insert([
            'callsid' => $callsid,
            'event_time' => $at->format('Y-m-d H:i:s'),
            'event_id' => $eventId,
            'service_body_id' => $serviceBodyId,
            'meta' => $meta === null ? null : json_encode($meta),
            'type' => $eventId === EventId::MEETING_SEARCH_SMS ? RecordType::SMS : RecordType::PHONE,
        ]);
    }
}
