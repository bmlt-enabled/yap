<?php
include 'config.php';
include 'functions.php';

if (isset($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $GLOBALS['fbmessenger_verifytoken']) {
    echo $_REQUEST['hub_challenge'];
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
$messaging = $input['entry'][0]['messaging'][0];
$messaging_attachment_payload = $input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload'];
$senderId  = $messaging['sender']['id'];
if ($messaging['message']['text'] !== null) {
	$messageText = $messaging['message']['text'];
	$coordinates = getCoordinatesForAddress($messageText);
} elseif ($messaging_attachment_payload !== null) {
	$coordinates = new Coordinates();
	$coordinates->latitude = $messaging_attachment_payload['coordinates']['lat'];
	$coordinates->longitude = $messaging_attachment_payload['coordinates']['long'];
}
 $payload = null;
$answer = "";

if (isset($input['entry'][0]['messaging'][0]['postback']['title']) && $input['entry'][0]['messaging'][0]['postback']['title'] == "Get Started") {
    sendMessage($GLOBALS['title'] .  ".  You can search for meetings by entering a City, County or Zip Code.");
} else {
    if ($coordinates->latitude !== null && $coordinates->longitude !== null) {
        try {
            $meeting_results = getMeetings($coordinates->latitude, $coordinates->longitude, 1);
        } catch (Exception $e) {
            error_log($e);
            exit;
        }

        $filtered_list = $meeting_results->filteredList;
        $results_count = isset($GLOBALS['result_count_max']) ? $GLOBALS['result_count_max'] : 10;

        for ($i = 0; $i < $results_count; $i++) {
            $result_day = $filtered_list[$i]->weekday_tinyint;
            $result_time = $filtered_list[$i]->start_time;

            $part_1 = str_replace("&", "&amp;", $filtered_list[$i]->meeting_name);
            $part_2 = str_replace("&", "&amp;", $GLOBALS['days_of_the_week'][$result_day]
                . ' ' . (new DateTime($result_time))->format('g:i A'));
            $part_3 = str_replace("&", "&amp;", $filtered_list[$i]->location_street
                . " in " . $filtered_list[$i]->location_municipality
                . ", " . $filtered_list[$i]->location_province);

            $message = $part_1 . " " . $part_2 . " " . $part_3;
            sendMessage($message);
        }
    } else {
        sendMessage("Location not recognized.  I only recognize City, County or Zip Code.");
    }
}

function sendMessage($message) {
    $payload = [
        'recipient' => ['id' => $GLOBALS['senderId']],
        'message' => ['text' => $message]
    ];

    post('https://graph.facebook.com/v2.6/me/messages?access_token=' . $GLOBALS['fbmessenger_accesstoken'], $payload);
}
