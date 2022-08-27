<?php
require_once 'auth_verify.php';
header("content-type: application/json");
$reportsServiceBodies = getReportsServiceBodies();
$GLOBALS["metrics"] = getMetric($reportsServiceBodies, $_REQUEST['date_range_start'], $_REQUEST['date_range_end']);
$GLOBALS["summary"] = getMetricCounts($reportsServiceBodies, $_REQUEST['date_range_start'], $_REQUEST['date_range_end']);
$GLOBALS["volunteers"] = getAnsweredAndMissedVolunteerMetrics($reportsServiceBodies, $_REQUEST['date_range_start'], $_REQUEST['date_range_end']);

function findMetric($date, $type)
{
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
    $metrics_types = [EventId::VOLUNTEER_SEARCH, EventId::MEETING_SEARCH, EventId::JFT_LOOKUP, EventId::MEETING_SEARCH_SMS, EventId::VOLUNTEER_SEARCH_SMS, EventId::JFT_LOOKUP_SMS];
    while ($current_date <= $end_date) {
        foreach ($metrics_types as $metric_type) {
            $fm = findMetric($current_date, $metric_type);
            if ($fm != null) {
                array_push($all_metrics, $fm);
            } else {
                array_push($all_metrics, ['timestamp' => $current_date, 'counts' => 0, 'data' => sprintf('{"searchType":"%s"}', $metric_type)]);
            }
        }

        $current_date = date('Y-m-d', strtotime($current_date . ' + 1 days'));
    }
}
echo json_encode(['metrics' => $all_metrics, 'summary' => $GLOBALS["summary"], 'volunteers' => $GLOBALS['volunteers']]);
