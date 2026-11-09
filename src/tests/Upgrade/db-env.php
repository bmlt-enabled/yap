<?php
/**
 * Database settings for the upgrade-test scripts, read from config.test.php
 * (or the file named by $UPGRADE_CONFIG), the same file yap:simulate and the
 * packaged app use. Included by the PHP scripts; run directly, it prints them
 * as shell assignments for the bash scripts:
 *
 *   eval "$(php tests/Upgrade/db-env.php)"
 */

function upgrade_db_settings(): array
{
    $file = getenv('UPGRADE_CONFIG') ?: dirname(__DIR__, 2) . '/config.test.php';
    if (!is_file($file)) {
        fwrite(STDERR, "db-env.php: no config file at {$file}\n");
        exit(2);
    }
    // config files declare `static $x = ...;`, so read them in a function scope.
    $vars = (static function (string $__file): array {
        include $__file;
        return get_defined_vars();
    })($file);

    return [
        'host' => $vars['mysql_hostname'] ?? '127.0.0.1',
        'port' => (int) ($vars['mysql_port'] ?? 3306),
        'user' => $vars['mysql_username'] ?? 'root',
        'password' => $vars['mysql_password'] ?? '',
        'database' => $vars['mysql_database'] ?? 'yap',
    ];
}

function upgrade_pdo(?string $database = null): PDO
{
    $db = upgrade_db_settings();
    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], $db['port']);
    if (($database ?? $db['database']) !== '') {
        $dsn .= ';dbname=' . ($database ?? $db['database']);
    }
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // yap writes UTC (gmdate) into TIMESTAMP columns, and mysqldump pins UTC;
    // read and write them the same way whatever the server's own zone is.
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    foreach (upgrade_db_settings() as $key => $value) {
        echo 'DB_' . strtoupper($key) . '=' . escapeshellarg((string) $value) . "\n";
    }
}
