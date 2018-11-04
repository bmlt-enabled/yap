<?php
    include 'config.php';
    include 'functions.php';
    require_once 'vendor/autoload.php';
    use Twilio\Rest\Client;
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    $sid = $GLOBALS['twilio_account_sid'];
    $token = $GLOBALS['twilio_auth_token'];
    $client = new Client( $sid, $token );
    
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
?>
<Response>
<?php
    if (!isset($_REQUEST["SmsSid"])) {
        if ($meeting_results->originalListCount == 0) {
            echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . word('no_results_found') . "... " . word('you_might_have_invalid_entry') . "... " . word('try_again') . "</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
        } elseif (count($filtered_list) == 0) {
            echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . word('there_are_no_other_meetings_for_today') . ".... " . word('try_again') . "</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
        } else {
            echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . word('meeting_information_found_listing_the_top') . " " . $results_count_num . " " . word('results') . "</Say>";
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
            echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . word('number') . " " . ($results_counter + 1) . "</Say>";
	    echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . $results[0] . "</Say>";
	    echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . word('starts_at') . " " . $results[1] . "</Say>";
	    echo "<Pause length=\"1\"/>";
	    $results[2] = preg_replace($city_pron_fix_a, $city_pron_fix_b, $results[2]);
	    echo "<Say voice=\"" . setting('voice') . "\" language=\"" . setting('language') . "\">" . $results[2] . "</Say>";
        }
        if (json_decode(setting('include_map_link'))) $results[2] .= " https://google.com/maps?q=" . $filtered_list[$i]->latitude . "," . $filtered_list[$i]->longitude;
        $message = $results[0] . $text_space . $results[1] . $text_space . $results[2];
        log_debug($message);
        if (json_decode(setting("sms_ask")) && !isset($_REQUEST["SmsSid"])) {
            array_push($sms_messages, $message);
        } else {
            if (isset($_REQUEST['From']) && isset($_REQUEST['To'])) {
                $client->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $message));
            }
        }
            
        $results_counter++;
        if ($results_counter == $results_count) break;
    }
    // Do not handle for the SMS gateway
    if (!isset($_REQUEST["SmsSid"]) && count($filtered_list) > 0) {
        echo "<Pause length=\"2\"/>";
        if (count($sms_messages) > 0) { ?>
            <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
                <?php echo word( 'press' ) ?> <?php echo word( "one" ) ?> <?php echo word( 'if_you_would_like_these_results_texted_to_you' ) ?>
            </Say>
            <?php if (json_decode(setting('infinite_searching'))) { ?>
                <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
                    <?php echo word( 'press' ) ?> <?php echo word( "two" ) ?> <?php echo word( 'if_you_would_like_to_search_again' ) ?>...
                    <?php echo word( 'press' ) ?> <?php echo word( "three" ) ?> <?php echo word( 'if_you_would_like_to_do_both' ) ?>
                </Say>
            <?php } ?>
            <Gather numDigits="1" timeout="10" speechTimeout="auto" input="speech dtmf"
                    action="post-call-action.php?Payload=<?php echo urlencode( json_encode( $sms_messages ) ) ?>"
                    method="GET"/>
        <?php } elseif (json_decode(setting('infinite_searching'))) { ?>
            <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
                <?php echo word('press')?> <?php echo word("two")?> <?php echo word('if_you_would_like_to_search_again') ?>.
            </Say>
            <Gather numDigits="1" timeout="10" speechTimeout="auto" input="speech dtmf" action="post-call-action.php" method="GET"/>
        <?php } ?>

    <Say voice="<?php echo setting('voice') ?>" language="<?php echo setting('language') ?>">
        <?php echo word('thank_you_for_calling_goodbye')?>
    </Say>
    <?php } ?>
</Response>
