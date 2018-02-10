<?php
include 'functions.php';
header("content-type: application/json");
echo getHelplineSchedule($_REQUEST["service_body_id"]);