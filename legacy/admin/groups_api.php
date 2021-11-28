<?php
require_once 'auth_verify.php';
header("content-type: application/json");
$data = getGroupsForServiceBody($_REQUEST["service_body_id"], isset($_REQUEST["manage"]));
echo count($data) > 0 ? json_encode($data) : json_encode(new StdClass());
