<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
echo json_encode(getMetric(intval($_REQUEST['service_body_id']) == 0 ? getServiceBodiesForUser(true) : [$_REQUEST['service_body_id']], intval($_REQUEST['service_body_id']) == 0));
