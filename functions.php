<?php
include 'config.php';
$google_maps_endpoint = "https://maps.googleapis.com/maps/api/geocode/json?key=" . $google_maps_api_key . "&address=";
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
        $map_details_response = get($GLOBALS['google_maps_endpoint'] . urlencode($address));
        $map_details = json_decode($map_details_response);
        $coordinates->location = $map_details->results[0]->formatted_address;
        $geometry = $map_details->results[0]->geometry->location;
        $coordinates->latitude = $geometry->lat;
        $coordinates->longitude = $geometry->lng;
    }

    return $coordinates;
}

function getTimeZoneForCoordinates($latitude, $longitude) {
    $time_zone = get($GLOBALS['timezone_lookup_endpoint'] . "&lat=" . $latitude . "&lng=" . $longitude);
    return json_decode($time_zone);
}

function helplineSearch($latitude, $longitude) {
    if ($GLOBALS['helpline_search_radius'] == null) {
        $GLOBALS['helpline_search_radius'] = 30;
    }
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetSearchResults&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=" . $GLOBALS['helpline_search_radius'];
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));

    if (isset($GLOBALS['helpline_search_unpublished']) && $GLOBALS['helpline_search_unpublished']) {
        $search_url = $search_url . "&advanced_published=0";

    }

    return json_decode(get($search_url));
}

function meetingSearch($latitude, $longitude, $search_type, $today, $tomorrow) {
    if ($search_type == 1) {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $today;
    } else if ($search_type == 2) {
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=start_time&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=-10&weekdays[]=" . $today . "&weekdays[]=" . $tomorrow;
    }

    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    return json_decode(get($search_url));
}

function getServiceBodyCoverage($latitude, $longitude) {
    $search_results = helplineSearch($latitude, $longitude);
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServiceBodies";
    $service_bodies = json_decode(get($bmlt_search_endpoint));
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

function getFormat($type) {
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetFormats";
    $formats = json_decode(get($bmlt_search_endpoint));
    for ($f = 0; $f <= count($formats); $f++) {
        if ($formats[$f]->key_string == $type) {
            return $formats[$f]->id;
        }
    }
}

function getHelplineVolunteer($service_body_int, $format_id, $tracker) {
    auth_bmlt();
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . '/client_interface/json/?switcher=GetSearchResults&services='.$service_body_int.'&formats='.$format_id.'&advanced_published=0';
    $volunteers = json_decode(get($bmlt_search_endpoint));
    if ($tracker > count($volunteers) - 1) {
        $tracker = count($volunteers) - 1;
    }

    return explode("#@-@#", $volunteers[$tracker]->contact_phone_1)[2];
}

function getHelplineBMLTRootServer() {
    return isset($GLOBALS['helpline_bmlt_root_server']) ? $GLOBALS['helpline_bmlt_root_server'] : $GLOBALS['bmlt_root_server'];
}

function auth_bmlt() {
    if (isset($GLOBALS['bmlt_username']) && isset($GLOBALS['bmlt_password'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, getHelplineBMLTRootServer() . '/local_server/server_admin/xml.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "admin_action=login&c_comdef_admin_login=".$GLOBALS['bmlt_username']."&c_comdef_admin_password=".$GLOBALS['bmlt_password']);
        curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);
    }
}

function get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close ($ch);
    return $data;
}
