<?php
require_once __DIR__.'/../endpoints/functions.php';
header("content-type: application/json");
echo json_encode(getServiceBodyCoverage(
    $_REQUEST['latitude'],
    $_REQUEST['longitude']));
