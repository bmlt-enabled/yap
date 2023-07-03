<?php
require_once '_includes/functions.php';
require_once '_includes/twilio-client.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";


$latitude = isset($_REQUEST['Latitude']) ? $_REQUEST['Latitude'] : null;
$longitude = isset($_REQUEST['Longitude']) ? $_REQUEST['Longitude'] : null;

try {
    $suppress_voice_results = has_setting('suppress_voice_results') && json_decode(setting('suppress_voice_results'));
    $sms_disable = has_setting('sms_disable') && json_decode(setting('sms_disable'));
    $results_count = has_setting('result_count_max') ? intval(setting('result_count_max')) : 5;
    $meeting_results = getMeetings($latitude, $longitude, $results_count, null, null);
    $results_count_num = count($meeting_results->filteredList) < $results_count ? count($meeting_results->filteredList) : $results_count;
} catch (Exception $e) { ?>
    <Response>
        <Redirect method="GET">fallback.php</Redirect>
    </Response>
    <?php
    return;
}

$filtered_list = $meeting_results->filteredList;
$sms_messages = [];

$text_space = " ";
$comma_space = ", ";
$message = "";
?>
<Response>
    <?php
    $isFromSmsGateway = isset($_REQUEST["SmsSid"]);
    if (!$isFromSmsGateway) {
        if ($meeting_results->originalListCount == 0) {
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('no_results_found') . "... " . word('you_might_have_invalid_entry') . "... " . word('try_again') . "</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
        } elseif (count($filtered_list) == 0) {
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('there_are_no_other_meetings_for_today') . ".... " . word('try_again') . "</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
        } elseif ($suppress_voice_results) {
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results_count_num  . " " . word('meetings_have_been_texted') . "</Say>";
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

    if (!json_decode(setting("sms_ask")) && !json_decode(setting("sms_disable"))) { ?>
        <Say voice="<?php echo voice(); ?>" language="<?php echo setting('language') ?>">
            <?php echo word('search_results_by_sms') ?>
        </Say>
    <?php }

    $results_counter = 0;
    for ($i = 0; $i < count($filtered_list); $i++) {
        $results = getResultsString($filtered_list[$i]);

        if (!$isFromSmsGateway && !$suppress_voice_results) {
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('number') . " " . ($results_counter + 1) . "</Say>";
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results['meeting_name'] . "</Say>";
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . word('starts_at') . " " . $results['timestamp'] . "</Say>";

            if (has_setting('include_format_details') && count(setting('include_format_details')) > 0) {
                for ($fd = 0; $fd < count($results['format_details']); $fd++) {
                    echo "<Pause length=\"1\"/>";
                    echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results['format_details'][$fd]->description_string . "</Say>";
                }
            }

            for ($ll = 0; $ll < count($results['location']); $ll++) {
                echo "<Pause length=\"1\"/>";
                echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results['location'][$ll] . "</Say>";
            }

            if (has_setting("say_links") && json_decode(setting("say_links"))) {
                for ($fl = 0; $fl < count($results['links']); $fl++) {
                    echo "<Pause length=\"1\"/>";
                    echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results['links'][$fl] . "</Say>";
                }
            }

            for ($vmai = 0; $vmai < count($results['virtual_meeting_additional_info']); $vmai++) {
                echo "<Pause length=\"1\"/>";
                echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . $results['virtual_meeting_additional_info'][$vmai] . "</Say>";
            }

            if (isset($_REQUEST["Debug"])) {
                echo "<Say voice=\"" . voice() . "\" language=\"" . setting('language') . "\">" . json_encode($filtered_list[$i]) . "</Say>";
            }
        }

        $results_counter++;
        if ($results_counter == $results_count) {
            break;
        }
    }

    if (!$sms_disable && has_setting('sms_summary_page') && json_decode(setting('sms_summary_page'))) {
        $voice_url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
        if (strpos(basename($voice_url), ".php")) {
            $webhook_url = substr($voice_url, 0, strrpos($voice_url, "/"));
        } else if (strpos($voice_url, "?")) {
            $webhook_url = substr($voice_url, 0, strrpos($voice_url, "?"));
        } else {
            $webhook_url = $voice_url;
        }

        $message = sprintf("Meeting Results, click here: %s/msr/%s/%s", $webhook_url, $latitude, $longitude);

        if (json_decode(setting("sms_ask")) && !$isFromSmsGateway) {
            array_push($sms_messages, $message);
        } else {
            sendSms($message);
        }
    } elseif (!$sms_disable) {
        $results_counter = 0;
        for ($i = 0; $i < count($filtered_list); $i++) {
            $results = getResultsString($filtered_list[$i]);
            $location_line = implode(", ", $results['location']);
            $message = $results['meeting_name'] . $text_space . $results['timestamp'] . $comma_space . $location_line;

            if (strlen($results['distance_details']) > 0) {
                $message .= " " . $results['distance_details'];
            }

            foreach ($results['format_details'] as $format_detail) {
                $message .= "\n" . $format_detail->description_string;
            }

            foreach ($results['location_links'] as $location_link) {
                $message .= " " . $location_link;
            }

            foreach ($results['links'] as $link) {
                $message .= "\n" . $link;
            }

            foreach ($results['virtual_meeting_additional_info'] as $additional_info) {
                $message .= "\n" . $additional_info;
            }

            if (json_decode(setting("sms_combine")) || (json_decode(setting("sms_ask")) && !$isFromSmsGateway)) {
                array_push($sms_messages, $message);
            } else {
                sendSms($message);
            }

            $results_counter++;
            if ($results_counter == $results_count) {
                break;
            }
        }

        if (json_decode(setting("sms_combine")) && !json_decode(setting("sms_ask"))) {
            sendSms(implode("\n\n", $sms_messages));
        }
    }

    // Do not handle for the SMS gateway
    if (!$isFromSmsGateway && count($filtered_list) > 0) {
        echo "<Pause length=\"2\"/>";
        if (!$sms_disable && !$suppress_voice_results && count($sms_messages) > 0) { ?>
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
