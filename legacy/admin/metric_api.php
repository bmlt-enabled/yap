<?php
require_once __DIR__ . '/../_includes/functions.php';
header("content-type: application/json");
$GLOBALS["metrics"] = getMetric(getReportsServiceBodies(), intval($_REQUEST['service_body_id']) == 0);

function findMetric($date, $type) {
    foreach ($GLOBALS['metrics'] as $metric) {
        if ($metric['timestamp'] == $date && ($metric['data'] == '{"searchType":"'.$type.'"}'
                || $metric['data'] == '{"searchType":'.$type.'}')) {
            return $metric;
        }
    }

    return null;
}

$all_metrics = array();
if (count($GLOBALS["metrics"]) > 0) {
    $start_date = $GLOBALS["metrics"][0]['timestamp'];
    $end_date = $GLOBALS["metrics"][count($GLOBALS["metrics"]) - 1]['timestamp'];
    $current_date = $start_date;
    while ($current_date <= $end_date) {
        for ($x = 1; $x <= 3; $x++) {
            $fm = findMetric($current_date, $x);
            if ($fm != null) {
                array_push($all_metrics, $fm);
            } else {
                array_push($all_metrics, ['timestamp' => $current_date, 'counts' => 0, 'data' => sprintf('{"searchType":"%s"}', $x)]);
            }
        }

        $current_date = date('Y-m-d', strtotime($current_date . ' + 1 days'));
    }
}
echo json_encode($all_metrics);
