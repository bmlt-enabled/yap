<?php
if (isset($GLOBALS['logentries_token'])) {
    putenv("LOGENTRIES_TOKEN=" . $GLOBALS['logentries_token']);
    require dirname(__FILE__) . '/vendor/logentries/logentries/logentries.php';
}

function log_debug($message) {
    if (isset($GLOBALS['logentries_token'])) {
        $GLOBALS['log']->Debug($message);
    } else {
        error_log($message);
    }
}
