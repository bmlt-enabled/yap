<?php
include 'config.php';
include 'functions.php';

if ($_REQUEST['hub_verify_token'] === $GLOBALS['fbmessenger_verifytoken']) {
    echo $_REQUEST['hub_challenge'];
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$senderId = $input['entry'][0]['messaging'][0]['sender']['id'];
$messageText = $input['entry'][0]['messaging'][0]['message']['text'];
$payload = null;
$answer = "";

$coordinates = getCoordinatesForAddress($messageText);
try {
    $meeting_results = getMeetings($coordinates->latitude, $coordinates->longitude, 1);
} catch (Exception $e) {
    error_log($e);
    exit;
}

$filtered_list = $meeting_results->filteredList;
$results_count = isset($GLOBALS['result_count_max']) ? $GLOBALS['result_count_max'] : 5;

for ($i = 0; $i < $results_count; $i++) {
    $result_day = $filtered_list[$i]->weekday_tinyint;
    $result_time = $filtered_list[$i]->start_time;

    $part_1 = str_replace("&", "&amp;", $filtered_list[$i]->meeting_name);
    $part_2 = str_replace("&", "&amp;", $GLOBALS['days_of_the_week'][$result_day]
        . ' ' . (new DateTime($result_time))->format('g:i A'));
    $part_3 = str_replace("&", "&amp;", $filtered_list[$i]->location_street
        . " in " . $filtered_list[$i]->location_municipality
        . ", " . $filtered_list[$i]->location_province);

    $message = $part_1 . " " . $part_2 . " " . $part_3;;

    $payload = [
        'recipient' => [ 'id' => $senderId ],
        'message' => [ 'text' => $message ]
    ];

    $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$GLOBALS['fbmessenger_accesstoken'];
    post($url, $payload);
}


