<?php
if (isset($GLOBALS['cloudlog_key']) && isset($GLOBALS['cloudlog_app'])) {
    include './vendor/autoload.php';
    $logger = new \Monolog\Logger('general');
    $logdnaHandler = new \Zwijn\Monolog\Handler\LogdnaHandler($GLOBALS['cloudlog_key'], $GLOBALS['cloudlog_app'], \Monolog\Logger::DEBUG);
    $logger->pushHandler($logdnaHandler);
}

function log_debug($message) {
    if (isset($GLOBALS['cloudlog_key']) && isset($GLOBALS['debug'])) {
        $GLOBALS['logger']->debug($message);
    } elseif (boolval($GLOBALS['debug'])) {
        error_log($message);
    }
}
