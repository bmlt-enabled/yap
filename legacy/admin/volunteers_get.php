<?php
require_once 'auth_verify.php';
require_once __DIR__ . '/../_includes/functions.php';

try {
    $report = getVolunteersListReport(setting("service_body_id"));
    if (isset($_REQUEST['fmt']) && $_REQUEST['fmt'] == "csv") {
        $handle = fopen('php://memory', 'rw');
        try {
            fputcsv($handle, array("name", "number", "gender", "responder", "type", "language", "shift_info"));
            foreach (json_decode($report) as $item) {
                fputcsv($handle, array(
                    $item->name,
                    $item->number,
                    $item->gender,
                    $item->responder,
                    $item->type,
                    json_encode($item->language),
                    json_encode($item->shift_info)
                ));
            }
            fseek($handle, 0);
            $data = stream_get_contents($handle);
            header('Content-type: text/plain');
            header('Content-Length: ' . strlen($data));
            header(sprintf('Content-Disposition: attachment; filename="%s-volunteers.csv"', setting("service_body_id")));
            echo $data;
        } finally {
            fclose($handle);
        }
    } elseif (isset($_REQUEST['fmt']) && $_REQUEST['fmt'] == "json") {
        header("Content-type: application/json");
        echo $report;
    } else {
        header("Content-type: application/json");
        echo json_encode(new StdClass());
    }
} catch (NoVolunteersException $nve) {
    header("Content-type: application/json");
    echo json_encode(new StdClass());
}
