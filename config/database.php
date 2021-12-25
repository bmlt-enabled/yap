<?php

class SearchType
{
    const NONE = -1;
    const VOLUNTEERS = 1;
    const MEETINGS = 2;
    const JFT = 3;
    const CUSTOM_EXTENSIONS = 998;
    const VOICEMAIL_PLAYBACK = 999;
    const DIALBACK = 1000;
}

class LocationSearchMethod
{
    const NONE = -1;
    const VOICE = 4;
    const DTMF = 5;
}

class MeetingResultSort
{
    const TODAY = 0;
}

if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], "/v1/")) {
    require_once(!getenv("ENVIRONMENT") ? base_path() . '/config.php' : base_path() . '/config.' . getenv("ENVIRONMENT") . '.php');
    putenv('DB_HOSTNAME=' . $mysql_hostname);
    putenv('DB_USERNAME=' . $mysql_username);
    putenv('DB_PASSWORD=' . $mysql_password);
    putenv('DB_DATABASE=' . $mysql_database);
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOSTNAME', ''),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', ''),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',


];
