<?php

static $bmlt_root_server = "http://na-bmlt.org/_/sandwich";
static $google_maps_endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=";
static $timezone_lookup_endpoint = "https://api.timezonedb.com/v2/get-time-zone?key=M007J6ZZ6OI1&format=json&by=position";
# BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
static $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]; 

class Coordinates {
    public $location;
    public $latitude;
    public $longitude;
}

function getCoordinatesForZipCode($zip) {
    $coordinates = new Coordinates();
    error_log($GLOBALS['google_maps_endpoint']);
	
    if (strlen($zip) > 0) {
        $map_details_response = file_get_contents($GLOBALS['google_maps_endpoint'] . $zip);
        $map_details = json_decode($map_details_response);
        $coordinates->location = $map_details->results[0]->formatted_address;
        $geometry = $map_details->results[0]->geometry->location;
        $coordinates->latitude = $geometry->lat;
        $coordinates->longitude = $geometry->lng;
    }
    
    return $coordinates;
}

function getTimeZoneForCoordinates($latitude, $longitude) {
    $time_zone = file_get_contents($GLOBALS['timezone_lookup_endpoint'] . "&lat=" . $latitude . "&lng=" . $longitude);
    return json_decode($time_zone);
}

function meetingSearch($latitude, $longitude, $search_type, $today, $tomorrow) {
    if ($search_type == 1) {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=distance_in_miles&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $today;
    } else if ($search_type == 2) {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=start_time&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $today . "&weekdays[]=" . $tomorrow;
    } else {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=distance_in_miles&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10";
    }
    
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    error_log($search_url);
    return json_decode(file_get_contents($search_url));
}

function getServiceBodyCoverage($latitude, $longitude) {
    $service_body_id = meetingSearch($latitude, $longitude, 3, null, null)[0]->service_body_bigint;
    $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetServiceBodies";
    $service_bodies = json_decode(file_get_contents($bmlt_search_endpoint));
    for ($i = 0; $i <= count($service_bodies); $i++) {
        if ($service_bodies[$i]->id == $service_body_id) {
            return $service_bodies[$i];
        }
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
    $mod_meeting_day = (new DateTime($GLOBALS['days_of_the_week'][$meeting_day]))->format("Y-m-d");
    $mod_meeting_datetime = new DateTime($mod_meeting_day . " " . $meeting_time);
    return $mod_meeting_datetime;
}
