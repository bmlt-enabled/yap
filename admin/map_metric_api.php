<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
echo json_encode(getMapMetrics(
    $_REQUEST['service_body_id']
));
