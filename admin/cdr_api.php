<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
echo json_encode(adjustedCallRecords(
    intval($_REQUEST['service_body_id']) == 0 ? getServiceBodiesForUser(true) : [$_REQUEST['service_body_id']], intval($_REQUEST['page']), intval($_REQUEST['size']), boolval($_REQUEST['recurse']))
);
