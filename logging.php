<?php
if (isset($GLOBALS['logentries_token'])) {
    putenv("LOGENTRIES_TOKEN=" . $GLOBALS['logentries_token']);
    require dirname(__FILE__) . '/vendor/logentries/logentries/logentries.php';
}

function log_debug($message) {
    if (isset($GLOBALS['logentries_token']) && isset($GLOBALS['debug'])) {
        $GLOBALS['log']->Debug($message);
    } elseif (boolval($GLOBALS['debug'])) {
        error_log($message);
    }
}
