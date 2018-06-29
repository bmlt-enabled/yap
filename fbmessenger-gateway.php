<?php
require_once 'vendor/autoload.php';
include 'functions.php';
$client = new Predis\Client();
$expiry_minutes = 5;

if (isset($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $GLOBALS['fbmessenger_verifytoken']) {
    echo $_REQUEST['hub_challenge'];
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
error_log(json_encode($input));
$messaging = $input['entry'][0]['messaging'][0];
if (isset($messaging['message']['attachments'])) {
    $messaging_attachment_payload = $messaging['message']['attachments'][0]['payload'];
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

if (isset($messaging['postback']['payload'])
    && $messaging['postback']['payload'] == "get_started") {
    sendMessage($GLOBALS['title'] . ".  You can search for meetings by entering a City, County or Postal Code, or even a Full Address.  You can also send your location, using the button below.  (Note: Distances, unless a precise location, will be estimates.)");
    sendMessage("By default, results for today will show up.  You can adjust this setting using the menu below.");
} elseif (isset($messageText)
          && strtoupper($messageText) == "MORE RESULTS") {
    $payload = json_decode( $messaging['message']['quick_reply']['payload'] );
    sendMeetingResults( $payload->coordinates, $messaging['sender']['id'], $payload->results_start);
} elseif (isset($messaging['postback']['payload'])) {
    $payload = json_decode($messaging['postback']['payload']);
    $client->setex('messenger_user_day_' . $messaging['sender']['id'], ($expiry_minutes * 60), json_encode($payload));

    $coordinates = getSavedCoordinates($messaging['sender']['id']);
    if ($coordinates != null) {
        sendMeetingResults($coordinates, $messaging['sender']['id']);
    } else {
        sendMessage('The day has been set to ' . $payload->set_day . ".  This setting will reset to lookup Today's meetings in 5 minutes.  Enter a City, County or Zip Code.");
    }
} elseif (isset($messageText) && strtoupper($messageText) == "THANK YOU") {
    sendMessage( ":)" );
} elseif (isset($messageText) && strtoupper($messageText) == "HELP") {
    sendMessage( "To find more information on this messenger app visit https://github.com/radius314/yap.");
} elseif (isset($messageText) && strtoupper($messageText) == "TALK") {
    $coordinates = getSavedCoordinates($messaging['sender']['id']);
    if ($coordinates != null) {
        sendServiceBodyCoverage($coordinates);
    } else {
        sendMessage("Enter a location, end then resubmit your request.");
    }
} else {
    $client->setex('messenger_user_location_' . $messaging['sender']['id'], ($expiry_minutes * 60), json_encode($coordinates));
    sendMeetingResults($coordinates, $messaging['sender']['id']);
    sendServiceBodyCoverage($coordinates);
}

function sendServiceBodyCoverage($coordinates) {
    $service_body = getServiceBodyCoverage($coordinates->latitude, $coordinates->longitude);
    if ($service_body != null) {
        sendMessage("Covered by: " . $service_body->name . ", their phone number is: " . $service_body->helpline);
    } else {
        sendMessage("Cannot find coverage.");
    }
}

function getSavedCoordinates($sender_id) {
    if ($GLOBALS['client']->get('messenger_user_location_' . $sender_id) != null) {
        return json_decode($GLOBALS['client']->get('messenger_user_location_' . $sender_id));
    } else {
        return null;
    }
}

function sendMeetingResults($coordinates, $sender_id, $results_start = 0) {
    if ($coordinates->latitude !== null && $coordinates->longitude !== null) {
        try {
            $results_count = (isset($GLOBALS['result_count_max']) ? $GLOBALS['result_count_max'] : 10) + $results_start;
            $settings = json_decode($GLOBALS['client']->get('messenger_user_day_' . $sender_id));
            $today = null;
            $tomorrow = null;
            if ($settings != null) {
                if ($today == null) $today = (new DateTime($settings->set_day))->format('w') + 1;
                if ($tomorrow == null) $tomorrow = (new DateTime($settings->set_day))->modify('+1 day')->format('w') + 1;
            }

            $meeting_results = getMeetings($coordinates->latitude, $coordinates->longitude, $results_count, $today, $tomorrow);
        } catch (Exception $e) {
            error_log($e);
            exit;
        }

        $filtered_list = $meeting_results->filteredList;

        for ($i = $results_start; $i < $results_count; $i++) {
            // Growth hacking
            if ($i == 0) {
                if (round($filtered_list[$i]->distance_in_miles) >= 100) {
                    sendMessage("Your community may not be covered by the BMLT yet.  https://www.doihavethebmlt.org/?latitude=" . $coordinates->latitude . "&longitude=" . $coordinates->longitude);
                }
            }

            $results = getResultsString($filtered_list[$i]);
            $distance_string = "(" . round($filtered_list[$i]->distance_in_miles) . " mi / " . round($filtered_list[$i]->distance_in_km) . " km)";

            $message = $results[0] . "\n" . $results[1] . "\n" . $results[2] . "\n" . $distance_string;
            sendMessage($message, $coordinates, $results_count);
        }
    } else {
        sendMessage("Location not recognized.  I only recognize City, County or Postal Code.");
    }
}

function sendMessage($message, $coordinates = null, $results_count = 0) {
    $quick_replies_payload = array(['content_type' => 'location']);
    if ($results_count > 0) {
        array_push($quick_replies_payload,
            ['content_type' => 'text',
             'title' => 'More Results',
             'payload' => json_encode([
                 'results_start' => $results_count + 1,
                 'coordinates' => $coordinates
             ])]);
    }

    sendBotResponse([
        'recipient' => ['id' => $GLOBALS['senderId']],
        'messaging_type' => 'RESPONSE',
        'message' => [
            'text' => $message,
            'quick_replies' => $quick_replies_payload
        ]
    ]);
}

function sendBotResponse($payload) {
    post('https://graph.facebook.com/v2.6/me/messages?access_token=' . $GLOBALS['fbmessenger_accesstoken'], $payload);
}
