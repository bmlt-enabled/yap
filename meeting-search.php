<?php
    include 'functions.php';
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];
    $search_type = $_REQUEST['SearchType'];
    $future = $_REQUEST['Future'] ?: 1;
    $time_zone_results = getTimeZoneForCoordinates($latitude, $longitude);
    # Could be wired up to use multiple roots in the future by using a parameter to select
    date_default_timezone_set($time_zone_results->zoneName);
    $today = date("w") + $future;
    $tomorrow = (new DateTime('tomorrow'))->format("w") + $future;
    
    $search_results = meetingSearch($latitude, $longitude, $search_type, $today, $tomorrow);

    $results_count = 5;
    $text_space = "\r\n";
    $message = "";
?>
<Response>
    <Say>Meeting information found, listing the top <?php echo $results_count ?> results.</Say>
<?php
    $results_counter = 0;
    for ($i = 0; $i < count($search_results); $i++) {  
        $result_day = $search_results[$i]->weekday_tinyint;
        $result_time = $search_results[$i]->start_time;
        
        if (isItPastTime($result_day, $result_time)) continue;
        
        $part_1 = str_replace("&", "&amp;", $search_results[$i]->meeting_name);
        $part_2 = str_replace("&", "&amp;", $GLOBALS['days_of_the_week'][$result_day]
                . ' ' . (new DateTime($result_time))->format('g:i A'));
        $part_3 = str_replace("&", "&amp;", $search_results[$i]->location_street 
                . " in " . $search_results[$i]->location_municipality 
                . ", " . $search_results[$i]->location_province);

        echo "<Pause length=\"1\"/>";
        echo "<Say>Result number " . ($results_counter + 1) . "</Say>";
        echo "<Say>" . $part_1 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>Starts at " . $part_2 . "</Say>";
        echo "<Pause length=\"1\"/>";
        echo "<Say>" . $part_3 . "</Say>";
        
        $message = $part_1 . $text_space . $part_2 . $text_space . $part_3;
        echo "<Sms>" . $message . "</Sms>";
        
        $results_counter++;
        if ($results_counter == $results_count) break;
    }
    
    echo "<Say>Thank you for calling, goodbye.</Say>"
?>
</Response>
