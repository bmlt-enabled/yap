<?php
    date_default_timezone_set('America/New_York');

    $day = date("l");
    $today = date("w") + 1;
    $tomorrow = (new DateTime('tomorrow'))->format("w") + 1;
    # BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
    $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]; 
    $google_maps_endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=";
    $google_timezone_endpoint = "https://maps.googleapis.com/maps/api/timezone/json?key=AIzaSyDA7EYTfk7o3tUrswgD2i6DHajw1Dn7Vho&";
    
    // TODO: Potentially allow different BMLT roots
    $bmlt_root_server = "http://bmlt-aggregator.archsearch.org/eccbc87e4b5ce2fe28308fd9f2a7baf3/bmltfed/main_server";
    #$bmlt_option_sort_key=  ["distance_in_miles,start_time", "start_time,distance_in_miles"];
    $bmlt_search_endpoint = $bmlt_root_server . "/client_interface/json/?switcher=GetSearchResults&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-20&weekdays[]=" . $today . "&weekdays[]=" . $tomorrow;

    $results_count = 3;
    $text_space = "\r\n";
    
    function isItPastTime($meeting_day, $meeting_time) {
        $next_meeting_time = getNextMeetingInstance($meeting_day, $meeting_time);
        $server_time = new DateTime();
        echo "next meeting time: " . $next_meeting_time->format("Y-m-d H:i:s") . "<br/>";
        echo "server date: " . $server_time->format("Y-m-d H:i:s") . "<br/>";
        return $next_meeting_time <= $server_time;
    }
    
    function getNextMeetingInstance($meeting_day, $meeting_time) {
        global $days_of_the_week;
        $mod_meeting_day = (new DateTime($days_of_the_week[$meeting_day]))->format("Y-m-d");
        $mod_meeting_datetime = new DateTime($mod_meeting_day . " " . $meeting_time);
        return $mod_meeting_datetime;
    }
