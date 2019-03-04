<?php
require_once '_includes/functions.php';
header("content-type: application/json");
try {
    echo filterOut(getHelplineSchedule(setting("service_body_id")));
} catch (NoVolunteersException $nve) {
    echo json_encode(new StdClass());
}
