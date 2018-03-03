<?php
include 'config.php';
$google_maps_endpoint = "https://maps.googleapis.com/maps/api/geocode/json?key=" . $google_maps_api_key . "&address=";
static $timezone_lookup_endpoint = "https://api.timezonedb.com/v2/get-time-zone?key=M007J6ZZ6OI1&format=json&by=position";
# BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
static $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

class VolunteerInfo {
    public $title;
    public $start;
    public $end;
    public $weekday_id;
    public $weekday;
    public $origin_duration;
    public $origin_start_time;
    public $time_zone;
    public $contact;
}

class Coordinates {
    public $location;
    public $latitude;
    public $longitude;
}

class DurationInterval {
    public $hours;
    public $minutes;
    public $seconds;

    public function getDurationFormat() {
        return $this->hours . " hours " . $this->minutes . " minutes " . $this->seconds . " seconds";
    }
}

class MeetingResults {
    public $originalListCount = 0;
    public $filteredList = [];
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

function getProvince() {
    if (isset($_REQUEST['ToState']) && strlen($_REQUEST['ToState']) > 0) {
        return $_REQUEST['ToState']; // Retrieved from Twilio metadata
    } elseif ($GLOBALS['toll_free_province_bias'] != null) {
        return $GLOBALS['toll_free_province_bias']; // Override for Tollfree
    } else {
        return "";
    }
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

function meetingSearch($meeting_results, $latitude, $longitude, $search_type, $day) {
	$meetingsearch = isset($GLOBALS['meeting_search_radius']) ? $GLOBALS['meeting_search_radius'] : -50;
    if ($search_type == 1) { // Close to you
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=" . $meetingsearch . "&weekdays=" . $day;
    } else if ($search_type == 2) { // Upcoming
        $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_keys=start_time&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=" . $meetingsearch . "&weekdays=" . $day;
    }

    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));
    try {
        $search_response = get($search_url);
    } catch (Exception $e) {
        if ($e->getMessage() == "Couldn't resolve host name") {
            throw $e;
        } else {
            $search_response = "[]";
        }
    }

    $search_results = json_decode($search_response);
    $meeting_results->originalListCount += count($search_results);

    $filteredList = $meeting_results->filteredList;
    if ($search_response !== "{}") {
        for ($i = 0; $i < count($search_results); $i++) {
            if (!isItPastTime($search_results[$i]->weekday_tinyint, $search_results[$i]->start_time)) {
                array_push($filteredList, $search_results[$i]);
            }
        }
    } else {
        $meeting_results->originalListCount += 0;
    }

    $meeting_results->filteredList = $filteredList;
    return $meeting_results;
}

function getServiceBodyCoverage($latitude, $longitude) {
    $search_results = helplineSearch($latitude, $longitude);
    $service_bodies = getServiceBodies();
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

function getServiceBodies() {
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServiceBodies";
    return json_decode(get($bmlt_search_endpoint));
}

function getYapBasedHelplines() {
    $service_bodies = getServiceBodies();
    $yapHelplines = [];
    foreach ($service_bodies as $service_body) {
        if (isset($service_body->helpline) && $service_body->helpline == "yap") {
            array_push($yapHelplines, $service_body);
        }
    }

    return json_encode($yapHelplines);
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

function getNextShiftInstance($shift_day, $shift_time, $shift_tz) {
    date_default_timezone_set($shift_tz->zoneName);
    return getNextMeetingInstance($shift_day, $shift_time);
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

function getHelplineVolunteersActiveNow($service_body_int) {
    $volunteers = json_decode(getHelplineSchedule($service_body_int));
    $activeNow = [];
    for ($v = 0; $v < count($volunteers); $v++) {
        date_default_timezone_set($volunteers[$v]->time_zone->zoneName);
        $current_time = new DateTime();
        if ($current_time >= (new DateTime($volunteers[$v]->start)) && $current_time <= (new DateTime($volunteers[$v]->end))) {
            array_push($activeNow, $volunteers[$v]);
        }
    }

    return $activeNow;
}

function getHelplineVolunteer($service_body_int, $tracker) {
    $volunteers = getHelplineVolunteersActiveNow($service_body_int);
    if (count($volunteers) > 0) {
        if ($tracker > count($volunteers) - 1) {
            $tracker = count($volunteers) - 1;
        }

        return explode("#@-@#", $volunteers[$tracker]->contact)[2];
    } else {
        return "000000000";
    }
}

function getHelplineSchedule($service_body_int) {
    auth_bmlt();
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . '/client_interface/json/?switcher=GetSearchResults&services='.$service_body_int.'&formats='.getFormat('HV').'&advanced_published=0';
    $volunteers = json_decode(get($bmlt_search_endpoint));
    list($volunteerNames, $finalSchedule) = getVolunteerInfo($volunteers);
    $volunteerShiftCounts = array_count_values($volunteerNames);
    $finalSchedule = flattenSchedule($volunteerShiftCounts, $finalSchedule);

    return json_encode($finalSchedule);
}

function filterOut($volunteers) {
    $volunteers_array = json_decode($volunteers);
    for ($v = 0; $v < count($volunteers_array); $v++) {
        unset($volunteers_array[$v]->contact);
    }

    return json_encode($volunteers_array);
}

function flattenSchedule($volunteerShiftCounts, $finalSchedule) {
    foreach (array_keys($volunteerShiftCounts) as $volunteerName) {
        // Ensure that they are only listed once
        if ($volunteerShiftCounts[$volunteerName] == 1) {
            // Get the information for their shift
            $finalSchedule = walkShifts($finalSchedule, $volunteerName);
        }
    }
    return $finalSchedule;
}

function walkShifts($finalSchedule, $volunteerName) {
    foreach ($finalSchedule as $volunteerInfoItem) {
        if ($volunteerName == $volunteerInfoItem->title) {
            // Figure out what day is represented already
            $dayRepresented = $volunteerInfoItem->weekday_id;
            $finalSchedule = spreadSchedule($finalSchedule, $dayRepresented, $volunteerInfoItem);
        }
    }
    return $finalSchedule;
}

function spreadSchedule($finalSchedule, $dayRepresented, $volunteerInfoItem) {
    for ($d = 1; $d <= 7; $d++) {
        // Loop through days, don't read the day that is already represented
        if ($dayRepresented !== $d) {
            $volunteerClone = clone $volunteerInfoItem;
            $volunteerClone->start = getNextShiftInstance($d, $volunteerInfoItem->origin_start_time, $volunteerClone->time_zone)->format("Y-m-d H:i:s");
            $volunteerClone->end = date_add(new DateTime($volunteerClone->start), date_interval_create_from_date_string($volunteerClone->origin_duration->getDurationFormat()))->format("Y-m-d H:i:s");
            $volunteerClone->weekday = $GLOBALS['days_of_the_week'][$d];
            $volunteerClone->weekday_id = $d;
            array_push($finalSchedule, $volunteerClone);
        }
    }
    return $finalSchedule;
}

function getVolunteerInfo($volunteers) {
    $finalSchedule = [];
    $volunteerNames = [];

    for ($v = 0; $v < count($volunteers); $v++) {
        $volunteerInfo = new VolunteerInfo();
        $volunteerInfo->title = $volunteers[$v]->meeting_name;
        $volunteerInfo->time_zone = getTimeZoneForCoordinates($volunteers[$v]->latitude, $volunteers[$v]->longitude);
        $volunteerInfo->start = getNextShiftInstance($volunteers[$v]->weekday_tinyint, $volunteers[$v]->start_time, $volunteerInfo->time_zone)->format("Y-m-d H:i:s");
        $volunteerInfo->origin_duration = getDurationInterval($volunteers[$v]->duration_time);
        $volunteerInfo->end = date_add(new DateTime($volunteerInfo->start), date_interval_create_from_date_string($volunteerInfo->origin_duration->getDurationFormat()))->format("Y-m-d H:i:s");
        $volunteerInfo->weekday_id = intval($volunteers[$v]->weekday_tinyint);
        $volunteerInfo->weekday = $GLOBALS['days_of_the_week'][$volunteers[$v]->weekday_tinyint];
        $volunteerInfo->origin_start_time = $volunteers[$v]->start_time;
        $volunteerInfo->contact = $volunteers[$v]->contact_phone_1;
        array_push($volunteerNames, $volunteers[$v]->meeting_name);
        array_push($finalSchedule, $volunteerInfo);
    }
    return array($volunteerNames, $finalSchedule);
}

function countOccurrences($initialSchedule, $volunteerName) {
    $occurrences = 0;
    for ($v = 0; $v <= count($initialSchedule); $v++) {
        if ($initialSchedule[$v]["title"] == $volunteerName) {
            $occurrences++;
        }
    }

    return $occurrences > 1;
}

function getDurationInterval($duration_time) {
    $durationArray = explode(":", $duration_time);
    $durationInterval = new DurationInterval();
    $durationInterval->hours = $durationArray[0];
    $durationInterval->minutes = $durationArray[1];
    $durationInterval->seconds = $durationArray[2];
    return $durationInterval;
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
    error_log($url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    $errorno = curl_errno($ch);
    curl_close($ch);
    if ($errorno > 0) {
        throw new Exception(curl_strerror($errorno));
    }

    return $data;
}
