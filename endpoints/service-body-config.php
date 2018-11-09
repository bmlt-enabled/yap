<?php
require_once 'functions.php';
header("content-type: application/json");
echo json_encode(getServiceBodyConfiguration(setting("service_body_id")));
