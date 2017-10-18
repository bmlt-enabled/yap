<?php
include 'config.php';
static $google_maps_endpoint = "http://maps.googleapis.com/maps/api/geocode/json?address=";
static $timezone_lookup_endpoint = "https://api.timezonedb.com/v2/get-time-zone?key=M007J6ZZ6OI1&format=json&by=position";
# BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
static $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]; 

class Coordinates {
    public $location;
    public $latitude;
    public $longitude;
}

function getCoordinatesForAddress($address) {
    $coordinates = new Coordinates();
	
    if (strlen($address) > 0) {
        $map_details_response = file_get_contents($GLOBALS['google_maps_endpoint'] . urlencode($address));
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

function helplineSearch($latitude, $longitude) {
    $helpline_search_radius = 100; #in miles
    $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=distance_in_miles&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=30";
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    return json_decode(file_get_contents($search_url));
}

function meetingSearch($latitude, $longitude, $search_type, $today, $tomorrow) {
    if ($search_type == 1) {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=distance_in_miles&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $today;
    } else if ($search_type == 2) {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=start_time&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $today . "&weekdays[]=" . $tomorrow;
    } 
    
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    return json_decode(file_get_contents($search_url));
}

function getServiceBodyCoverage($latitude, $longitude) {
    $search_results = helplineSearch($latitude, $longitude, 3, null, null);
    $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetServiceBodies";
    $service_bodies = json_decode(file_get_contents($bmlt_search_endpoint));
    $already_checked = [];
    
    for ($j = 0; $j <= count($search_results); $j++) { 
        $service_body_id = $search_results[$j]->service_body_bigint;
        if (in_array($service_body_id, $already_checked)) continue;
        for ($i = 0; $i <= count($service_bodies); $i++) {
            if ($service_bodies[$i]->id == $service_body_id) {
                if (strlen($service_bodies[$i]->helpline) > 0) {
                    return $service_bodies[$i];
                } else {
                    array_push($already_checked, $service_bodies[$i]->id);
                }
            }
        }
    }
}

function isItPastTime($meeting_day, $meeting_time) {
    $next_meeting_time = getNextMeetingInstance($meeting_day, $meeting_time);
    $time_zone_time = new DateTime();
    return $next_meeting_time <= $time_zone_time;
}

function getNextMeetingInstance($meeting_day, $meeting_time) {
    $mod_meeting_day = (new DateTime($GLOBALS['days_of_the_week'][$meeting_day]))->format("Y-m-d");
    $mod_meeting_datetime = new DateTime($mod_meeting_day . " " . $meeting_time);
    return $mod_meeting_datetime;
}
