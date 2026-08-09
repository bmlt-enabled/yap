<?php

namespace App\Services\Preflight;

use App\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreflightService
{
    private const PHP_MIN_VERSION = '8.2.0';
    private const MYSQL_MIN_VERSION = '8.0.0';
    private const MARIADB_MIN_VERSION = '10.3.0';

    public function __construct(
        private SettingsService $settings,
    ) {
    }

    /**
     * @return array{passed: bool, checks: array<int, array<string, mixed>>}
     */
    public function run(): array
    {
        $checks = [
            $this->checkRequiredSettings(),
            $this->checkTwilioAuthToken(),
            $this->checkTwilioSignatureBypass(),
            $this->checkTrustedProxies(),
            $this->checkSessionDriver(),
            $this->checkAppEnv(),
            $this->checkPhpVersion(),
            $this->checkPhpExtensions(),
            ...$this->checkDatabase(),
        ];

        $passed = collect($checks)->every(
            fn (PreflightCheck $check) => $check->passed || !$check->blocking
        );

        return [
            'passed' => $passed,
            'checks' => array_map(fn (PreflightCheck $check) => $check->toArray(), $checks),
        ];
    }

    /**
     * @return list<PreflightCheck>
     */
    private function checkDatabase(): array
    {
        if (!$this->isDatabaseConfigured()) {
            return [
                PreflightCheck::fail(
                    'database',
                    'Database connection',
                    'MySQL is not configured (mysql_hostname and mysql_database are required).',
                    'Set mysql_hostname, mysql_username, mysql_password, and mysql_database in config.php.',
                ),
            ];
        }

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            return [
                PreflightCheck::fail(
                    'database',
                    'Database connection',
                    'Unable to connect to MySQL: ' . $e->getMessage(),
                    'Verify mysql_hostname, mysql_username, mysql_password, mysql_database, and mysql_port in config.php.',
                ),
            ];
        }

        $checks = [$this->checkMysqlVersion()];

        if (!Schema::hasTable('users')) {
            $checks[] = PreflightCheck::pass(
                'users_table',
                'Users table',
                'Users table not found; skipping user-data checks (fresh install).',
            );

            return $checks;
        }

        return [
            ...$checks,
            $this->checkDuplicateUsernames(),
            $this->checkEmptyUsernames(),
            $this->checkUsersSchema(),
        ];
    }

    private function checkDuplicateUsernames(): PreflightCheck
    {
        $duplicateUsernames = DB::select(
            'SELECT username, COUNT(*) as cnt FROM users GROUP BY username HAVING cnt > 1'
        );

        if (count($duplicateUsernames) === 0) {
            return PreflightCheck::pass(
                'duplicate_usernames',
                'Duplicate usernames',
                'No duplicate usernames found.',
            );
        }

        $examples = collect($duplicateUsernames)->pluck('username')->take(5)->implode(', ');

        return PreflightCheck::fail(
            'duplicate_usernames',
            'Duplicate usernames',
            sprintf('%d duplicate username value(s) found (e.g. %s).', count($duplicateUsernames), $examples),
            'Resolve duplicate usernames before upgrading. Usernames are the stable identifier for local accounts in the admin UI and API.',
        );
    }

    private function checkEmptyUsernames(): PreflightCheck
    {
        $nullOrEmptyUsername = DB::table('users')
            ->where(function ($query) {
                $query->whereNull('username')->orWhere('username', '');
            })
            ->count();

        if ($nullOrEmptyUsername === 0) {
            return PreflightCheck::pass(
                'empty_usernames',
                'Empty usernames',
                'All users have a non-empty username.',
            );
        }

        return PreflightCheck::fail(
            'empty_usernames',
            'Empty usernames',
            sprintf('%d user(s) have a NULL or empty username.', $nullOrEmptyUsername),
            'Assign a unique username to every user row before upgrading. Local admin login and user management require a non-empty username.',
        );
    }

    private function checkUsersSchema(): PreflightCheck
    {
        $createTable = DB::selectOne('SHOW CREATE TABLE users');
        $sql = $createTable->{'Create Table'};

        if (!preg_match('/PRIMARY KEY\s*\(`id`\)/', $sql)) {
            return PreflightCheck::fail(
                'users_schema',
                'Users table schema',
                'users.id does not have a primary key.',
                'Restore a PRIMARY KEY on users.id before upgrading.',
            );
        }

        if (!preg_match('/UNIQUE.*`username`/i', $sql)) {
            return PreflightCheck::fail(
                'users_schema',
                'Users table schema',
                'users.username does not have a unique index.',
                'Add UNIQUE INDEX username_unique (username) on the users table before upgrading. Some older installs lack the index the migration assumes.',
            );
        }

        return PreflightCheck::pass(
            'users_schema',
            'Users table schema',
            'users.id has a primary key and users.username has a unique index.',
        );
    }

    private function checkRequiredSettings(): PreflightCheck
    {
        $missing = [];
        foreach ($this->settings->minimalRequiredSettings() as $setting) {
            if ($setting === 'twilio_auth_token') {
                continue;
            }

            if (!$this->settings->has($setting) || $this->settings->get($setting) === '') {
                $missing[] = $setting;
            }
        }

        if ($missing === []) {
            return PreflightCheck::pass(
                'required_settings',
                'Required settings',
                'All required config.php settings are present.',
            );
        }

        return PreflightCheck::fail(
            'required_settings',
            'Required settings',
            'Missing required setting(s): ' . implode(', ', $missing) . '.',
            'Set each required value in config.php before upgrading.',
        );
    }

    private function checkTwilioSignatureBypass(): PreflightCheck
    {
        if (!config('twilio.disable_signature_validation') || app()->environment('production')) {
            return PreflightCheck::pass(
                'twilio_signature_bypass',
                'Twilio signature bypass',
                'Twilio signature validation is not bypassed.',
            );
        }

        return PreflightCheck::warn(
            'twilio_signature_bypass',
            'Twilio signature bypass',
            'TWILIO_DISABLE_SIGNATURE_VALIDATION is enabled outside production.',
            'Do not set TWILIO_DISABLE_SIGNATURE_VALIDATION on a live helpline.',
        );
    }

    private function checkTrustedProxies(): PreflightCheck
    {
        $trustedProxies = config('trustedproxy.proxies');

        if (!empty($trustedProxies)) {
            return PreflightCheck::pass(
                'trusted_proxies',
                'Trusted proxies',
                'TRUSTED_PROXIES is configured.',
            );
        }

        return PreflightCheck::pass(
            'trusted_proxies',
            'Trusted proxies',
            'TRUSTED_PROXIES is not set — not required for direct Apache/nginx→PHP connections.',
        );
    }

    private function checkTwilioAuthToken(): PreflightCheck
    {
        $token = $this->settings->get('twilio_auth_token');

        if (!empty($token)) {
            return PreflightCheck::pass(
                'twilio_auth_token',
                'Twilio auth token',
                'twilio_auth_token is configured.',
            );
        }

        return PreflightCheck::fail(
            'twilio_auth_token',
            'Twilio auth token',
            'twilio_auth_token is missing or empty.',
            'Set twilio_auth_token in config.php to the Auth Token for the Twilio account that owns your phone numbers. ValidateTwilioSignature rejects every IVR request with HTTP 403 when the token is empty, so all calls will fail.',
        );
    }

    private function checkSessionDriver(): PreflightCheck
    {
        $driver = config('session.driver');

        if ($driver !== 'database') {
            return PreflightCheck::pass(
                'session_driver',
                'Session driver',
                sprintf('SESSION_DRIVER is "%s".', $driver),
            );
        }

        return PreflightCheck::fail(
            'session_driver',
            'Session driver',
            'SESSION_DRIVER is set to "database".',
            'Use file, redis, or another driver instead of database. config/session.php points at the sessions table, but Yap uses that table for call PINs (callsid, timestamp, pin), not Laravel sessions.',
        );
    }

    private function checkAppEnv(): PreflightCheck
    {
        $env = config('app.env');

        if ($env === 'production') {
            return PreflightCheck::pass(
                'app_env',
                'APP_ENV',
                'APP_ENV is "production".',
            );
        }

        if (in_array($env, ['local', 'testing'], true)) {
            return PreflightCheck::warn(
                'app_env',
                'APP_ENV',
                sprintf('APP_ENV is "%s".', $env),
                'Several security guards only apply the strict production behavior when APP_ENV is exactly "production". Set APP_ENV=production on live servers.',
            );
        }

        return PreflightCheck::warn(
            'app_env',
            'APP_ENV',
            sprintf('APP_ENV is "%s".', $env),
            'Yap compares APP_ENV to the exact string "production" for security-sensitive behavior (for example Twilio signature validation bypass rules). Use APP_ENV=production on live servers unless you understand the implications.',
        );
    }

    private function checkPhpVersion(): PreflightCheck
    {
        $current = PHP_VERSION;

        if (version_compare($current, self::PHP_MIN_VERSION, '<')) {
            return PreflightCheck::fail(
                'php_version',
                'PHP version',
                sprintf('PHP %s is below the minimum %s required by composer.json.', $current, self::PHP_MIN_VERSION),
                sprintf('Upgrade PHP to at least %s before deploying Yap 5.0.', self::PHP_MIN_VERSION),
            );
        }

        return PreflightCheck::pass(
            'php_version',
            'PHP version',
            sprintf('PHP %s meets the minimum requirement (%s).', $current, self::PHP_MIN_VERSION),
        );
    }

    private function checkPhpExtensions(): PreflightCheck
    {
        $missing = [];
        foreach (PhpExtensionRequirements::REQUIRED as $extension => $description) {
            if (!extension_loaded($extension)) {
                $missing[$extension] = $description;
            }
        }

        if ($missing === []) {
            return PreflightCheck::pass(
                'php_extensions',
                'PHP extensions',
                sprintf(
                    'All %d required PHP extensions are loaded.',
                    count(PhpExtensionRequirements::REQUIRED),
                ),
            );
        }

        $missingList = collect($missing)
            ->map(fn (string $description, string $extension) => sprintf('%s (%s)', $extension, $description))
            ->implode('; ');

        return PreflightCheck::fail(
            'php_extensions',
            'PHP extensions',
            'Missing required PHP extension(s): ' . implode(', ', array_keys($missing)) . '.',
            sprintf(
                'Enable or install the missing extensions on this server. A common error is "Class \'finfo\' not found" when fileinfo is disabled. Missing: %s. See https://yap.bmlt.app/general/php-requirements for shared-hosting and self-hosted install steps. Verify with: php -m',
                $missingList,
            ),
        );
    }

    private function checkMysqlVersion(): PreflightCheck
    {
        $versionString = DB::selectOne('SELECT VERSION() as version')->version;
        $version = $this->normalizeDatabaseVersion($versionString);

        if (str_contains(strtolower($versionString), 'mariadb')) {
            if (version_compare($version, self::MARIADB_MIN_VERSION, '<')) {
                return PreflightCheck::fail(
                    'mysql_version',
                    'MySQL version',
                    sprintf('MariaDB %s is below the minimum %s.', $version, self::MARIADB_MIN_VERSION),
                    sprintf('Upgrade MariaDB to at least %s before deploying Yap 5.0.', self::MARIADB_MIN_VERSION),
                );
            }

            return PreflightCheck::pass(
                'mysql_version',
                'MySQL version',
                sprintf('MariaDB %s meets the minimum (%s).', $version, self::MARIADB_MIN_VERSION),
            );
        }

        if (version_compare($version, self::MYSQL_MIN_VERSION, '<')) {
            return PreflightCheck::fail(
                'mysql_version',
                'MySQL version',
                sprintf('MySQL %s is below the minimum %s required by Laravel 12.', $version, self::MYSQL_MIN_VERSION),
                sprintf('Upgrade MySQL to at least %s before deploying Yap 5.0.', self::MYSQL_MIN_VERSION),
            );
        }

        return PreflightCheck::pass(
            'mysql_version',
            'MySQL version',
            sprintf('MySQL %s meets the minimum (%s).', $version, self::MYSQL_MIN_VERSION),
        );
    }

    private function normalizeDatabaseVersion(string $versionString): string
    {
        if (preg_match('/(\d+\.\d+\.\d+)/', $versionString, $matches)) {
            return $matches[1];
        }

        return $versionString;
    }

    private function isDatabaseConfigured(): bool
    {
        return !empty($this->settings->get('mysql_hostname'))
            && !empty($this->settings->get('mysql_database'));
    }
}
