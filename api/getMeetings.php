<?php
include '../functions.php';
header("content-type: application/json");
echo json_encode(getMeetings(
    isset($_REQUEST['latitude']) ? $_REQUEST['latitude'] : null,
    isset($_REQUEST['longitude']) ? $_REQUEST['longitude'] : null,
    $_REQUEST['results_count'],
    isset($_REQUEST['today']) ? $_REQUEST['today'] : null,
    isset($_REQUEST['tomorrow']) ? $_REQUEST['tomorrow'] : null));
