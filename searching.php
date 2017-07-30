<?php
    header("content-type: text/xml");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
    
    $timezone_lookup_endpoint = "https://api.timezonedb.com/v2/get-time-zone?key=M007J6ZZ6OI1&format=json&by=position";
    $latitude = $_REQUEST['Latitude'];
    $longitude = $_REQUEST['Longitude'];
    $time_zone = file_get_contents($timezone_lookup_endpoint . "&lat=" . $latitude . "&lng=" . $longitude);
    error_log($time_zone);
    $time_zone_results = json_decode($time_zone);
    date_default_timezone_set($time_zone_results->zoneName);
    
    # Could be wired up to use multiple roots in the future by using a parameter to select
    $day = date("l");
    $today = date("w") + 1;
    $tomorrow = (new DateTime('tomorrow'))->format("w") + 1;
    
    $bmlt_root_server = "http://na-bmlt.org/_/sandwich";
    # BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
    $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]; 
    $search_url = str_replace("{LONGITUDE}", $longitude, 
                  str_replace("{LATITUDE}", $latitude, meetingSearch($_REQUEST['SearchType'])));
    error_log($search_url);
    $search_results_raw = file_get_contents($search_url);
    $search_results = json_decode($search_results_raw);

    $results_count = 5;
    $text_space = "\r\n";
    $message = "";
    
    function meetingSearch($search_type) {
        global $bmlt_root_server, $today, $tomorrow;
        if ($search_type == 1) {
            return $bmlt_search_endpoint = $bmlt_root_server . "/client_interface/json/?switcher=GetSearchResults&sort_keys=distance_in_miles&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-5&weekdays[]=" . $today;
        } else {
            return $bmlt_search_endpoint = $bmlt_root_server . "/client_interface/json/?switcher=GetSearchResults&sort_keys=start_time&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-5&weekdays[]=" . $today . "&weekdays[]=" . $tomorrow;
        }
    }
    
    function isItPastTime($meeting_day, $meeting_time) {
        $next_meeting_time = getNextMeetingInstance($meeting_day, $meeting_time);
        $time_zone_time = new DateTime();
        error_log( "next meeting time: " . $next_meeting_time->format("Y-m-d H:i:s"));
        error_log("time zone time: " . $time_zone_time->format("Y-m-d H:i:s"));
        return $next_meeting_time <= $time_zone_time;
    }
    
    function getNextMeetingInstance($meeting_day, $meeting_time) {
        $mod_meeting_day = (new DateTime($days_of_the_week[$meeting_day]))->format("Y-m-d");
        $mod_meeting_datetime = new DateTime($mod_meeting_day . " " . $meeting_time);
        return $mod_meeting_datetime;
    }
?>
<Response>
    <Say>Meeting information found, listing the top <?php echo $results_count ?> results.</Say>
<?php
    $results_counter = 0;
    for ($i = 0; $i < count($search_results); $i++) {  
        $result_day = $search_results[$i]->weekday_tinyint;
        $result_time = $search_results[$i]->start_time;
        
        if (isItPastTime($result_day, $result_time)) continue;
        
        $part_1 = $search_results[$i]->meeting_name;
        $part_2 = $days_of_the_week[$result_day]
                . ' ' . (new DateTime($result_time))->format('g:i A');
        $part_3 = $search_results[$i]->location_street 
                . " in " . $search_results[$i]->location_municipality 
                . ", " . $search_results[$i]->location_province;

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
