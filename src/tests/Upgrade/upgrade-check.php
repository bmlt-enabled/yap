<?php
/**
 * Checks for the 4.5.3 -> packaged release upgrade test (#1684). Driven by
 * .github/scripts/upgrade-test.sh; plain PHP + PDO + curl, no Laravel, so it
 * checks the package from the outside.
 *
 *   php upgrade-check.php snapshot <before.json>
 *       Read the restored 4.5.3 database (before any 5.0 code has touched it)
 *       and record what the upgraded app must still return. The expected
 *       report numbers are computed with 4.5.3's own report SQL, so "reports
 *       return the historical calls" means "the same numbers 4.5.3 showed".
 *
 *   php upgrade-check.php check <before.json> <base-url> <package-dir>
 *       Make the first web request (which runs the migrations), then check
 *       the database and the admin API against the snapshot.
 *
 * Nothing here is specific to the stand-in fixture: every expectation comes
 * from the dump, so a real sanitized 4.5.3 dump drops in unchanged. The only
 * contract with the dump is the one sanitize-database.php establishes: each
 * local user's password is "yap-upgrade-<username>".
 */

namespace Yap\UpgradeTest;

use PDO;

require_once __DIR__ . '/db-env.php';
require_once __DIR__ . '/sanitize-database.php';

final class UpgradeCheck
{
    /** The event ids 4.5.3's metrics summary counts (ReportsRepository::getMetricCounts). */
    private const SUMMARY_EVENTS = '1,2,3,12,19,20,21,23,24';
    private const DAY_NAMES = [1 => 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    private PDO $pdo;
    private int $failures = 0;
    private int $passes = 0;
    private array $responses = [];

    public function __construct()
    {
        $this->pdo = upgrade_pdo();
    }

    // =========================================================================
    // snapshot
    // =========================================================================

    public function snapshot(string $out): void
    {
        $tables = $this->tables();
        $snapshot = [
            'tables' => [],
            'migrations' => $this->migrationsRan(),
            'users' => $this->pdo->query('SELECT * FROM users ORDER BY id')->fetchAll(),
            'config' => $this->pdo->query("SELECT id, service_body_id, data, data_type, parent_id, status
                FROM config WHERE IFNULL(status, 0) <> 1 ORDER BY id")->fetchAll(),
            'service_bodies' => [],
            'date_range' => null,
        ];
        foreach ($tables as $table) {
            $snapshot['tables'][$table] = (int) $this->pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        }

        $range = $this->pdo->query('SELECT MIN(start_time) AS a, MAX(start_time) AS b FROM records')->fetch();
        $eventRange = $this->pdo->query('SELECT MIN(event_time) AS a, MAX(event_time) AS b FROM records_events')->fetch();
        if ($range['a'] !== null) {
            $snapshot['date_range'] = [
                substr(min($range['a'], $eventRange['a'] ?? $range['a']), 0, 10) . ' 00:00:00',
                substr(max($range['b'], $eventRange['b'] ?? $range['b']), 0, 10) . ' 23:59:59',
            ];
        }

        $ids = $this->pdo->query('SELECT DISTINCT IFNULL(service_body_id, 0) FROM records_events
            UNION SELECT DISTINCT service_body_id FROM config')->fetchAll(PDO::FETCH_COLUMN);
        sort($ids);
        foreach ($ids as $id) {
            $snapshot['service_bodies'][(string) $id] = $this->serviceBodySnapshot((int) $id, $snapshot['date_range']);
        }

        file_put_contents($out, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        printf(
            "Snapshot: %d tables, %d migrations ran, %d users, %d config rows, %d service bodies, calls %s\n",
            count($snapshot['tables']),
            count($snapshot['migrations']),
            count($snapshot['users']),
            count($snapshot['config']),
            count($snapshot['service_bodies']),
            $snapshot['date_range'] ? implode(' .. ', $snapshot['date_range']) : 'none'
        );
    }

    private function serviceBodySnapshot(int $id, ?array $range): array
    {
        $sb = ['calls' => [], 'summary' => [], 'voicemails' => []];
        if ($range === null) {
            return $sb;
        }
        // Calls with an event in this service body (NULL counts as 0, as in 4.5.3).
        $stmt = $this->pdo->prepare('SELECT DISTINCT r.callsid FROM records r
            JOIN records_events e ON e.callsid = r.callsid
            WHERE IFNULL(e.service_body_id, 0) = ? AND r.start_time >= ? AND r.start_time <= ?
            ORDER BY r.callsid');
        $stmt->execute([$id, $range[0], $range[1]]);
        $sb['calls'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 4.5.3's ReportsRepository::getMetricCounts, verbatim.
        $stmt = $this->pdo->prepare('select event_id, count(a.event_id) as counts from records_events a
            INNER JOIN (select callsid, IFNULL(service_body_id, 0) as service_body_id from records_events
            where event_time >= ? AND event_time <= ?
            group by callsid, service_body_id) b on a.callsid = b.callsid
            WHERE a.event_id in (' . self::SUMMARY_EVENTS . ') and IFNULL(b.service_body_id,0) in (?)
            GROUP BY a.event_id ORDER BY a.event_id');
        $stmt->execute([$range[0], $range[1], $id]);
        foreach ($stmt->fetchAll() as $row) {
            $sb['summary'][(string) $row['event_id']] = (int) $row['counts'];
        }

        // Voicemails 4.5.3's Voicemail page listed: event 4, not deleted.
        $stmt = $this->pdo->prepare('SELECT e.callsid, e.meta, r.from_number, s.pin FROM records_events e
            LEFT JOIN records r ON r.callsid = e.callsid
            LEFT JOIN (SELECT callsid, MAX(pin) AS pin FROM sessions GROUP BY callsid) s ON s.callsid = e.callsid
            WHERE e.event_id = 4 AND e.service_body_id = ?
              AND NOT EXISTS (SELECT 1 FROM event_status x WHERE x.callsid = e.callsid AND x.event_id = 4
                  AND x.status = 1)
            ORDER BY e.callsid');
        $stmt->execute([$id]);
        $sb['voicemails'] = $stmt->fetchAll();
        return $sb;
    }

    // =========================================================================
    // check
    // =========================================================================

    public function check(string $beforeFile, string $baseUrl, string $packageDir): void
    {
        $before = json_decode(file_get_contents($beforeFile), true);
        $base = rtrim($baseUrl, '/');

        $this->section('before the first request');
        $this->expect(
            $this->migrationsRan() === $before['migrations'],
            'no migration has run yet (' . count($before['migrations']) . ' from 4.5.3)'
        );

        $this->section('first request');
        $first = $this->request('GET', "$base/ping");
        $this->expect(
            $first['status'] === 200 && str_contains($first['body'], 'PONG'),
            "GET /ping -> {$first['status']}"
        );

        $this->checkMigrations($before, $packageDir);
        $this->checkTablesKept($before);
        $expectedBmlt = $this->checkUsersConverted($before);
        $token = $this->checkLogins($before, $base, $expectedBmlt);
        if ($token === null) {
            $this->finish($packageDir);
            return;
        }
        $this->checkUsersApi($before, $base, $token, $expectedBmlt);
        $this->checkConfig($before, $base, $token);
        $this->checkReports($before, $base, $token);
        $this->checkVoicemail($before, $base, $token);
        $this->finish($packageDir);
    }

    private function checkMigrations(array $before, string $packageDir): void
    {
        $this->section('migrations');
        $files = array_map(
            fn ($f) => basename($f, '.php'),
            glob(rtrim($packageDir, '/') . '/database/migrations/*.php')
        );
        sort($files);
        $ran = $this->migrationsRan();
        $missing = array_values(array_diff($files, $ran));
        $this->expect($missing === [], 'every migration in the package ran (' . count($files) . ')', $missing);
        $this->expect(
            array_values(array_diff($before['migrations'], $ran)) === [],
            '4.5.3 migration rows are kept'
        );
        foreach ([
            'personal_access_tokens', 'laravel_sessions', 'chat_sessions', 'webhook_failures',
            'fake_twilio_calls',
        ] as $table) {
            $this->expect(in_array($table, $this->tables(), true), "table $table exists");
        }
        foreach ([
            'users.auth_source', 'records.type', 'records_events.type', 'records_events.meta',
            'conference_participants.role', 'alerts.status', 'cache.expiry', 'metrics.service_body_id',
            'cache_records_conference_participants.service_body_id', 'users.service_bodies', 'config.status',
        ] as $column) {
            [$t, $c] = explode('.', $column);
            $this->expect($this->hasColumn($t, $c), "column $column exists");
        }
    }

    private function checkTablesKept(array $before): void
    {
        $this->section('4.5.3 tables');
        foreach ($before['tables'] as $table => $count) {
            if (in_array($table, ['migrations_v2', 'cache', 'cache_records_conference_participants'], true)) {
                continue; // grow with the upgrade / emptied by the app
            }
            $now = (int) $this->pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $this->expect($now === $count, "$table keeps its $count rows", $now === $count ? null : "now $now");
        }
        $config = $this->pdo->query("SELECT id, service_body_id, data, data_type, parent_id, status
            FROM config WHERE IFNULL(status, 0) <> 1 ORDER BY id")->fetchAll();
        $this->expect($config == $before['config'], 'config rows are byte-for-byte unchanged');
    }

    /** @return array<string, true> usernames the auth_source migration must have converted */
    private function checkUsersConverted(array $before): array
    {
        $this->section('users after the auth_source migration');
        $after = [];
        foreach ($this->pdo->query('SELECT * FROM users ORDER BY id')->fetchAll() as $row) {
            $after[$row['username']] = $row;
        }
        $expectedBmlt = [];
        foreach ($before['users'] as $user) {
            // The rule #1676 uses for rows the 5.0 betas made for BMLT logins.
            $bmltShaped = $user['name'] === $user['username'] && !(int) $user['is_admin']
                && (int) $user['permissions'] === 0;
            $now = $after[$user['username']] ?? null;
            if ($now === null) {
                $this->fail("user {$user['username']} is gone");
                continue;
            }
            foreach (['name', 'is_admin', 'permissions', 'service_bodies', 'created_on'] as $field) {
                if ((string) $now[$field] !== (string) $user[$field]) {
                    $this->fail("user {$user['username']}: $field changed", [$user[$field], $now[$field]]);
                }
            }
            if ($bmltShaped) {
                $expectedBmlt[$user['username']] = true;
                $this->expect(
                    $now['auth_source'] === 'bmlt' && $now['password'] === '',
                    "{$user['username']} (BMLT-shaped) is auth_source=bmlt with no password"
                );
            } else {
                $this->expect(
                    $now['auth_source'] === 'local' && $now['password'] === $user['password'],
                    "{$user['username']} stays local with its 4.5.3 password hash"
                );
            }
        }
        return $expectedBmlt;
    }

    private function checkLogins(array $before, string $base, array $expectedBmlt): ?string
    {
        $this->section('logins with 4.5.3 passwords');
        $adminToken = null;
        foreach ($before['users'] as $user) {
            $username = $user['username'];
            $password = Sanitizer::knownPassword($username);
            if (isset($expectedBmlt[$username])) {
                // BMLT login is off in config.test.php, so a BMLT row cannot log in at all.
                $r = $this->login($base, $username, $password);
                $this->expect(
                    $r['status'] === 401,
                    "$username (converted to BMLT) is refused locally",
                    "HTTP {$r['status']}"
                );
                continue;
            }
            if ((string) $user['password'] === '') {
                continue;
            }
            $wrong = $this->login($base, $username, $password . '-wrong');
            $this->expect($wrong['status'] === 401, "$username: wrong password refused", "HTTP {$wrong['status']}");

            $r = $this->login($base, $username, $password);
            $token = $r['json']['token'] ?? null;
            $this->expect(
                $r['status'] === 200 && $token,
                "$username logs in with its 4.5.3 password",
                "HTTP {$r['status']}"
            );
            $hash = (string) $this->pdo->query('SELECT password FROM users WHERE username = '
                . $this->pdo->quote($username))->fetchColumn();
            $this->expect(
                !Sanitizer::isLegacySha256($hash) && password_get_info($hash)['algo'] !== null,
                "$username: password re-hashed from SHA-256 (" . (password_get_info($hash)['algoName'] ?? '?') . ')'
            );
            $again = $this->login($base, $username, $password);
            $this->expect($again['status'] === 200, "$username logs in again with the re-hashed password");
            if ($token && (int) $user['is_admin'] === 1 && $adminToken === null) {
                $adminToken = $token;
                $me = $this->request('GET', "$base/api/v1/user", $token);
                $this->expect(($me['json']['username'] ?? null) === $username, "token works for $username");
            }
        }
        if ($adminToken === null) {
            $this->fail('no local is_admin=1 user could log in; the dump must keep one (see README)');
        }
        return $adminToken;
    }

    private function checkUsersApi(array $before, string $base, string $token, array $expectedBmlt): void
    {
        $this->section('GET /api/v1/users');
        $r = $this->request('GET', "$base/api/v1/users", $token);
        $got = [];
        foreach ((array) $r['json'] as $u) {
            $got[$u['username']] = $u;
        }
        foreach ($before['users'] as $user) {
            $u = $got[$user['username']] ?? null;
            $same = $u !== null
                && (string) $u['name'] === (string) $user['name']
                && (int) $u['is_admin'] === (int) $user['is_admin']
                && (int) $u['permissions'] === (int) $user['permissions']
                && (string) $u['service_bodies'] === (string) $user['service_bodies'];
            $this->expect($same, "users lists {$user['username']} unchanged", $u);
        }
    }

    private function checkConfig(array $before, string $base, string $token): void
    {
        $byId = [];
        foreach ($before['config'] as $row) {
            $byId[(int) $row['id']] = $row;
        }

        foreach ($before['config'] as $row) {
            $sb = (int) $row['service_body_id'];
            $data = json_decode($row['data'], true);
            switch ($row['data_type']) {
                case '_YAP_VOLUNTEERS_V2_':
                    $this->section("volunteers for service body $sb");
                    $r = $this->request('GET', "$base/api/v1/volunteers?serviceBodyId=$sb", $token);
                    $this->expect((int) ($r['json']['id'] ?? 0) === (int) $row['id'], 'returns the 4.5.3 row');
                    $this->expectSame(
                        $this->decodeVolunteers($data),
                        $r['json']['data'] ?? null,
                        'volunteers read back unchanged (shift schedules decoded)'
                    );
                    $this->checkSchedule($base, $token, $sb, $data, $before['config']);
                    break;
                case '_YAP_GROUP_VOLUNTEERS_V2_':
                    $group = (int) $row['parent_id'];
                    $this->section("volunteers of group $group");
                    $r = $this->request('GET', "$base/api/v1/groups/volunteers?groupId=$group", $token);
                    $this->expectSame(
                        $this->decodeVolunteers($data),
                        $r['json']['data'] ?? null,
                        'group volunteers read back unchanged'
                    );
                    break;
                case '_YAP_GROUPS_V2_':
                    $this->section("group {$row['id']} of service body $sb");
                    $r = $this->request('GET', "$base/api/v1/groups?serviceBodyId=$sb&manage=1", $token);
                    $found = null;
                    foreach ((array) $r['json'] as $g) {
                        if ((int) ($g['id'] ?? 0) === (int) $row['id']) {
                            $found = $g;
                        }
                    }
                    $this->expectSame($data, $found['data'] ?? null, 'group reads back unchanged');
                    foreach ($data[0]['group_shared_service_bodies'] ?? [] as $shared) {
                        $r = $this->request('GET', "$base/api/v1/groups?serviceBodyId=$shared", $token);
                        $ids = array_map(fn ($g) => (int) $g['id'], (array) $r['json']);
                        $this->expect(in_array((int) $row['id'], $ids, true), "still shared with $shared");
                    }
                    break;
                case '_YAP_CALL_HANDLING_V2_':
                    $this->section("call handling for service body $sb");
                    $r = $this->request('GET', "$base/api/v1/callHandling?serviceBodyId=$sb", $token);
                    $this->expectSame($data, $r['json']['data'] ?? null, 'call handling reads back unchanged');
                    break;
                case '_YAP_CONFIG_V2_':
                    $this->section("settings for service body $sb");
                    $r = $this->request('GET', "$base/api/v1/settings/serviceBody/$sb", $token);
                    $expected = [];
                    $settings = (is_array($data) && array_is_list($data)) ? ($data[0] ?? []) : (array) $data;
                    foreach ($settings as $k => $v) {
                        $expected[] = ['setting' => $k, 'value' => $v];
                    }
                    $this->expectSame($expected, $r['json'], 'service body settings read back unchanged');
                    break;
            }
        }
    }

    /** What 5.0's GET volunteers returns for a stored 4.5.3 list. */
    private function decodeVolunteers(array $cards): array
    {
        foreach ($cards as &$card) {
            if (!isset($card['volunteer_shift_schedule'])) {
                continue;
            }
            $shifts = json_decode(base64_decode($card['volunteer_shift_schedule']), true);
            if (is_array($shifts)) {
                foreach ($shifts as &$shift) {
                    $shift['day_name'] = self::DAY_NAMES[(int) $shift['day']] ?? null;
                }
                unset($shift);
                usort($shifts, fn ($a, $b) => (int) $a['day'] <=> (int) $b['day']);
            }
            $card['volunteer_shift_schedule'] = $shifts;
        }
        return $cards;
    }

    /** volunteers/schedule: one entry per shift of every enabled volunteer with a number. */
    private function checkSchedule(string $base, string $token, int $sb, array $cards, array $config): void
    {
        $expanded = [];
        foreach ($cards as $card) {
            if (isset($card['group_id'], $card['group_enabled']) && json_decode($card['group_enabled'])) {
                foreach ($config as $row) {
                    if ($row['data_type'] === '_YAP_GROUP_VOLUNTEERS_V2_'
                        && (int) $row['parent_id'] === (int) $card['group_id']) {
                        array_push($expanded, ...json_decode($row['data'], true));
                        break;
                    }
                }
            } else {
                $expanded[] = $card;
            }
        }
        $expected = [];
        foreach ($expanded as $card) {
            if (empty($card['volunteer_enabled']) || (string) ($card['volunteer_phone_number'] ?? '') === '') {
                continue;
            }
            foreach (json_decode(base64_decode($card['volunteer_shift_schedule']), true) ?? [] as $shift) {
                $expected[] = sprintf(
                    '%s|%s|%d|%s',
                    $card['volunteer_name'],
                    $shift['type'] ?? 'PHONE',
                    (int) $shift['day'],
                    $shift['tz']
                );
            }
        }
        $r = $this->request('GET', "$base/api/v1/volunteers/schedule?service_body_id=$sb", $token);
        $got = [];
        foreach ((array) $r['json'] as $entry) {
            $name = preg_replace('/ \((PHONE|SMS)\).*$/s', '', (string) ($entry['title'] ?? ''));
            $got[] = sprintf(
                '%s|%s|%d|%s',
                $name,
                $entry['type'] ?? '',
                (int) ($entry['weekday_id'] ?? 0),
                $entry['time_zone'] ?? ''
            );
            if (array_key_exists('contact', $entry)) {
                $this->fail('volunteers/schedule exposes volunteer phone numbers');
            }
        }
        sort($expected);
        sort($got);
        $this->expectSame($expected, $got, 'volunteers/schedule has every enabled shift (' . count($expected) . ')');
    }

    private function checkReports(array $before, string $base, string $token): void
    {
        if ($before['date_range'] === null) {
            $this->section('reports');
            $this->pass('no calls in the dump; nothing to report');
            return;
        }
        [$from, $to] = array_map('rawurlencode', $before['date_range']);
        foreach ($before['service_bodies'] as $sb => $expected) {
            if ((int) $sb === 0) {
                continue; // 0 is "every service body you can see", resolved through BMLT
            }
            $this->section("reports for service body $sb");
            $query = "service_body_id=$sb&date_range_start=$from&date_range_end=$to";

            $cdr = $this->request('GET', "$base/api/v1/reports/cdr?$query", $token);
            $got = array_unique(array_column($cdr['json']['data'] ?? [], 'callsid'));
            $missing = array_values(array_diff($expected['calls'], $got));
            $this->expect(
                $cdr['status'] === 200 && $missing === [],
                'cdr returns every historical call (' . count($expected['calls']) . ')',
                $missing
            );

            $metrics = $this->request('GET', "$base/api/v1/reports/metrics?$query", $token);
            $summary = [];
            foreach ($metrics['json']['summary'] ?? [] as $row) {
                $summary[(string) $row['event_id']] = (int) $row['counts'];
            }
            $this->expectSame($expected['summary'], $summary, 'metrics summary matches 4.5.3\'s');

            $map = $this->request('GET', "$base/api/v1/reports/mapmetrics?$query", $token);
            $this->expect($map['status'] === 200, "mapmetrics -> HTTP {$map['status']}");
        }
    }

    private function checkVoicemail(array $before, string $base, string $token): void
    {
        foreach ($before['service_bodies'] as $sb => $expected) {
            if ((int) $sb === 0) {
                continue;
            }
            $this->section("voicemail for service body $sb");
            $r = $this->request('GET', "$base/api/v1/voicemail?serviceBodyId=$sb", $token);
            $want = array_map(fn ($v) => [$v['callsid'], $v['from_number'], $v['meta'],
                $v['pin'] === null ? null : (int) $v['pin']], $expected['voicemails']);
            $got = array_map(fn ($v) => [$v['callsid'], $v['from_number'], $v['meta'],
                $v['pin'] === null ? null : (int) $v['pin']], $r['json']['data'] ?? []);
            usort($want, fn ($a, $b) => strcmp($a[0], $b[0]));
            usort($got, fn ($a, $b) => strcmp($a[0], $b[0]));
            $this->expectSame($want, $got, 'voicemails read back (' . count($want) . ', deleted ones hidden)');
        }
    }

    private function finish(string $packageDir): void
    {
        $this->section('responses');
        $errors = array_filter($this->responses, fn ($r) => $r['status'] >= 500 || $r['status'] === 0);
        $this->expect(
            $errors === [],
            count($this->responses) . ' requests, none returned a 5xx',
            array_values($errors)
        );
        $log = rtrim($packageDir, '/') . '/storage/logs/laravel.log';
        if (is_file($log) && preg_match_all(
            '/^\[[^\]]+\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): .*$/m',
            file_get_contents($log),
            $m
        )) {
            $this->fail('the package logged errors', array_slice($m[0], 0, 10));
        } else {
            $this->pass('no errors in the package\'s laravel.log');
        }

        echo "\n{$this->passes} passed, {$this->failures} failed\n";
        if ($this->failures > 0) {
            exit(1);
        }
    }

    // =========================================================================
    // helpers
    // =========================================================================

    private function login(string $base, string $username, string $password): array
    {
        return $this->request('POST', "$base/api/v1/login", null, ['username' => $username, 'password' => $password]);
    }

    private function request(string $method, string $url, ?string $token = null, ?array $body = null): array
    {
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        if ($token) {
            $headers[] = "Authorization: Bearer $token";
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $body = $raw === false ? curl_error($ch) : (string) $raw;
        $path = parse_url($url, PHP_URL_PATH) . (parse_url($url, PHP_URL_QUERY) ? '?…' : '');
        $this->responses[] = ['status' => $status, 'request' => "$method $path"];
        if ($status >= 500 || $status === 0) {
            fwrite(STDERR, "  !! $method $url -> HTTP $status\n" . substr(strip_tags($body), 0, 1500) . "\n");
        }
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    }

    private function migrationsRan(): array
    {
        if (!in_array('migrations_v2', $this->tables(), true)) {
            return [];
        }
        $ran = $this->pdo->query('SELECT migration FROM migrations_v2')->fetchAll(PDO::FETCH_COLUMN);
        sort($ran);
        return $ran;
    }

    private function tables(): array
    {
        return $this->pdo->query("SELECT table_name FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
        $stmt->execute([$table, $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function section(string $title): void
    {
        echo "\n== $title\n";
    }

    private function expect(bool $ok, string $what, mixed $detail = null): void
    {
        $ok ? $this->pass($what) : $this->fail($what, $detail);
    }

    private function expectSame(mixed $expected, mixed $actual, string $what): void
    {
        if ($expected === $actual) {
            $this->pass($what);
            return;
        }
        $this->fail($what, ['expected' => $expected, 'actual' => $actual]);
    }

    private function pass(string $what): void
    {
        $this->passes++;
        echo "  ok   $what\n";
    }

    private function fail(string $what, mixed $detail = null): void
    {
        $this->failures++;
        echo "  FAIL $what\n";
        if ($detail !== null) {
            $text = is_string($detail) ? $detail : json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo preg_replace('/^/m', '         ', substr($text, 0, 4000)) . "\n";
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $command = $argv[1] ?? '';
    if ($command === 'snapshot' && isset($argv[2])) {
        (new UpgradeCheck())->snapshot($argv[2]);
    } elseif ($command === 'check' && isset($argv[2], $argv[3], $argv[4])) {
        (new UpgradeCheck())->check($argv[2], $argv[3], $argv[4]);
    } else {
        fwrite(STDERR, "usage: php upgrade-check.php snapshot <before.json>\n"
            . "       php upgrade-check.php check <before.json> <base-url> <package-dir>\n");
        exit(2);
    }
}
