<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
echo json_encode(adjustedCallRecords(getReportsServiceBodies(), intval($_REQUEST['page']), intval($_REQUEST['size'])));
