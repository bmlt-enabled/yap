<?php
include 'functions.php';
header("content-type: application/json");
echo filterOut(getHelplineSchedule(setting("service_body_id")));