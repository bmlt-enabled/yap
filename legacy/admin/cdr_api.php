<?php
require_once __DIR__ . '/../_includes/functions.php';
header("content-type: application/json");
echo json_encode(adjustedCallRecords(getReportsServiceBodies(), $_REQUEST['date_range_start'], $_REQUEST['date_range_end']));
