<?php
require_once 'auth_verify.php';
echo json_encode(adjustedCallRecords(getReportsServiceBodies(), $_REQUEST['date_range_start'], $_REQUEST['date_range_end']));
