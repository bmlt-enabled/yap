<?php
include_once 'config.php';
$word_language = isset($GLOBALS['word_language']) ? $GLOBALS['word_language'] : 'en-US';
include_once 'lang/'.$word_language.'.php';

$maps_enpoint = "https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1"; 
$timezone_lookup_endpoint = "https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1"; 
# BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
static $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

class VolunteerInfo {
    public $title;
    public $start;
    public $end;
    public $weekday_id;
    public $weekday;
    public $sequence;
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

function word($name) {
    return isset($GLOBALS['override_' . $name]) ? $GLOBALS['override_' . $name] : $GLOBALS[$name];
}

function getCoordinatesForAddress($address) {
    $coordinates = new Coordinates();

    if (strlen($address) > 0) {
        $map_details_response = get($GLOBALS['maps_endpoint']
            . "&address="
            . urlencode($address)
            . (isset($GLOBALS['location_lookup_bias']) ? "&country=" . urlencode($GLOBALS['location_lookup_bias']) : ""));
        $map_details = json_decode($map_details_response);
        if ($map_details[0]->place_id != null) { 
            $coordinates->location  = $map_details[0]->display_name; 
            $coordinates->latitude  = $map_details[0]->lat; 
            $coordinates->longitude = $map_details[0]->lon; 
        }
    }

    return $coordinates;
}

function getTimeZoneForCoordinates($latitude, $longitude) {
    $time_zone = get($GLOBALS['timezone_lookup_endpoint'] . "&location=" . $latitude . "," . $longitude . "&timestamp=" . time());
    return json_decode($time_zone);
}

function getProvince() {
    if (isset($GLOBALS['sms_bias_bypass']) && $GLOBALS['sms_bias_bypass']) {
        return "";
    } elseif (isset($_REQUEST['ToState']) && strlen($_REQUEST['ToState']) > 0) {
        return $_REQUEST['ToState']; // Retrieved from Twilio metadata
    } elseif (isset($GLOBALS['toll_free_province_bias'])) {
        return $GLOBALS['toll_free_province_bias']; // Override for Tollfree
    } else {
        return "";
    }
}

function getGatherLanguage() {
    return isset($GLOBALS["gather_language"]) ? $GLOBALS["gather_language"] : "en-US";
}

function getGatherHints() {
    return isset($GLOBALS["gather_hints"]) ? $GLOBALS["gather_hints"] : "";
}

function helplineSearch($latitude, $longitude) {
    $helpline_search_radius = isset($GLOBALS['helpline_search_radius']) ? $GLOBALS['helpline_search_radius'] : 30;
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetSearchResults&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=" . $helpline_search_radius;
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));

    if (isset($GLOBALS['helpline_search_unpublished']) && $GLOBALS['helpline_search_unpublished']) {
        $search_url = $search_url . "&advanced_published=0";
        if (isset($GLOBALS['bmlt_username']) && isset($GLOBALS['bmlt_password'])) {
            auth_bmlt($GLOBALS['bmlt_username'], $GLOBALS['bmlt_password']);
        }
    }

    return json_decode(get($search_url));
}

function getFormatString($formats, $ignore = false, $helpline = false) {
    $formatsArray = getIdsFormats($formats, $helpline);
    $finalString = "";
    for ($i = 0; $i < count($formatsArray); $i++) {
        $finalString .= "&formats[]=" . ($ignore ? "-" : "") . $formatsArray[$i];
    }

    return $finalString;
}

function meetingSearch($meeting_results, $latitude, $longitude, $day) {
	$meeting_search_radius = isset($GLOBALS['meeting_search_radius']) ? $GLOBALS['meeting_search_radius'] : -50;
    $bmlt_search_endpoint = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=" . $meeting_search_radius . "&weekdays=" . $day;
    if (isset($GLOBALS['ignore_formats'])) {
        $bmlt_search_endpoint .= getFormatString($GLOBALS['ignore_formats'], true);
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

function getResultsString($filtered_list) {
    return array(
        str_replace("&", "&amp;", $filtered_list->meeting_name),
        str_replace("&", "&amp;", $GLOBALS['days_of_the_week'][$filtered_list->weekday_tinyint]
                                        . ' ' . (new DateTime($filtered_list->start_time))->format('g:i A')),
        str_replace("&", "&amp;", $filtered_list->location_street
                                        . ($filtered_list->location_municipality !== "" ? " " . $filtered_list->location_municipality : "")
                                        . ($filtered_list->location_province !== "" ? ", " . $filtered_list->location_province : "")));

}

function getServiceBodyCoverage($latitude, $longitude) {
    $search_results = helplineSearch($latitude, $longitude);
    $service_bodies = getServiceBodies();
    $already_checked = [];

    for ($j = 0; $j < count($search_results); $j++) {
        $service_body_id = $search_results[$j]->service_body_bigint;
        if (in_array($service_body_id, $already_checked)) continue;
        for ($i = 0; $i < count($service_bodies); $i++) {
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

function getGraceMinutes() {
    return isset($GLOBALS['grace_minutes']) ? $GLOBALS['grace_minutes'] : 15;
}

function getMeetings($latitude, $longitude, $results_count, $today = null, $tomorrow = null) {
    $time_zone_results = getTimeZoneForCoordinates($latitude, $longitude);
    # Could be wired up to use multiple roots in the future by using a parameter to select
    date_default_timezone_set($time_zone_results->timeZoneId);

    $graced_date_time = (new DateTime())->modify(sprintf("-%s minutes", getGraceMinutes()));
    if ($today == null) $today = $graced_date_time ->format( "w" ) + 1;

    $meeting_results = new MeetingResults();
    $meeting_results = meetingSearch($meeting_results, $latitude, $longitude, $today);
    if (count($meeting_results->filteredList) < $results_count) {
        if ($tomorrow == null) $tomorrow = $graced_date_time->modify("+24 hours")->format("w") + 1;

        $meeting_results = meetingSearch($meeting_results, $latitude, $longitude, $tomorrow);
    }
    return $meeting_results;
}

function isItPastTime($meeting_day, $meeting_time) {
    $next_meeting_time = getNextMeetingInstance($meeting_day, $meeting_time);
    $time_zone_time = new DateTime();
    return $next_meeting_time <= $time_zone_time;
}

function getNextMeetingInstance($meeting_day, $meeting_time) {
    $mod_meeting_day = (new DateTime())
        ->modify(sprintf("-%s minutes", getGraceMinutes()))
        ->modify($GLOBALS['days_of_the_week'][$meeting_day])->format("Y-m-d");
    $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $meeting_time))
        ->modify(sprintf("+%s minutes", getGraceMinutes()));
    return $mod_meeting_datetime;
}

function getServiceBodies() {
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServiceBodies";
    return json_decode(get($bmlt_search_endpoint));
}

function getYapBasedHelplines() {
    $service_bodies = getServiceBodies();
    $yapHelplines = [];
    foreach ($service_bodies as $service_body) {
        if (isset($service_body->helpline) && strpos($service_body->helpline, 'yap') !== false) {
            $r_service_body = $service_body;
            if (strpos($service_body->helpline, '->') !== false) {
                $r_service_body->id = explode('->', $service_body->helpline)[1];
                $r_service_body->name = $r_service_body->name . " (Redirected To Service Body: " . $r_service_body->id . ")";
            }

            array_push($yapHelplines, $r_service_body);
        }
    }

    return json_encode($yapHelplines);
}

function getNextShiftInstance($shift_day, $shift_time, $shift_tz) {
    date_default_timezone_set($shift_tz->timeZoneId);
    if ($shift_time == "23:59:00") {
    	$shift_time = "00:00:00";
    }
    return getNextMeetingInstance($shift_day, $shift_time);
}

function getIdsFormats($types, $helpline = false) {
    $typesArray = explode(",", $types);
    $finalFormats = array();
    $bmlt_search_endpoint = ($helpline ? getHelplineBMLTRootServer() : $GLOBALS['bmlt_root_server']) . "/client_interface/json/?switcher=GetFormats";
    $formats = json_decode(get($bmlt_search_endpoint));
    for ($t = 0; $t < count($typesArray); $t++) {
        for ( $f = 0; $f < count( $formats ); $f ++ ) {
            if ( $formats[ $f ]->key_string == $typesArray[$t] ) {
                array_push($finalFormats, $formats[ $f ]->id);
            }
        }
    }

    return $finalFormats;
}

function getHelplineVolunteersActiveNow($service_body_int) {
    $volunteers = json_decode(getHelplineSchedule($service_body_int));
    $activeNow = [];
    for ($v = 0; $v < count($volunteers); $v++) {
        date_default_timezone_set($volunteers[$v]->time_zone->timeZoneId);
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

        if (isset($volunteers[$tracker]->contact) && $volunteers[$tracker]->contact != "") {
            return explode("#@-@#", $volunteers[$tracker]->contact)[2];
        }
    }

    return "000000000";
}

function getFormatResults($service_body_int, $format_codes) {
    if (isset($GLOBALS['bmlt_username']) && isset($GLOBALS['bmlt_password'])) {
        if (auth_bmlt($GLOBALS['bmlt_username'], $GLOBALS['bmlt_password'])) {
            $bmlt_search_endpoint = getHelplineBMLTRootServer() . '/client_interface/json/?switcher=GetSearchResults&services=' . $service_body_int . getFormatString($format_codes, false, true) . '&advanced_published=0';
            return get($bmlt_search_endpoint);
        }
    }

    return null;
}

function getHelplineSchedule($service_body_int) {
    $volunteers = json_decode(getFormatResults($service_body_int, 'HV'));
    list($volunteerNames, $finalSchedule) = getVolunteerInfo($volunteers);
    $finalSchedule = flattenSchedule(array_count_values($volunteerNames), $finalSchedule);

    usort($finalSchedule, function($a, $b) {
        return $a->sequence > $b->sequence;
    });

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
        $volunteerInfo->sequence = $volunteers[$v]->location_info != null ? intval($volunteers[$v]->location_info) : 0;
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

function auth_bmlt($username, $password) {
    $ch = curl_init();
    $auth_endpoint = (isset($GLOBALS['alt_auth_method']) && $GLOBALS['alt_auth_method'] ? '/index.php' : '/local_server/server_admin/xml.php');
    curl_setopt($ch, CURLOPT_URL, getHelplineBMLTRootServer() . $auth_endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap' );
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'admin_action=login&c_comdef_admin_login='.$username.'&c_comdef_admin_password='.$password);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return !strpos($res, "NOT AUTHORIZED");
}

function get($url) {
    error_log($url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap' );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    $errorno = curl_errno($ch);
    curl_close($ch);
    if ($errorno > 0) {
        throw new Exception(curl_strerror($errorno));
    }

    return $data;
}

function post($url, $payload) {
    error_log($url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap" );
    $data = curl_exec($ch);
    $errorno = curl_errno($ch);
    curl_close($ch);
    if ($errorno > 0) {
        throw new Exception(curl_strerror($errorno));
    }

    return $data;
}

function async_post($url, $payload)  {
    error_log($url);
    $parts = parse_url($url);

    if (isset($parts['port'])) {
        $port = $parts['port'];
    } else if ($parts['scheme'] == 'https') {
        $port = 443;
    } else {
        $port = 80;
    }

    $host = ($parts['scheme'] == 'https' ? "ssl://" : "") . $parts['host'];
    $fp = fsockopen($host, $port, $errno, $errstr, 30);
    assert(($fp!=0), "Couldn’t open a socket to ".$url." (".$errstr.")");
    $post_data = json_encode($payload);

    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
    $out.= "Host: ".$parts['host']."\r\n";
    $out.= "User-Agent: Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap\r\n";
    $out.= "Content-Type: application/json\r\n";
    $out.= "Content-Length: ".strlen($post_data)."\r\n";
    $out.= "Connection: Close\r\n\r\n";
    if (isset($post_data)) $out.= $post_data;

    fwrite($fp, $out);
    fclose($fp);
}
