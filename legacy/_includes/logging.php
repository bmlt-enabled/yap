<?php
include __DIR__ . '/../../vendor/autoload.php';

function log_debug($message)
{
    if (isset($GLOBALS['debug']) && boolval($GLOBALS['debug'])) {
        error_log($message);
    }
}
