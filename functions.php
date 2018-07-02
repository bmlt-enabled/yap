<?php
include_once 'config.php';
$word_language_selected = isset($GLOBALS['word_language']) ? $GLOBALS['word_language'] : 'en-US';
include_once 'lang/'.$word_language_selected.'.php';

$google_maps_endpoint = "https://maps.googleapis.com/maps/api/geocode/json?key=" . trim($google_maps_api_key);
$timezone_lookup_endpoint = "https://maps.googleapis.com/maps/api/timezone/json?key" . trim($google_maps_api_key);
# BMLT uses weird date formatting, Sunday is 1.  PHP uses 0 based Sunday.
static $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

class VolunteerInfo {
    public $title;
    public $start;
    public $end;
    public $weekday_id;
    public $weekday;
    public $sequence;
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

class CycleAlgorithm {
    const CYCLE_AND_FAILOVER = 0;
    const LOOP_FOREVER = 1;
    const RANDOMIZER = 2;
}

class DataType {
    const YAP_CONFIG = "_YAP_CONFIG_";
    const YAP_DATA = "_YAP_DATA_";
}

function word($name) {
    return isset($GLOBALS['override_' . $name]) ? $GLOBALS['override_' . $name] : $GLOBALS[$name];
}

function getConferenceName($service_body_id) {
    return $service_body_id . "_" . rand(1000000, 9999999) . "_" . time();
}

function getCoordinatesForAddress($address) {
    $coordinates = new Coordinates();

    if (strlen($address) > 0) {
        $map_details_response = get($GLOBALS['google_maps_endpoint']
            . "&address="
            . urlencode($address)
            . (isset($GLOBALS['location_lookup_bias']) ? "&components=" . urlencode($GLOBALS['location_lookup_bias']) : ""));
        $map_details = json_decode($map_details_response);
        if (count($map_details->results) > 0) {
            $coordinates->location  = $map_details->results[0]->formatted_address;
            $geometry               = $map_details->results[0]->geometry->location;
            $coordinates->latitude  = $geometry->lat;
            $coordinates->longitude = $geometry->lng;
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
            auth_bmlt($GLOBALS['bmlt_username'], $GLOBALS['bmlt_password'], true);
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

function getTimezoneList() {
    return DateTimeZone::listIdentifiers(DateTimeZone::ALL);
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

function getServiceBodyDetailForUser() {
    $service_bodies = admin_GetServiceBodiesForUser();
    $service_body_detail = getServiceBodies();
    $user_service_bodies = [];
    foreach ($service_bodies as $service_body) {
        foreach ($service_body_detail as $service_body_detail_item) {
            if ($service_body->id == $service_body_detail_item->id) {
                array_push($user_service_bodies, $service_body_detail_item);
            }
        }
    }

    return $user_service_bodies;
}

function admin_GetServiceBodiesForUser() {
    $url = getHelplineBMLTRootServer() . "/local_server/server_admin/json.php?admin_action=get_permissions";
    return json_decode(get($url, $_SESSION['username']))->service_body;
}

function admin_PersistHelplineData($helpline_data_id = 0, $service_body_id, $data, $data_type = DataType::YAP_DATA) {
    $url = getHelplineBMLTRootServer() . "/local_server/server_admin/json.php";
    if ($helpline_data_id == 0) {
        $data_bmlt_encoded = "admin_action=add_meeting&service_body_id=" . $service_body_id . "&meeting_field[]=meeting_name,".$data_type;
    } else {
        $data_bmlt_encoded = "admin_action=modify_meeting&meeting_id=" . $helpline_data_id;
    }

    $helpline_data = str_replace(",", ";", $data);
    error_log("helpline_data_length:" . strlen($helpline_data));
    $data_bmlt_encoded .= "&meeting_field[]=comments," . $helpline_data;

    return post($url, $data_bmlt_encoded, false, $_SESSION['username']);
}

function getAllHelplineData($data_type) {
    return getHelplineData(0, $data_type);
}

function getVolunteerRoutingEnabledServiceBodies() {
    $all_helpline_data = getAllHelplineData("_YAP_CONFIG_");
    $service_bodies = getServiceBodies();
    $helpline_enabled = [];

    for ($x = 0; $x < count($all_helpline_data); $x++) {
        if (isset($all_helpline_data[$x]['data'][0]->volunteer_routing_enabled) && boolval($all_helpline_data[$x]['data'][0]->volunteer_routing_enabled)) {
            for ($y = 0; $y < count($service_bodies); $y++) {
                if ( $all_helpline_data[ $x ]['service_body_id'] == intval($service_bodies[$y]->id) ) {
                    $all_helpline_data[ $x ]['service_body_name'] = $service_bodies[$y]->name;
                    array_push($helpline_enabled, $all_helpline_data[$x]);
                }
            }
        }
    }

    return $helpline_enabled;
}

function getHelplineData($service_body_id, $data_type = DataType::YAP_DATA) {
    $helpline_data_items = [];
    auth_bmlt($GLOBALS['bmlt_username'], $GLOBALS['bmlt_password'], true);
    $helpline_data = json_decode(get(getHelplineBMLTRootServer()
                                     . "/client_interface/json/?switcher=GetSearchResults"
                                     . (($service_body_id > 0) ? "&services=" . $service_body_id : "")
                                     . "&meeting_key=meeting_name&meeting_key_value=" . $data_type
                                     . "&advanced_published=0"));

    if ($helpline_data != null) {
        foreach ( $helpline_data as $item ) {
            $json_string = str_replace( ';', ',', html_entity_decode( explode( '#@-@#', $item->comments )[2] ) );
            array_push($helpline_data_items, [
                'id'              => intval( $item->id_bigint ),
                'data'            => json_decode( $json_string )->data,
                'service_body_id' => intval( $item->service_body_bigint )
            ]);
        }
    }

    return $helpline_data_items;
}

function getNextShiftInstance($shift_day, $shift_time, $shift_tz) {
    date_default_timezone_set($shift_tz);
    $mod_meeting_day = (new DateTime())
        ->modify($GLOBALS['days_of_the_week'][$shift_day])->format("Y-m-d");
    $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $shift_time));
    return $mod_meeting_datetime;
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
        date_default_timezone_set($volunteers[$v]->time_zone);
        $current_time = new DateTime();
        if ($current_time >= (new DateTime($volunteers[$v]->start)) && $current_time <= (new DateTime($volunteers[$v]->end))) {
            array_push($activeNow, $volunteers[$v]);
        }
    }

    return $activeNow;
}

function getHelplineVolunteer($service_body_int, $tracker, $cycle_algorithm = CycleAlgorithm::CYCLE_AND_FAILOVER) {
    $volunteers = getHelplineVolunteersActiveNow($service_body_int);
    if ( isset($volunteers) && count( $volunteers ) > 0 ) {
        if ($cycle_algorithm == CycleAlgorithm::CYCLE_AND_FAILOVER) {
            if ( $tracker > count( $volunteers ) - 1 ) {
                // TODO: Put failover number here, voicemail?
                return "000000000";
            }

            return $volunteers[ $tracker ]->contact;
        }
        else if ($cycle_algorithm == CycleAlgorithm::LOOP_FOREVER) {
            return $volunteers[$tracker % count( $volunteers )]->contact;
        } else if ($cycle_algorithm == CycleAlgorithm::RANDOMIZER) {
            return $volunteers[rand(0, count( $volunteers ) - 1)]->contact;
        }
    }

    return "000000000";
}

function getHelplineSchedule($service_body_int) {
    if (count(getHelplineData($service_body_int)) > 0) {
        $volunteers    = getHelplineData( $service_body_int )[0];
        $finalSchedule = getVolunteerInfo( $volunteers );

        usort( $finalSchedule, function ( $a, $b ) {
            return $a->sequence > $b->sequence;
        } );

        return json_encode( $finalSchedule );
    } else {
        return new StdClass();
    }
}

function filterOut($volunteers) {
    $volunteers_array = json_decode($volunteers);
    for ($v = 0; $v < count($volunteers_array); $v++) {
        unset($volunteers_array[$v]->contact);
    }

    return json_encode($volunteers_array);
}

function getVolunteerInfo($volunteers) {
    $finalSchedule = [];

    for ($v = 0; $v < count($volunteers['data']); $v++) {
        $volunteer = $volunteers['data'][$v];
        if (isset($volunteer->volunteer_enabled) && $volunteer->volunteer_enabled) {
            $volunteerShiftSchedule = dataDecoder($volunteer->volunteer_shift_schedule);
            foreach ($volunteerShiftSchedule as $vsi) {
                $volunteerInfo             = new VolunteerInfo();
                $volunteerInfo->title      = $volunteer->volunteer_name;
                $volunteerInfo->time_zone  = $vsi->tz;
                $volunteerInfo->start      = getNextShiftInstance( $vsi->day, $vsi->start_time, $volunteerInfo->time_zone )->format( "Y-m-d H:i:s" );
                $volunteerInfo->end        = getNextShiftInstance( $vsi->day, $vsi->end_time, $volunteerInfo->time_zone )->format( "Y-m-d H:i:s" );
                $volunteerInfo->weekday_id = $vsi->day;
                $volunteerInfo->weekday    = $GLOBALS['days_of_the_week'][ $vsi->day ];
                $volunteerInfo->sequence   = $v;
                $volunteerInfo->contact    = $volunteer->volunteer_phone_number;
                array_push( $finalSchedule, $volunteerInfo );
            }
        }
    }

    return $finalSchedule;
}

function dataEncoder($dataObject) {
    return base64_encode(json_encode($dataObject));
}

function dataDecoder($dataString) {
    return json_decode(base64_decode($dataString));
}

function sort_on_field(&$objects, $on, $order = 'ASC') {
    $comparer = ($order === 'DESC')
        ? "return -strcmp(\$a->{$on},\$b->{$on});"
        : "return strcmp(\$a->{$on},\$b->{$on});";
    usort($objects, create_function('$a,$b', $comparer));
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

function auth_bmlt($username, $password, $master = false) {
    $ch = curl_init();
    $auth_endpoint = (isset($GLOBALS['alt_auth_method']) && $GLOBALS['alt_auth_method'] ? '/index.php' : '/local_server/server_admin/xml.php');
    curl_setopt($ch, CURLOPT_URL, getHelplineBMLTRootServer() . $auth_endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR,  ($master ? 'master' : $username) . '_cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap' );
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'admin_action=login&c_comdef_admin_login='.$username.'&c_comdef_admin_password='.$password);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER,  false);
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return !strpos($res,  "NOT AUTHORIZED");
}

function check_auth($username) {
    $cookie_file = $username . '_cookie.txt';
    if (file_exists($cookie_file)) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, getHelplineBMLTRootServer() . '/local_server/server_admin/xml.php?admin_action=get_permissions' );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie_file );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie_file );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        $res = curl_exec( $ch );
        curl_close( $ch );
    } else {
        $res = "NOT AUTHORIZED";
    }

    return !preg_match('/NOT AUTHORIZED/', $res);
}

function logout_auth($username) {
    session_unset();
    $cookie_file = $username . '_cookie.txt';
    if (file_exists($cookie_file)) {
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, getHelplineBMLTRootServer() . '/local_server/server_admin/xml.php?admin_action=logout' );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie_file );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie_file );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap' );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        $res = curl_exec( $ch );
        curl_close( $ch );
    } else {
        $res = "BYE;";
    }

    return !preg_match('/BYE/', $res);
}

function get($url, $username = 'master') {
    error_log($url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $username . '_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $username . '_cookie.txt');
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

function post($url, $payload, $is_json = true, $username = 'master') {
    error_log($url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $username . '_cookie.txt');
    curl_setopt($ch, CURLOPT_COOKIEJAR, $username . '_cookie.txt');
    $post_field_count = $is_json ? 1 : substr_count($payload, '=');
    curl_setopt($ch, CURLOPT_POST, $post_field_count);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $is_json ? json_encode($payload) : $payload);
    if ($is_json) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
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
