<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

    
    $latitude = isset($_REQUEST['Latitude']) ? $_REQUEST['Latitude'] : null;
    $longitude = isset($_REQUEST['Longitude']) ? $_REQUEST['Longitude'] : null;

try {
    $results_count = has_setting('result_count_max') ? setting('result_count_max') : 5;
    $meeting_results = getMeetings($latitude, $longitude, $results_count, null, null);
    $results_count_num = count($meeting_results->filteredList) < $results_count ? count($meeting_results->filteredList) : $results_count;
} catch (Exception $e) { ?>
        <Response>
        <Redirect method="GET">fallback.php</Redirect>
        </Response>
        <?php
        exit;
}

    $filtered_list = $meeting_results->filteredList;
    $sms_messages = [];

    $text_space = " ";
    $message = "";

function mobileCheck()
{
    if (!isset($_SESSION['is_mobile'])) {
        $is_mobile = true;
        if (has_setting('mobile_check') && json_decode(setting('mobile_check'))) {
            $phone_number = $GLOBALS['twilioClient']->lookups->v1->phoneNumbers($_REQUEST['From'])->fetch(array("type" => "carrier"));
            if ($phone_number->carrier['type'] !== 'mobile') {
                $is_mobile = false;
            }
        }
        $_SESSION['is_mobile'] = $is_mobile;
    }

    return $_SESSION['is_mobile'];
}

function sendSms($message)
{
    if (isset($_REQUEST['From']) && isset($_REQUEST['To']) && mobileCheck()) {
        $GLOBALS['twilioClient']->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $message));
    }
}
?>
<Response>
<?php
if (!isset($_REQUEST["SmsSid"])) {
    if ($meeting_results->originalListCount == 0) {
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('no_results_found') . "... " . word('you_might_have_invalid_entry') . "... " . word('try_again') . "</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
    } elseif (count($filtered_list) == 0) {
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('there_are_no_other_meetings_for_today') . ".... " . word('try_again') . "</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
    } else {
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('meeting_information_found_listing_the_top') . " " . $results_count_num . " " . word('results') . "</Say>";
    }
} else {
    if ($meeting_results->originalListCount == 0) {
        echo "<Sms>" . word('no_results_found') . "... " . word('you_might_have_invalid_entry') . "..." . word('try_again') . "</Sms>";
    } elseif (count($filtered_list) == 0) {
        echo "<Sms>" . word('there_are_no_other_meetings_for_today') . "</Sms>";
    }
}

    $results_counter = 0;
for ($i = 0; $i < count($filtered_list); $i++) {
    $results = getResultsString($filtered_list[$i]);

    if (!isset($_REQUEST["SmsSid"])) {
        echo "<Pause length=\"1\"/>";
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('number') . " " . ($results_counter + 1) . "</Say>";
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results[0] . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('starts_at') . " " . $results[1] . "</Say>";
        if (has_setting('include_location_text') && json_decode(setting('include_location_text'))) {
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results[2] . "</Say>";
        }

        echo "<Pause length=\"1\"/>";
        echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results[3] . "</Say>";

        if (isset($_REQUEST["Debug"])) {
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . json_encode($filtered_list[$i]) . "</Say>";
        }
    }

    $results_counter++;
    if ($results_counter == $results_count) {
        break;
    }
}

if (has_setting('sms_summary_page') && json_decode(setting('sms_summary_page'))) {
    $voice_url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    if (strpos(basename($voice_url), ".php")) {
        $webhook_url = substr($voice_url, 0, strrpos($voice_url, "/"));
    } else if (strpos($voice_url, "?")) {
        $webhook_url = substr($voice_url, 0, strrpos($voice_url, "?"));
    } else {
        $webhook_url = $voice_url;
    }

    $message = "Meeting Results, Click Here: " . $webhook_url . "/meeting-results.php?latitude=" . $latitude . "&longitude=" . $longitude;

    if (json_decode(setting("sms_ask")) && !isset($_REQUEST["SmsSid"])) {
        array_push($sms_messages, $message);
    } else {
        sendSms($message);
    }
} else {
    $results_counter = 0;
    for ($i = 0; $i < count($filtered_list); $i++) {
        $results = getResultsString($filtered_list[$i]);
        $location_line = (has_setting('include_location_text') && json_decode(setting('include_location_text'))) ? $results[2] . $text_space . $results[3] : $results[3];
        $message = $results[0] . $text_space . $results[1] . $text_space . $location_line;
        if (has_setting('include_map_link') && json_decode(setting('include_map_link'))) {
            $message .= " https://google.com/maps?q=" . $filtered_list[$i]->latitude . "," . $filtered_list[$i]->longitude;
        }

        if (json_decode(setting("sms_ask")) && !isset($_REQUEST["SmsSid"])) {
            array_push($sms_messages, $message);
        } else {
            sendSms($message);
        }

        $results_counter++;
        if ($results_counter == $results_count) {
            break;
        }
    }
}

    // Do not handle for the SMS gateway
if (!isset($_REQUEST["SmsSid"]) && count($filtered_list) > 0) {
    echo "<Pause length=\"2\"/>";
    if (count($sms_messages) > 0) { ?>
            <Gather numDigits="1" timeout="10" speechTimeout="auto" input="<?php echo getInputType() ?>"
                    action="post-call-action.php?Payload=<?php echo urlencode(json_encode($sms_messages)) ?>"
                    method="GET">
            <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                <?php echo getPressWord() ?> <?php echo word("one") ?> <?php echo word('if_you_would_like_these_results_texted_to_you') ?>
            </Say>
            <?php if (json_decode(setting('infinite_searching'))) { ?>
                <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                    <?php echo getPressWord() ?> <?php echo word("two") ?> <?php echo word('if_you_would_like_to_search_again') ?>...
                    <?php echo getPressWord() ?> <?php echo word("three") ?> <?php echo word('if_you_would_like_to_do_both') ?>
                </Say>
            <?php } ?>
            </Gather>
    <?php } elseif (json_decode(setting('infinite_searching'))) { ?>
        <Gather numDigits="1" timeout="10" speechTimeout="auto" input="<?php echo getInputType() ?>" action="post-call-action.php" method="GET">
            <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
                <?php echo getPressWord()?> <?php echo word("two")?> <?php echo word('if_you_would_like_to_search_again') ?>.
            </Say>
        </Gather>
    <?php } ?>

    <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>">
        <?php echo word('thank_you_for_calling_goodbye')?>
    </Say>
<?php } ?>
</Response>
