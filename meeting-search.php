<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];

    try {
        $results_count = $results_count = isset($GLOBALS['result_count_max']) ? $GLOBALS['result_count_max'] : 5;
        $meeting_results = getMeetings($latitude, $longitude, $results_count);
    } catch (Exception $e) {
        header("Location: fallback.php");
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
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">No results found, you might have an invalid entry.  Try again.</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
        } elseif (count($filtered_list) == 0) {
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">There are no other meetings for today.  Thank you for calling, goodbye.</Say>";
        } else {
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Meeting information found, listing the top " . $results_count . " results.</Say>";
        }
    } else {
        if ($meeting_results->originalListCount == 0) {
            echo "<Sms>No results found, you might have an invalid entry.  Try again.</Sms>";
        } elseif (count($filtered_list) == 0) {
            echo "<Sms>There are no other meetings for today.</Sms>";
        }
    }

    $results_counter = 0;
    for ($i = 0; $i < count($filtered_list); $i++) {
        $results = getResultsString($filtered_list[$i]);

        if (!isset($_REQUEST["SmsSid"])) {
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Result number " . ($results_counter + 1) . "</Say>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . $results[0] . "</Say>";
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Starts at " . $results[1] . "</Say>";
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . $results[2] . "</Say>";
        }

        if (isset($GLOBALS['include_map_link']) && $GLOBALS['include_map_link']) $results[2] .= " https://google.com/maps?q=" . $filtered_list[$i]->latitude . "," . $filtered_list[$i]->longitude;
        $message = "<Sms>" . $results[0] . $text_space . $results[1] . $text_space . $results[2] . "</Sms>";
        error_log($message);
        if (isset($GLOBALS["sms_ask"]) && $GLOBALS["sms_ask"] && !isset($_REQUEST["SmsSid"])) {
            array_push($sms_messages, $message);
        } else {
            echo $message;
        }
            
        $results_counter++;
        if ($results_counter == $results_count) break;
    }

    if (!isset($_REQUEST["SmsSid"]) && count($filtered_list) > 0) {
        if (count($sms_messages) > 0) {
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">If you would like these results to be texted... press any key now.</Say>";
            echo "<Gather numDigits=\"1\" timeout=\"10\" action=\"sms-ask.php?Payload=" . urlencode(json_encode($sms_messages)) . "\" method=\"GET\"/>";
        }

        echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Thank you for calling, goodbye.</Say>";
    }
?>
</Response>
