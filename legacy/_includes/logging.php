<?php
include __DIR__ . '/../../vendor/autoload.php';
use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

if (isset($GLOBALS['cloudlog_key']) && isset($GLOBALS['cloudlog_app'])) {
    $logger = new \Monolog\Logger($GLOBALS['cloudlog_app']);
    $logdnaHandler = new \Zwijn\Monolog\Handler\LogdnaHandler($GLOBALS['cloudlog_key'], "yap", \Monolog\Logger::DEBUG);
    $logger->pushHandler($logdnaHandler);
}

if (isset($GLOBALS['rollbar_access_token']) && isset($GLOBALS['rollbar_environment'])) {
    if ((new Rollbar) instanceof Rollbar) {
        Rollbar::init(
            array(
                'access_token' => $GLOBALS['rollbar_access_token'],
                'environment' => $GLOBALS['rollbar_environment']
            )
        );
    }
}

function log_debug($message)
{
    if (isset($GLOBALS['cloudlog_key']) && isset($GLOBALS['debug'])) {
        $GLOBALS['logger']->debug($message);
    } elseif (isset($GLOBALS['debug']) && boolval($GLOBALS['debug'])) {
        error_log($message);
    }
}
