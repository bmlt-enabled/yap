<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
header("content-type: application/json");
$data = json_decode(file_get_contents('php://input'));
saveUser($data);
