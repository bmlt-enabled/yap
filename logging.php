<?php
if (isset($GLOBALS['cloudlog_key']) && isset($GLOBALS['cloudlog_app'])) {
    include __DIR__.'/vendor/autoload.php';
    $logger = new \Monolog\Logger($GLOBALS['cloudlog_app']);
    $logdnaHandler = new \Zwijn\Monolog\Handler\LogdnaHandler($GLOBALS['cloudlog_key'], "yap", \Monolog\Logger::DEBUG);
    $logger->pushHandler($logdnaHandler);
}

function log_debug($message) {
    if (isset($GLOBALS['cloudlog_key']) && isset($GLOBALS['debug'])) {
        $GLOBALS['logger']->debug($message);
    } elseif (boolval($GLOBALS['debug'])) {
        error_log($message);
    }
}
