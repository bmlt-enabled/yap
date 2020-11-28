<?php
require_once __DIR__ . '/../_includes/functions.php';
header("content-type: application/json");
echo json_encode(getMetric(getReportsServiceBodies(), intval($_REQUEST['service_body_id']) == 0));
