<?php
    require_once '_includes/functions.php';
    header("Content-Type: application/json");
    echo json_encode(UpgradeAdvisor::getStatus());
