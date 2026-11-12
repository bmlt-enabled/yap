<?php
/**
 * Sanitize a copy of a yap 4.5.x database in place, so it can be committed as
 * the upgrade-test fixture (#1684). See tests/Fixtures/upgrade/README.md.
 *
 * NEVER point this at a live database: it rewrites every row.
 *
 *   php tests/Upgrade/sanitize-database.php <database>
 *
 * Connection settings (host, port, user, password) come from config.test.php
 * or $UPGRADE_CONFIG; <database> must be named explicitly.
 *
 * What it does, keeping every table, row count and row shape:
 *   - users: username -> user<id>, name -> "User <id>" (or the new username
 *     when name equalled username, so BMLT-shaped rows keep that shape, and
 *     "User<id>" when they were equal but for case),
 *     and every non-empty password -> SHA-256 of a known password
 *     ("yap-upgrade-<new username>"), so the checks can log in as 4.5.3 did.
 *   - phone numbers -> fake numbers, emails -> @example.invalid, Twilio SIDs
 *     -> fake SIDs. Each value maps to the same fake everywhere in the run,
 *     so callsids still join records, records_events, conference_participants
 *     and sessions, and a volunteer's number still matches their calls.
 *   - names (volunteer_name, group_name, caller names, ...), helpline titles,
 *     free-text notes, voicemail transcriptions, caller city / zip -> placeholders.
 *   - URLs on any host but Twilio's -> https://example.invalid/sanitized.<ext>
 *     (a custom greeting hosted on the install's own site names the install).
 *   - credentials in service body settings (tokens, passwords, keys) ->
 *     "redacted"; caller coordinates are rounded to 1 decimal (~10 km).
 *   - cache is emptied (it can hold geocoded caller addresses).
 * Then it scans every text column for anything that still looks like a phone
 * number, an email or a non-Twilio URL and fails if it finds one, naming the
 * column and a masked shape (never the value).
 *
 * The mapping key is random per run and never stored, so fake values cannot
 * be reversed by hashing candidate phone numbers.
 */

namespace Yap\UpgradeTest;

use PDO;

require_once __DIR__ . '/db-env.php';

final class Sanitizer
{
    /** JSON keys whose string values are personal names or free text. */
    private const NAME_KEYS = '/^(volunteer_name|group_name|name|friendlyname|caller_?name|callername|'
        . 'from_?name|to_?name|contact_?name|primary_contact_name|notes?|volunteer_notes|'
        . 'transcription(_?text)?|transcript|body|message|speechresult|title)$/i';

    /** JSON keys whose values are credentials. */
    private const SECRET_KEYS = '/(token|password|passwd|secret|api_?key|private_?key)$/i';

    /** JSON keys with caller location ("gather" is what a caller typed or said). */
    private const CITY_KEYS = '/(city|street|address|^location|^gather)$/i';
    private const ZIP_KEYS = '/(zip|postal_?code)$/i';
    private const COORD_KEYS = '/^(lat|lng|lon|long|latitude|longitude)$/i';

    /** Columns that are phone numbers outright (an alert's payload is the number). */
    private const PHONE_COLUMNS = ['records.from_number', 'records.to_number', 'alerts.payload'];

    /** JSON keys whose values are phone numbers. */
    private const PHONE_KEYS = '/phone|_number$|^(from|to|caller|called|forwardedfrom|callerid|caller_id|'
        . 'forced_caller_id|primary_contact|helpline_number|number|helpline)$/i';

    private const SID_PATTERN = '/\b(AC|CA|CF|RE|SM|MM|PN|QU|TR|TK|SK|AP|MG|WS|FN|CL)([0-9a-fA-F]{32})\b/';
    private const EMAIL_PATTERN = '/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/';
    /**
     * Any http(s) URL. A custom greeting or hold music is often hosted on the
     * install's own site, which identifies it; Twilio's own URLs (recordings)
     * carry nothing but SIDs, which are faked separately. twimlets.com is
     * Twilio's too: it serves the default hold music every service body
     * starts with, so faking it would rewrite nearly every install's moh.
     */
    private const URL_PATTERN = '#\bhttps?://([A-Za-z0-9.\-]+)(:\d+)?([^\s"\'<>,]*)#i';
    private const KEPT_URL_HOSTS = '/(^|\.)(twilio\.com|twimlets\.com|example\.(invalid|com|org))$/i';
    // E.164 (Twilio's format, also URL-encoded), then common NANP spellings.
    private const PHONE_PATTERNS = [
        '/(?<![\w%])\+\d{8,15}\b/',
        '/%2B\d{8,15}\b/i',
        '/\(\d{3}\)\s?\d{3}[\s.\-]\d{4}\b/',
        '/(?<![\w\-.])(?:1[\s.\-])?\d{3}[\s.\-]\d{3}[\s.\-]\d{4}\b/',
    ];

    private string $key;
    /** @var array<string, string> */
    private array $phones = [];
    /** @var array<string, string> */
    private array $emails = [];
    /** @var array<string, string> */
    private array $names = [];
    private array $stats = [];

    public function __construct(private PDO $pdo, private string $database)
    {
        $this->key = random_bytes(32);
    }

    public function run(): void
    {
        $this->pdo->exec('SET SESSION sql_mode = ""');
        $this->sanitizeUsers();
        if ($this->hasTable('cache')) {
            $this->stats['cache rows deleted'] = $this->pdo->exec('DELETE FROM `cache`');
        }
        foreach ($this->tables() as $table) {
            if (in_array($table, ['users', 'cache', 'migrations', 'migrations_v2'], true)) {
                continue;
            }
            $this->sanitizeTable($table);
        }
        $leaks = $this->scanForLeaks();
        foreach ($this->stats as $what => $count) {
            printf("  %-40s %d\n", $what, $count);
        }
        if ($leaks) {
            fwrite(STDERR, "Possible personal data left after sanitizing:\n  " . implode("\n  ", $leaks) . "\n");
            exit(1);
        }
        echo "Sanitized {$this->database}: " . count($this->phones) . " phone numbers, "
            . count($this->emails) . " emails, " . count($this->names) . " names replaced\n";
    }

    // --- users --------------------------------------------------------------

    private function sanitizeUsers(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }
        $rows = $this->pdo->query('SELECT id, name, username, password FROM users ORDER BY id')->fetchAll();
        // Two passes so renaming row 2 to "user5" cannot collide with row 5's old username.
        $this->pdo->exec("UPDATE users SET username = CONCAT('__sanitizing_', id)");
        $update = $this->pdo->prepare('UPDATE users SET name = ?, username = ?, password = ? WHERE id = ?');
        foreach ($rows as $row) {
            $username = 'user' . $row['id'];
            // Keep how name compares to username, both byte-for-byte and under
            // the column's case-insensitive collation (5.0's auth_source
            // migration compares them in SQL): equal stays equal, equal but
            // for case stays that way ("Admin" / "admin" -> "User1" / "user1").
            $name = match (true) {
                $row['name'] === $row['username'] => $username,
                mb_strtolower($row['name']) === mb_strtolower($row['username']) => ucfirst($username),
                default => 'User ' . $row['id'],
            };
            $password = (string) $row['password'] === '' ? '' : hash('sha256', self::knownPassword($username));
            $update->execute([$name, $username, $password, $row['id']]);
        }
        $this->stats['users'] = count($rows);
    }

    /** 4.5.3's password format: unsalted SHA-256, 64 hex digits. */
    public static function isLegacySha256(string $hash): bool
    {
        return preg_match('/\A[0-9a-f]{64}\z/i', $hash) === 1;
    }

    /** The 4.5.3 password every sanitized local user can log in with. */
    public static function knownPassword(string $username): string
    {
        return 'yap-upgrade-' . $username;
    }

    // --- generic tables -----------------------------------------------------

    private function sanitizeTable(string $table): void
    {
        $columns = $this->textColumns($table);
        if (!$columns) {
            return;
        }
        $key = $this->primaryKey($table);
        $select = 'SELECT ' . implode(', ', array_map(fn ($c) => "`$c`", array_unique(array_merge($key, $columns))))
            . " FROM `$table`";
        $changed = 0;
        foreach ($this->pdo->query($select)->fetchAll() as $row) {
            $set = [];
            foreach ($columns as $column) {
                if ($row[$column] === null || $row[$column] === '') {
                    continue;
                }
                $new = in_array("$table.$column", self::PHONE_COLUMNS, true)
                    ? $this->fakePhone($row[$column])
                    : $this->sanitizeValue($row[$column]);
                if ($new !== $row[$column]) {
                    $set[$column] = $new;
                }
            }
            if ($set) {
                $this->updateRow($table, $key, $row, $set);
                $changed++;
            }
        }
        $this->stats["$table rows changed"] = $changed;
    }

    private function updateRow(string $table, array $key, array $row, array $set): void
    {
        $sql = "UPDATE `$table` SET " . implode(', ', array_map(fn ($c) => "`$c` = ?", array_keys($set)));
        $params = array_values($set);
        if ($key) {
            $sql .= ' WHERE ' . implode(' AND ', array_map(fn ($c) => "`$c` <=> ?", $key));
            foreach ($key as $c) {
                $params[] = $row[$c];
            }
        } else {
            // No primary key (sessions, cache_records_conference_participants):
            // match on every column we read.
            $sql .= ' WHERE ' . implode(' AND ', array_map(fn ($c) => "`$c` <=> ?", array_keys($row)));
            foreach ($row as $value) {
                $params[] = $value;
            }
            $sql .= ' LIMIT 1';
        }
        $this->pdo->prepare($sql)->execute($params);
    }

    /** A column value: JSON (possibly JSON inside JSON), PHP-serialized, or plain text. */
    public function sanitizeValue(string $value): string
    {
        $trimmed = ltrim($value);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode($this->walk($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        return $this->scrubText($value);
    }

    private function walk(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->walk($value);
            } elseif (is_string($value) || is_int($value) || is_float($value)) {
                $data[$key] = $this->sanitizeField((string) $key, $value);
            }
        }
        return $data;
    }

    private function sanitizeField(string $key, string|int|float $value): string|int|float
    {
        if (is_string($value) && $value === '') {
            return $value;
        }
        if (preg_match(self::COORD_KEYS, $key) && is_numeric($value)) {
            $rounded = round((float) $value, 1);
            return is_string($value) ? (string) $rounded : $rounded;
        }
        if (!is_string($value)) {
            return $value;
        }
        // Nested JSON in a string (e.g. a payload stored as a JSON string).
        $trimmed = ltrim($value);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode($this->walk($decoded), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        if (preg_match(self::SECRET_KEYS, $key) && !preg_match('/^(auth_?source|auth_?mechanism)$/i', $key)) {
            return 'redacted';
        }
        if (preg_match(self::PHONE_KEYS, $key) && preg_match('/\d{7,}|\d{3}[\s.\-)]\s?\d{3}/', $value)) {
            return $this->fakePhone($value);
        }
        if (preg_match(self::NAME_KEYS, $key) && !preg_match('/^\d+$/', $value)) {
            return $this->fakeName($key, $value);
        }
        if (preg_match(self::ZIP_KEYS, $key)) {
            return '00000';
        }
        if (preg_match(self::CITY_KEYS, $key)) {
            return 'Anytown';
        }
        return $this->scrubText($value);
    }

    /** Free text: replace anything that looks like a SID, URL, email or phone number. */
    public function scrubText(string $value): string
    {
        $value = preg_replace_callback(self::SID_PATTERN, fn ($m) => $this->fakeSid($m[1], $m[2]), $value);
        $value = preg_replace_callback(self::URL_PATTERN, fn ($m) => $this->fakeUrl($m[0], $m[1], $m[3]), $value);
        $value = preg_replace_callback(self::EMAIL_PATTERN, fn ($m) => $this->fakeEmail($m[0]), $value);
        foreach (self::PHONE_PATTERNS as $pattern) {
            $value = preg_replace_callback($pattern, fn ($m) => $this->fakePhone($m[0]), $value);
        }
        return $value;
    }

    // --- fakes --------------------------------------------------------------

    private function fakeSid(string $prefix, string $hex): string
    {
        // Already fake (the stand-in fixture uses a zero-padded account SID).
        if (preg_match('/^0+[0-9a-f]{0,4}$/i', $hex)) {
            return $prefix . $hex;
        }
        return $prefix . substr(hash_hmac('sha256', strtolower($prefix . $hex), $this->key), 0, 32);
    }

    /**
     * Replace the digits of a phone number, keeping its punctuation and length:
     * "+12125551234" -> "+15550000003", "212-555-1234" -> "555-000-0003".
     * Every spelling of one number maps to the same fake.
     */
    public function fakePhone(string $value): string
    {
        $prefix = '';
        if (stripos($value, '%2B') === 0) {
            $prefix = substr($value, 0, 3);
            $value = substr($value, 3);
        }
        $digits = preg_replace('/\D/', '', $value);
        // Too short to be a number, or SpecialPhoneNumber::UNKNOWN ("0000000000").
        if (strlen($digits) < 7 || trim($digits, '0') === '') {
            return $prefix . $value;
        }
        // NANP: key on the 10-digit number so +1 and bare spellings match.
        $core = (strlen($digits) === 11 && $digits[0] === '1') ? substr($digits, 1) : $digits;
        if (str_starts_with($core, '555') && strlen($core) === 10) {
            return $prefix . $value; // already a fictional number
        }
        if (!isset($this->phones[$core])) {
            $n = count($this->phones) + 1;
            $this->phones[$core] = strlen($core) === 10
                ? '555' . sprintf('%07d', $n)
                : str_pad((string) $n, strlen($core), '9', STR_PAD_LEFT);
        }
        $fake = $this->phones[$core];
        if (strlen($digits) !== strlen($core)) {
            $fake = substr($digits, 0, strlen($digits) - strlen($core)) . $fake;
        }
        // Put the fake digits back into the original punctuation.
        $i = 0;
        $out = preg_replace_callback('/\d/', function () use ($fake, &$i) {
            return $fake[$i++] ?? '0';
        }, $value);
        return $prefix . $out;
    }

    /**
     * A URL on the install's own (or any third-party) host becomes one on
     * example.invalid, keeping the file extension so an audio URL still looks
     * like one: "https://region.org/x/greeting.mp3" -> "https://example.invalid/sanitized.mp3".
     */
    private function fakeUrl(string $url, string $host, string $rest): string
    {
        if (preg_match(self::KEPT_URL_HOSTS, $host)) {
            return $url;
        }
        $path = (string) parse_url('http://h' . $rest, PHP_URL_PATH);
        $ext = preg_match('/\.([A-Za-z0-9]{1,5})$/', $path, $m) ? '.' . strtolower($m[1]) : '';
        $this->stats['URLs replaced'] = ($this->stats['URLs replaced'] ?? 0) + 1;
        return (stripos($url, 'https') === 0 ? 'https' : 'http') . '://example.invalid/sanitized' . $ext;
    }

    private function fakeEmail(string $email): string
    {
        $lower = strtolower($email);
        if (str_ends_with($lower, '@example.invalid') || str_ends_with($lower, '@example.com')) {
            return $email;
        }
        return $this->emails[$lower] ??= 'person' . (count($this->emails) + 1) . '@example.invalid';
    }

    private function fakeName(string $key, string $value): string
    {
        $k = strtolower($key);
        $label = match (true) {
            str_contains($k, 'group') => 'Group',
            str_contains($k, 'volunteer_notes'), $k === 'notes', $k === 'note' => 'Notes',
            str_contains($k, 'transcri') => 'Transcription',
            in_array($k, ['body', 'message', 'speechresult'], true) => 'Message',
            str_contains($k, 'friendlyname') => 'Participant',
            $k === 'title' => 'Helpline',
            default => 'Person',
        };
        if (in_array($label, ['Notes', 'Transcription', 'Message'], true)) {
            return "Sanitized {$label}";
        }
        return $this->names[$label . "\0" . $value] ??= $label . ' ' . (count($this->names) + 1);
    }

    // --- schema helpers -----------------------------------------------------

    private function tables(): array
    {
        $stmt = $this->pdo->prepare("SELECT table_name AS t FROM information_schema.tables
            WHERE table_schema = ? AND table_type = 'BASE TABLE' ORDER BY table_name");
        $stmt->execute([$this->database]);
        return array_column($stmt->fetchAll(), 't');
    }

    private function hasTable(string $table): bool
    {
        return in_array($table, $this->tables(), true);
    }

    private function textColumns(string $table): array
    {
        $stmt = $this->pdo->prepare("SELECT column_name AS c FROM information_schema.columns
            WHERE table_schema = ? AND table_name = ?
              AND data_type IN ('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'json')
            ORDER BY ordinal_position");
        $stmt->execute([$this->database, $table]);
        return array_column($stmt->fetchAll(), 'c');
    }

    private function primaryKey(string $table): array
    {
        $stmt = $this->pdo->prepare("SELECT column_name AS c FROM information_schema.key_column_usage
            WHERE table_schema = ? AND table_name = ? AND constraint_name = 'PRIMARY'
            ORDER BY ordinal_position");
        $stmt->execute([$this->database, $table]);
        return array_column($stmt->fetchAll(), 'c');
    }

    /**
     * Anything left that looks like a real phone number, email or install URL.
     * Reported as table.column plus a masked shape, never the value itself:
     * this runs against real data and its output lands in terminals and logs.
     */
    private function scanForLeaks(): array
    {
        $leaks = [];
        foreach ($this->tables() as $table) {
            if (in_array($table, ['migrations', 'migrations_v2'], true)) {
                continue;
            }
            foreach ($this->textColumns($table) as $column) {
                $stmt = $this->pdo->query("SELECT `$column` AS v FROM `$table` WHERE `$column` IS NOT NULL");
                foreach ($stmt->fetchAll() as $row) {
                    $v = (string) $row['v'];
                    if (preg_match_all(self::EMAIL_PATTERN, $v, $m)) {
                        foreach ($m[0] as $email) {
                            if (!preg_match('/@example\.(invalid|com)$/i', $email)) {
                                $leaks[] = "$table.$column: email " . self::mask($email);
                            }
                        }
                    }
                    if (preg_match_all(self::URL_PATTERN, $v, $m)) {
                        foreach ($m[1] as $host) {
                            if (!preg_match(self::KEPT_URL_HOSTS, $host)) {
                                $leaks[] = "$table.$column: url on host " . self::mask($host);
                            }
                        }
                    }
                    foreach (self::PHONE_PATTERNS as $pattern) {
                        preg_match_all($pattern, $v, $m);
                        foreach ($m[0] as $phone) {
                            $digits = preg_replace('/\D/', '', preg_replace('/^%2B/i', '', $phone));
                            $core = (strlen($digits) === 11 && $digits[0] === '1') ? substr($digits, 1) : $digits;
                            if (!str_starts_with($core, '555') && !$this->isKnownFake($core)) {
                                $leaks[] = "$table.$column: number " . self::mask($phone);
                            }
                        }
                    }
                }
            }
        }
        return array_slice(array_unique($leaks), 0, 50);
    }

    /** "+12125551234" -> "+99999999999", "a.b@c.org" -> "a.a@a.aaa": the shape, not the value. */
    public static function mask(string $value): string
    {
        return preg_replace(['/\d/', '/[^\W\d_]/u'], ['9', 'a'], $value);
    }

    private function isKnownFake(string $core): bool
    {
        return in_array($core, $this->phones, true);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $database = $argv[1] ?? '';
    if ($database === '') {
        fwrite(STDERR, "usage: php sanitize-database.php <database>\n");
        exit(2);
    }
    $live = upgrade_db_settings()['database'];
    echo "== sanitize {$database}" . ($database === $live ? " (the database config.test.php names)" : '') . "\n";
    (new Sanitizer(upgrade_pdo($database), $database))->run();
}
