<?php
if (!file_exists('config.php') && isset($_REQUEST['status-check'])) {
    echo json_encode(["status"=>false,"message"=>"Waiting for config.php to exist..."]);
    return;
}
try {
    require_once '_includes/functions.php';
    echo json_encode(UpgradeAdvisor::getStatus());
} catch (Exception $e) {
    echo json_encode(["status"=>false,"message"=>sprintf("Error: %s", $e->getMessage())]);
}
