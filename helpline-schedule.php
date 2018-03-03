<?php
include 'functions.php';
header("content-type: application/json");
echo filterOut(getHelplineSchedule($_REQUEST["service_body_id"]));