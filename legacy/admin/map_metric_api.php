<?php
require_once __DIR__ . '/../endpoints/_includes/functions.php';
if (isset($_REQUEST['format']) == "csv") {
    $data = "lat,lon,name,desc\n";
    $metrics = getMapMetricByType(getReportsServiceBodies(), $_REQUEST['event_id']);
    $event_id = intval($_REQUEST['event_id']);
    foreach ($metrics as $metric) {
        $coordinates = json_decode($metric['meta'])->coordinates;
        if ($coordinates->location != null) {
            $data .= sprintf(
                "%s,%s,\"%s\",\"%s\"\n",
                $coordinates->latitude,
                $coordinates->longitude,
                $coordinates->location,
                $event_id
            );
        }
    }

    header('Content-type: text/plain');
    header('Content-Length: ' . strlen($data));
    header(sprintf('Content-Disposition: attachment; filename="%s-map-metrics.csv"', $event_id == EventId::VOLUNTEER_SEARCH ? "volunteers" : "meetings"));
    echo $data;
} else {
    $results = [];
    $metrics = getMapMetrics(getReportsServiceBodies());
    foreach ($metrics as $metric) {
        $coordinates = json_decode($metric['meta'])->coordinates;
        if ($coordinates->location != null) {
            array_push($results, $metric);
        }
    }
    header("content-type: application/json");
    echo json_encode($results);
}
