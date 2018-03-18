<?php
include 'config.php';
include 'functions.php';

if (isset($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $GLOBALS['fbmessenger_verifytoken']) {
    echo $_REQUEST['hub_challenge'];
    exit;
}

$input     = json_decode(file_get_contents('php://input'), true);
$messaging = $input['entry'][0]['messaging'][0];
if (isset($input['entry'][0]['messaging'][0]['message']['attachments'])) {
    $messaging_attachment_payload = $input['entry'][0]['messaging'][0]['message']['attachments'][0]['payload'];
}
$senderId  = $messaging['sender']['id'];
if (isset($messaging['message']['text']) && $messaging['message']['text'] !== null) {
	$messageText = $messaging['message']['text'];
	$coordinates = getCoordinatesForAddress($messageText);
} elseif (isset($messaging_attachment_payload) && $messaging_attachment_payload !== null) {
	$coordinates = new Coordinates();
	$coordinates->latitude = $messaging_attachment_payload['coordinates']['lat'];
	$coordinates->longitude = $messaging_attachment_payload['coordinates']['long'];
}

$payload = null;
$answer = "";

if (isset($input['entry'][0]['messaging'][0]['postback']['title']) && $input['entry'][0]['messaging'][0]['postback']['title'] == "Get Started") {
    sendMessage($GLOBALS['title'] .  ".  You can search for meetings by entering a City, County or Postal Code.  You can also send your location, using the button below.");
} else {
    if ($coordinates->latitude !== null && $coordinates->longitude !== null) {
        try {
            $results_count = isset($GLOBALS['result_count_max']) ? $GLOBALS['result_count_max'] : 10;
            $meeting_results = getMeetings($coordinates->latitude, $coordinates->longitude, 1, $results_count);
        } catch (Exception $e) {
            error_log($e);
            exit;
        }

        $filtered_list = $meeting_results->filteredList;

        for ($i = 0; $i < $results_count; $i++) {
            $results = getResultsString($filtered_list[$i]);

            $message = $results[0] . " " . $results[1] . " " . $results[2];
            sendMessage($message);
        }
    } else {
        sendMessage("Location not recognized.  I only recognize City, County or Postal Code.");
    }
}

function sendMessage($message) {
    sendBotResponse([
        'recipient' => ['id' => $GLOBALS['senderId']],
        'message' => [
            'text' => $message,
            'quick_replies' => array(['content_type' => 'location'])
        ]
    ]);
}

function sendBotResponse($payload) {
    post('https://graph.facebook.com/v2.6/me/messages?access_token=' . $GLOBALS['fbmessenger_accesstoken'], $payload);
}
