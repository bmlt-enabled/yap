<?php
include '../functions.php';
header("content-type: application/json");
echo json_encode(getServiceBodyCoverage(
    $_REQUEST['latitude'],
    $_REQUEST['longitude']));
