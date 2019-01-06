<?php
require_once '_includes/functions.php';
header("content-type: application/json");
echo json_encode(getServiceBodyConfiguration(setting("service_body_id")));
