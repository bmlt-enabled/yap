<?php
require_once __DIR__.'/../endpoints/functions.php';
header("content-type: application/json");
echo json_encode(getMeetings(
    $_REQUEST['latitude'],
    $_REQUEST['longitude'],
    $_REQUEST['results_count'],
    isset($_REQUEST['today']) ? $_REQUEST['today'] : null,
    isset($_REQUEST['tomorrow']) ? $_REQUEST['tomorrow'] : null));
