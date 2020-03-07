<?php
header("Content-Type: application/json");
if (!file_exists('../config.php') && isset($_REQUEST['status-check'])) {
    echo json_encode(["status"=>false]);
    exit();
}
require_once '_includes/functions.php';
echo json_encode(UpgradeAdvisor::getStatus());
