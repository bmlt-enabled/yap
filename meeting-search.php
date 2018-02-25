<?php
    include 'config.php';
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];
    $search_type = $_REQUEST['SearchType'];
    $future = isset($_REQUEST['Future']) ? $_REQUEST['Future'] : 1;
    $time_zone_results = getTimeZoneForCoordinates($latitude, $longitude);
    # Could be wired up to use multiple roots in the future by using a parameter to select
    date_default_timezone_set($time_zone_results->zoneName);
    $today = date("w") + $future;
    $tomorrow = (new DateTime('tomorrow'))->format("w") + $future;

    try {
        $search_results = meetingSearch($latitude, $longitude, $search_type, $today);
        if (count($search_results->filteredList) == 0) {
            $search_results = meetingSearch($latitude, $longitude, $search_type, $tomorrow);
        }
    } catch (Exception $e) {
        header("Location: fallback.php");
        exit;
    }

    $filtered_list = $search_results->filteredList;
    $sms_messages = [];

    $results_count = 5;
    $text_space = "\r\n";
    $message = "";
?>
<Response>
<?php
    if (!isset($_REQUEST["SmsSid"])) {
        if ($search_results->originalListCount == 0) {
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">No results found, you might have an invalid entry.  Try again.</Say><Redirect method=\"GET\">input-method.php?Digits=2</Redirect>";
        } elseif (count($filtered_list) == 0) {
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">There are no other meetings for today.  Thank you for calling, goodbye.</Say>";
        } else {
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Meeting information found, listing the top " . $results_count . " results.</Say>";
        }
    } else {
        if ($search_results->originalListCount == 0) {
            echo "<Sms>No results found, you might have an invalid entry.  Try again.</Sms>";
        } elseif (count($filtered_list) == 0) {
            echo "<Sms>There are no other meetings for today.</Sms>";
        }
    }

    $results_counter = 0;
    for ($i = 0; $i < count($filtered_list); $i++) {
        $result_day = $filtered_list[$i]->weekday_tinyint;
        $result_time = $filtered_list[$i]->start_time;

        $part_1 = str_replace("&", "&amp;", $filtered_list[$i]->meeting_name);
        $part_2 = str_replace("&", "&amp;", $GLOBALS['days_of_the_week'][$result_day]
                . ' ' . (new DateTime($result_time))->format('g:i A'));
        $part_3 = str_replace("&", "&amp;", $filtered_list[$i]->location_street
                . " in " . $filtered_list[$i]->location_municipality
                . ", " . $filtered_list[$i]->location_province);

        if (!isset($_REQUEST["SmsSid"])) {
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Result number " . ($results_counter + 1) . "</Say>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . $part_1 . "</Say>";
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">Starts at " . $part_2 . "</Say>";
            echo "<Pause length=\"1\"/>";
            echo "<Say voice=\"" . $voice . "\" language=\"" . $language . "\">" . $part_3 . "</Say>";
        }

        $message = "<Sms>" . $part_1 . $text_space . $part_2 . $text_space . $part_3 . "</Sms>";
        if (isset($GLOBALS["sms_ask"]) && $GLOBALS["sms_ask"]) {
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
