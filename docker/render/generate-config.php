<?php

/**
 * Renders yap's config.php from environment variables at container start.
 *
 * yap reads its settings (and database credentials) from config.php rather than
 * from the environment, and config/database.php putenv()s the DB_* variables
 * from it. Rendering the file from env vars lets the Render Blueprint keep every
 * secret in the dashboard without touching that loading path.
 *
 * Mapped explicitly:   TITLE, BMLT_ROOT_SERVER, GOOGLE_MAPS_API_KEY,
 *                      TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN,
 *                      MYSQL_HOSTNAME, MYSQL_PORT, MYSQL_USERNAME,
 *                      MYSQL_PASSWORD, MYSQL_DATABASE
 * Any other setting:   YAP_SETTING_<NAME>, e.g. YAP_SETTING_LANGUAGE=es-US sets
 *                      $language. A value that parses as JSON (true, 30,
 *                      ["a","b"], {"1":"x"}) is decoded; anything else is a string.
 *
 * Usage: php yap-generate-config.php /var/www/html/config.php
 */

$target = $argv[1] ?? '/var/www/html/config.php';

$explicit = [
    'TITLE' => 'title',
    'BMLT_ROOT_SERVER' => 'bmlt_root_server',
    'GOOGLE_MAPS_API_KEY' => 'google_maps_api_key',
    'TWILIO_ACCOUNT_SID' => 'twilio_account_sid',
    'TWILIO_AUTH_TOKEN' => 'twilio_auth_token',
    'MYSQL_HOSTNAME' => 'mysql_hostname',
    'MYSQL_PORT' => 'mysql_port',
    'MYSQL_USERNAME' => 'mysql_username',
    'MYSQL_PASSWORD' => 'mysql_password',
    'MYSQL_DATABASE' => 'mysql_database',
];

$settings = [];
foreach ($explicit as $env => $name) {
    $value = getenv($env);
    if ($value !== false && $value !== '') {
        $settings[$name] = $name === 'mysql_port' ? (int) $value : $value;
    }
}

foreach (getenv() as $env => $value) {
    if (!str_starts_with($env, 'YAP_SETTING_') || $value === '') {
        continue;
    }
    $name = strtolower(substr($env, strlen('YAP_SETTING_')));
    if (!preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
        fwrite(STDERR, "yap: skipping $env, not a valid setting name\n");
        continue;
    }
    $decoded = json_decode($value, true);
    $settings[$name] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
}

$lines = ["<?php", "// Generated from environment variables by yap-generate-config.php at container start."];
foreach ($settings as $name => $value) {
    $lines[] = sprintf('static $%s = %s;', $name, var_export($value, true));
}

if (file_put_contents($target, implode("\n", $lines) . "\n") === false) {
    fwrite(STDERR, "yap: could not write $target\n");
    exit(1);
}
chmod($target, 0640);

$missing = array_diff(['title', 'bmlt_root_server', 'twilio_account_sid', 'twilio_auth_token',
    'mysql_hostname', 'mysql_username', 'mysql_password', 'mysql_database'], array_keys($settings));
if (getenv('GOOGLE_MAPS_API_KEY') === false || getenv('GOOGLE_MAPS_API_KEY') === '') {
    $missing[] = 'google_maps_api_key';
}
fwrite(STDERR, sprintf("yap: wrote %d settings to %s\n", count($settings), $target));
if ($missing) {
    fwrite(STDERR, "yap: warning, required settings not set: " . implode(', ', $missing) . "\n");
}
