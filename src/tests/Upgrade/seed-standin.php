<?php
/**
 * Fill a database migrated by yap 4.5.3 with representative FAKE data, written
 * in the formats 4.5.3 itself writes (#1684). Used only by
 * build-standin-fixture.sh; a real 4.5.3 dump replaces the result.
 *
 *   php tests/Upgrade/seed-standin.php <database>
 *
 * Formats follow 4.5.3's code: volunteers are stored verbatim from the admin
 * UI's jQuery form serialization (strings, "true" for a checked box, absent
 * when unchecked, shifts as base64 JSON with int or string days); call
 * handling is a ServiceBodyCallHandling struct with every default; service
 * body settings carry the UI's serviceBodyConfigurationFields key; users have
 * unsalted SHA-256 passwords; records/records_events/conference_participants/
 * sessions/event_status are what the call flow writes.
 *
 * The numbers and names below are invented but deliberately NOT in the 555
 * range, so the sanitizer has something to replace and the leak check proves
 * it did.
 *
 * Deterministic: mt_srand() is fixed, so rebuilding gives the same data.
 */

namespace Yap\UpgradeTest;

use PDO;

require_once __DIR__ . '/db-env.php';

final class StandinSeeder
{
    private const SB_A = 1006;   // service bodies on latest.aws.bmlt.app
    private const SB_B = 1007;
    private const SB_C = 1053;
    private const HELPLINE = '+19195550100';

    private int $sidCounter = 0;
    /**
     * An invented account SID. Built at runtime: a literal of that shape
     * trips secret scanning even though it's fake. The sanitizer replaces it.
     */
    private string $account;

    public function __construct(private PDO $pdo)
    {
        mt_srand(453);
        $this->account = 'AC' . substr(hash('sha256', 'yap-standin-account'), 0, 32);
    }

    public function run(): void
    {
        $this->users();
        $groupId = $this->config();
        $this->calls();
        $this->misc();
        foreach (['users', 'config', 'records', 'records_events', 'conference_participants', 'sessions',
                     'event_status', 'alerts', 'metrics', 'cache', 'flags', 'migrations'] as $table) {
            $count = $this->pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            printf("  %-28s %d rows\n", $table, $count);
        }
        echo "Seeded (shared group config.id = {$groupId})\n";
    }

    // --- users ---------------------------------------------------------------

    private function users(): void
    {
        $insert = $this->pdo->prepare('INSERT INTO users (name, username, password, permissions, is_admin, created_on,
            service_bodies) VALUES (?, ?, SHA2(?, 256), ?, ?, ?, ?)');
        foreach ([
            // name, username, password, permissions, is_admin, created_on, service_bodies
            // The installer's admin: name = username, but is_admin keeps it local.
            ['admin', 'admin', 'admin-pass-1', 0, 1, '2021-03-02 10:00:00', null],
            // Local users created in the 4.5.3 Users page.
            ['Marguerite Okafor', 'mokafor', 'Tr0ub4dor&3', 1, 0, '2022-01-15 18:30:00', '1006,1007'],
            ['Desmond Ruiz', 'druiz', 'correct horse battery', 0, 0, '2023-06-07 02:12:44', '1006'],
            // Local user with name = username but MANAGE_USERS: stays local.
            ['tpatel', 'tpatel', 'p@tel-2024', 1, 0, '2024-02-29 23:59:59', '1053'],
            // Rows shaped like the 5.0 betas' BMLT logins (name = username, not an
            // admin, no permissions); #1676 converts them to auth_source = bmlt.
            ['gnyr_admin', 'gnyr_admin', 'bmlt-password-1', 0, 0, '2026-09-20 12:00:00', '1006'],
            ['area51', 'area51', 'bmlt-password-2', 0, null, '2026-10-01 08:15:00', '1007,1053'],
        ] as $row) {
            $insert->execute($row);
        }
    }

    // --- config --------------------------------------------------------------

    private function config(): int
    {
        // 24/7 button: int days 1..7, midnight to 11:59 PM.
        $allWeek = [];
        for ($day = 1; $day <= 7; $day++) {
            $allWeek[] = $this->shift($day, 'America/New_York', '12:00 AM', '11:59 PM', 'PHONE');
        }
        // A single "add shift": the day is a string.
        $evenings = [
            $this->shift('2', 'America/New_York', '06:00 PM', '10:00 PM', 'PHONE'),
            $this->shift('4', 'America/New_York', '06:00 PM', '10:00 PM', 'PHONE'),
            $this->shift(6, 'America/Chicago', '09:00 AM', '01:00 PM', 'SMS'),
        ];
        $weekend = [
            $this->shift(7, 'America/Los_Angeles', '08:00 AM', '08:00 PM', 'PHONE'),
            $this->shift(1, 'America/Los_Angeles', '08:00 AM', '08:00 PM', 'PHONE'),
        ];

        // Groups: one owned by SB_A and shared with SB_B, one private to SB_C.
        $sharedGroupId = $this->insertConfig(self::SB_A, '_YAP_GROUPS_V2_', null, [
            ['group_name' => 'Night Owls', 'group_shared_service_bodies' => [(string) self::SB_B]],
        ]);
        $privateGroupId = $this->insertConfig(self::SB_C, '_YAP_GROUPS_V2_', null, [
            ['group_name' => 'Spanish Line', 'group_shared_service_bodies' => []],
        ]);
        $this->insertConfig(self::SB_A, '_YAP_GROUP_VOLUNTEERS_V2_', $sharedGroupId, [
            $this->volunteer('Priya Natarajan', '+19195550187', '2', '0', $this->b64($allWeek), 'Night owl', true),
            $this->volunteer('Walter Kim', '(704) 555-0143', '1', '1', $this->b64($evenings), '', true),
        ]);
        $this->insertConfig(self::SB_C, '_YAP_GROUP_VOLUNTEERS_V2_', $privateGroupId, [
            $this->volunteer(
                'Lucia Fernandez',
                '+17865550111',
                '2',
                '0',
                $this->b64($weekend),
                'Habla español',
                true,
                ['es-US']
            ),
        ]);

        // Service body volunteers.
        $this->insertConfig(self::SB_A, '_YAP_VOLUNTEERS_V2_', 0, [
            $this->volunteer(
                'Harold Abernathy',
                '+19195550123',
                '1',
                '0',
                $this->b64($evenings),
                'Prefers texts. Email harold.abernathy@gmail.com',
                true,
                ['en-US']
            ),
            $this->volunteer('Ngozi Eze', '9195550199', '2', '1', $this->b64($weekend), '', true),
            // Disabled: volunteer_enabled is absent.
            $this->volunteer('Frank Delgado', '+19195550166', '0', '0', $this->b64($allWeek), 'On leave', false),
            // Enabled with no shifts ("W10=" is base64 of []).
            $this->volunteer('Ines Moreau', '+19195550177', '0', '0', 'W10=', '', true),
            ['group_id' => (string) $sharedGroupId, 'group_enabled' => 'true'],
        ]);
        $this->insertConfig(self::SB_B, '_YAP_VOLUNTEERS_V2_', 0, [
            $this->volunteer('Oskar Lindqvist', '+13365550102', '1', '0', $this->b64($allWeek), '', true),
            // The shared group, enabled here too.
            ['group_id' => (string) $sharedGroupId, 'group_enabled' => 'true'],
        ]);
        $this->insertConfig(self::SB_C, '_YAP_VOLUNTEERS_V2_', 0, [
            ['group_id' => (string) $privateGroupId, 'group_enabled' => 'true'],
        ]);

        // Call handling.
        $this->insertConfig(self::SB_A, '_YAP_CALL_HANDLING_V2_', 0, [$this->callHandling([
            'volunteer_routing' => 'volunteers', 'call_strategy' => '1', 'call_timeout' => '20',
            'gender_routing' => '1', 'forced_caller_id' => '+19195550100', 'volunteer_sms_notification' => 'send_sms',
            'primary_contact' => '+19195550155', 'primary_contact_email' => 'helpline.chair@outlook.com',
            'override_en_US_greeting' => 'https://example.org/greetings/sb1006.mp3',
        ])]);
        $this->insertConfig(self::SB_B, '_YAP_CALL_HANDLING_V2_', 0, [$this->callHandling([
            'volunteer_routing' => 'volunteers_and_sms', 'call_strategy' => '3', 'sms_strategy' => '2',
        ])]);
        $this->insertConfig(self::SB_C, '_YAP_CALL_HANDLING_V2_', 0, [$this->callHandling([
            'volunteer_routing' => 'volunteers_redirect', 'volunteers_redirect_id' => (string) self::SB_A,
        ])]);

        // Service body setting overrides, as the 4.5.3 Service Bodies page posts them.
        // None on SB_A: the simulator's scenarios run through 1006 afterwards,
        // and language_selections would put a language menu in front of them.
        $this->insertConfig(self::SB_C, '_YAP_CONFIG_V2_', null, [[
            'serviceBodyConfigurationFields' => 'title',
            'title' => 'Triangle Area Helpline',
            'language' => 'en-US',
            'call_timeout' => '30',
            'jft_option' => 'true',
            'language_selections' => 'en-US,es-US',
            'smtp_password' => 'hunter2-not-real',
            'gender_no_preference' => 'true',
        ]]);
        $this->insertConfig(self::SB_B, '_YAP_CONFIG_V2_', null, [[
            'serviceBodyConfigurationFields' => 'title',
            'title' => 'Piedmont Helpline',
            'province_lookup' => 'true',
            'province_lookup_list' => "['North Carolina','South Carolina']",
        ]]);

        return $sharedGroupId;
    }

    private function shift(int|string $day, string $tz, string $start, string $end, string $type): array
    {
        return ['day' => $day, 'tz' => $tz, 'start_time' => $start, 'end_time' => $end, 'type' => $type];
    }

    private function b64(array $shifts): string
    {
        return base64_encode(json_encode($shifts));
    }

    private function volunteer(
        string $name,
        string $phone,
        string $gender,
        string $responder,
        string $schedule,
        string $notes,
        bool $enabled,
        ?array $languages = null
    ): array {
        $card = [
            'volunteer_name' => $name,
            'volunteer_phone_number' => $phone,
            'volunteer_gender' => $gender,
            'volunteer_responder' => $responder,
        ];
        if ($languages !== null) {
            $card['volunteer_language'] = $languages;
        }
        $card['volunteer_shift_schedule'] = $schedule;
        $card['volunteer_notes'] = $notes;
        if ($enabled) {
            $card['volunteer_enabled'] = 'true';
        }
        return $card;
    }

    private function callHandling(array $posted): array
    {
        return array_merge([
            'service_body_id' => null, 'service_body_name' => null, 'service_body_parent_id' => null,
            'service_body_parent_name' => null, 'volunteer_routing' => 'helpline_field',
            'volunteer_routing_enabled' => false, 'volunteer_routing_redirect' => false,
            'volunteers_redirect_id' => '', 'volunteer_routing_redirect_id' => 0,
            'forced_caller_id_enabled' => false, 'forced_caller_id_number' => '0000000000',
            'call_timeout' => '20', 'volunteer_sms_notification_enabled' => false, 'gender_routing' => '0',
            'gender_routing_enabled' => false, 'call_strategy' => '1', 'primary_contact_number_enabled' => false,
            'primary_contact' => '', 'primary_contact_number' => '0000000000',
            'primary_contact_email_enabled' => false, 'primary_contact_email' => '',
            'moh' => 'https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical', 'moh_count' => 1,
            'sms_routing_enabled' => false, 'sms_strategy' => '2', 'override_en_US_greeting' => '',
            'override_en_US_voicemail_greeting' => '', 'forced_caller_id' => '',
            'volunteer_sms_notification' => 'no_sms',
        ], $posted);
    }

    private function insertConfig(int $serviceBodyId, string $dataType, ?int $parentId, array $data): int
    {
        $this->pdo->prepare('INSERT INTO config (service_body_id, data, data_type, parent_id) VALUES (?, ?, ?, ?)')
            ->execute([$serviceBodyId, json_encode($data), $dataType, $parentId]);
        return (int) $this->pdo->lastInsertId();
    }

    // --- calls ---------------------------------------------------------------

    private function calls(): void
    {
        $callers = ['+19195550142', '+12525550198', '+17045550161', '+19105550127', '+13365550184',
            '+18285550133', '+19195550109', '+19845550170'];
        $volunteers = ['+19195550123', '9195550199', '+19195550187', '+13365550102'];
        $start = strtotime('2024-01-08 15:00:00 UTC');
        $end = strtotime('2026-10-28 03:00:00 UTC');
        $flows = ['answered', 'answered', 'meeting', 'meeting', 'meeting', 'jft', 'voicemail', 'voicemail',
            'sms_meeting', 'sms_volunteer', 'root_meeting', 'rejected'];

        for ($i = 0; $i < 60; $i++) {
            $t = $start + intdiv(($end - $start) * $i, 59) + mt_rand(0, 3600);
            $flow = $flows[$i % count($flows)];
            $sb = [self::SB_A, self::SB_A, self::SB_B, self::SB_C][$i % 4];
            $caller = $callers[$i % count($callers)];
            $volunteer = $volunteers[$i % count($volunteers)];
            $this->call($flow, $t, $sb, $caller, $volunteer, $i);
        }
    }

    private function call(string $flow, int $t, int $sb, string $caller, string $volunteer, int $i): void
    {
        $sms = str_starts_with($flow, 'sms_');
        $sid = $this->sid($sms ? 'SM' : 'CA');
        $duration = $sms ? 0 : mt_rand(20, 900);
        $type = $sms ? 2 : 1;
        $cities = [['Raleigh, NC', 35.7796, -78.6382], ['Durham, NC', 35.9940, -78.8986],
            ['Charlotte, NC', 35.2271, -80.8431], ['Wilmington, NC', 34.2104, -77.8868]];
        [$place, $lat, $lng] = $cities[$i % count($cities)];
        $located = ['gather' => explode(',', $place)[0],
            'coordinates' => ['location' => "$place, USA", 'latitude' => $lat, 'longitude' => $lng]];

        $payload = $sms ? [
            'ToCountry' => 'US', 'ToState' => 'NC', 'SmsMessageSid' => $sid, 'NumMedia' => '0',
            'ToCity' => 'RALEIGH', 'FromZip' => '27601', 'SmsSid' => $sid, 'FromState' => 'NC',
            'SmsStatus' => 'received', 'FromCity' => 'RALEIGH', 'Body' => 'meeting near ' . $place,
            'FromCountry' => 'US', 'To' => self::HELPLINE, 'ToZip' => '27601', 'NumSegments' => '1',
            'MessageSid' => $sid, 'AccountSid' => $this->account, 'From' => $caller, 'ApiVersion' => '2010-04-01',
        ] : [
            'Called' => self::HELPLINE, 'ToState' => 'NC', 'CallerCountry' => 'US', 'Direction' => 'inbound',
            'Timestamp' => gmdate('D, d M Y H:i:s +0000', $t + $duration), 'CallbackSource' => 'call-progress-events',
            'CallerState' => 'NC', 'ToZip' => '', 'SequenceNumber' => '0', 'CallSid' => $sid, 'To' => self::HELPLINE,
            'CallerZip' => '27513', 'ToCountry' => 'US', 'CalledZip' => '', 'ApiVersion' => '2010-04-01',
            'CalledCity' => '', 'CallStatus' => 'completed', 'Duration' => (string) max(1, intdiv($duration, 60)),
            'From' => $caller, 'CallDuration' => (string) $duration, 'AccountSid' => $this->account,
            'CalledCountry' => 'US', 'CallerCity' => 'CARY', 'ToCity' => '', 'FromCountry' => 'US',
            'Caller' => $caller, 'FromCity' => 'CARY', 'CalledState' => 'NC', 'FromZip' => '27513',
            'FromState' => 'NC', 'StirVerstat' => 'TN-Validation-Passed-A',
        ];
        $this->pdo->prepare('INSERT INTO records (callsid, start_time, end_time, from_number, to_number, payload,
            duration, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')->execute([
            $sid, gmdate('Y-m-d H:i:s', $t), gmdate('Y-m-d H:i:s', $t + $duration), $caller, self::HELPLINE,
            json_encode($payload), $duration, $type,
        ]);
        $this->pdo->prepare('INSERT INTO sessions (callsid, timestamp, pin) VALUES (?, ?, ?)')
            ->execute([$sid, gmdate('Y-m-d H:i:s', $t), mt_rand(1000000, 9999999)]);

        $at = $t;
        $event = function (int $eventId, ?array $meta, ?int $serviceBody = null) use ($sid, &$at, $sb, $type) {
            $at += mt_rand(2, 40);
            $this->pdo->prepare('INSERT INTO records_events (callsid, event_time, event_id, service_body_id, meta, type)
                VALUES (?, ?, ?, ?, ?, ?)')->execute([
                $sid, gmdate('Y-m-d H:i:s', $at), $eventId, $serviceBody === 0 ? null : ($serviceBody ?? $sb),
                $meta === null ? null : json_encode($meta), $type,
            ]);
        };

        switch ($flow) {
            case 'answered':
            case 'rejected':
                $event(29, ['stir_verstat' => 'TN-Validation-Passed-A']);
                $event(1, $located);
                $event(15, ['helpline_number' => self::HELPLINE, 'extension' => 'w']);
                $conference = $this->sid('CF');
                $volunteerSid = $this->sid('CA');
                $this->participant($conference, $sid, $sb, 1, $at);
                $event(10, null);
                $event(5, ['to_number' => $volunteer]);
                if ($flow === 'rejected') {
                    $event(7, ['digits' => '2', 'to_number' => $volunteer]);
                    $event(13, null);
                    break;
                }
                $event(6, ['to_number' => $volunteer]);
                $this->participant($conference, $volunteerSid, $sb, 2, $at);
                $event(12, ['to_number' => $volunteer]);
                $event(11, null);
                break;
            case 'meeting':
                $event(14, $located);
                $event(2, null);
                break;
            case 'root_meeting':
                // No service body chosen yet: service_body_id is NULL.
                $event(14, $located, 0);
                $event(2, null, 0);
                break;
            case 'jft':
                $event(3, null);
                break;
            case 'voicemail':
                $event(1, ['gather' => 'Cary', 'coordinates' => null]);
                $event(5, ['to_number' => $volunteer]);
                $event(8, ['to_number' => $volunteer]);
                $event(4, ['url' => sprintf(
                    'https://api.twilio.com/2010-04-01/Accounts/%s/Recordings/%s',
                    $this->account,
                    $this->sid('RE')
                )]);
                if ($i % 24 === 6) {
                    // Deleted in the 4.5.3 Voicemail page.
                    $this->pdo->prepare('INSERT INTO event_status (callsid, status, event_id, created_at, updated_at)
                        VALUES (?, 1, 4, ?, ?)')->execute([$sid, gmdate('Y-m-d H:i:s', $at + 86400),
                        gmdate('Y-m-d H:i:s', $at + 86400)]);
                }
                break;
            case 'sms_meeting':
                $event(19, null);
                break;
            case 'sms_volunteer':
                $event(20, null);
                $event(5, ['to_number' => $volunteer]);
                break;
        }
    }

    private function participant(string $conference, string $callSid, int $sb, int $role, int $at): void
    {
        $this->pdo->prepare('INSERT INTO conference_participants (timestamp, conferencesid, callsid, friendlyname, role)
            VALUES (?, ?, ?, ?, ?)')->execute([gmdate('Y-m-d H:i:s', $at), $conference, $callSid,
            sprintf('%d_%d_%d', $sb, mt_rand(100000, 999999), $at), $role]);
    }

    private function sid(string $prefix): string
    {
        return $prefix . substr(hash('sha256', 'standin-' . (++$this->sidCounter)), 0, 32);
    }

    // --- everything else -----------------------------------------------------

    private function misc(): void
    {
        // STATUS_CALLBACK_MISSING: the payload is the helpline number.
        $this->pdo->prepare('INSERT INTO alerts (timestamp, alert_id, payload, status) VALUES (?, 1, ?, NULL)')
            ->execute(['2025-12-24 04:00:00', '+19195550100']);
        // Legacy table; nothing reads it any more.
        $this->pdo->prepare('INSERT INTO metrics (timestamp, data, service_body_id) VALUES (?, ?, ?)')
            ->execute(['2023-11-11 11:11:11', '{"searchType":"1"}', self::SB_A]);
        // Something cached that would hold a caller's location.
        $this->pdo->prepare('INSERT INTO cache (`key`, value, expiry) VALUES (?, ?, ?)')
            ->execute(['geocode:Raleigh, NC', '{"lat":35.7796,"lng":-78.6382}', 1700000000]);
        // Pre-Laravel schema versions on long-running installs.
        foreach (['1', '2', '3'] as $version) {
            $this->pdo->prepare('INSERT INTO migrations (version, timestamp) VALUES (?, ?)')
                ->execute([$version, '2019-05-05 05:05:05']);
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $database = $argv[1] ?? '';
    if ($database === '') {
        fwrite(STDERR, "usage: php seed-standin.php <database>\n");
        exit(2);
    }
    (new StandinSeeder(upgrade_pdo($database)))->run();
}
