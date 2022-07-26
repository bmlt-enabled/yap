<?php
require_once 'auth_verify.php';
require_once __DIR__ . '/../_includes/functions.php';

try {
    $report = getVolunteersListReport(setting("service_body_id"));
    if (isset($_REQUEST['format']) == "csv") {
        $data = "name,number,gender,responder,type,language,shift_info\n";
        foreach (json_decode($report) as $item) {
            $data .= sprintf(
                "%s,%s,%s,%s,%s,%s,'%s'\n",
                $item->name,
                $item->number,
                $item->gender,
                $item->responder,
                $item->type,
                json_encode($item->language),
                json_encode($item->shift_info),
            );
        }
        header('Content-type: text/plain');
        header('Content-Length: ' . strlen($data));
        header(sprintf('Content-Disposition: attachment; filename="%s-volunteers.csv"', setting("service_body_id")));
        echo $data;
    } else {
        header("Content-type: application/json");
        echo $report;
    }
} catch (NoVolunteersException $nve) {
    header("Content-type: application/json");
    echo json_encode(new StdClass());
}
