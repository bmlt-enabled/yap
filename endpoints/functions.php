<?php
include_once '../config.php';
include_once 'logging.php';
include_once 'session.php';
static $version = "3.0.0-alpha2";
static $settings_whitelist = [
    'blocklist' => [ 'description' => '' , 'default' => '', 'overridable' => true],
    'bmlt_root_server' => [ 'description' => '' , 'default' => '', 'overridable' => false],
    'custom_query' => ['description' => '', 'default' => '&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width={SETTING_MEETING_SEARCH_RADIUS}&weekdays={DAY}', 'overridable' => true],
    'fallback_number' => [ 'description' => '' , 'default' => '', 'overridable' => true],
    'gather_hints' => [ 'description' => '' , 'default' => '', 'overridable' => true],
    'gather_language' => [ 'description' => '' , 'default' => 'en-US', 'overridable' => true],
    'grace_minutes' => [ 'description' => '' , 'default' => 15, 'overridable' => true],
    'helpline_bmlt_root_server' => [ 'description' => '' , 'default' => null, 'overridable' => false],
    'helpline_fallback' => [ 'description' => '', 'default' => '', 'overridable' => true],
    'helpline_search_radius' => [ 'description' => '' , 'default' => 30, 'overridable' => true],
    'helpline_search_unpublished' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'ignore_formats' => [ 'description' => '' , 'default' => null, 'overridable' => true],
    'include_map_link' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'infinite_searching' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'jft_option' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'language' => [ 'description' => '' , 'default' => 'en', 'overridable' => true],
    'language_selections' => [ 'description' => '', 'default' => '', 'overridable' => true],
    'location_lookup_bias' => [ 'description' => '' , 'default' => 'country:us', 'overridable' => true],
    'meeting_result_sort' => [ 'description' => '' , 'default' => MeetingResultSort::TODAY, 'overridable' => true],
    'meeting_search_radius' => [ 'description' => '' , 'default' => -50, 'overridable' => true],
    'postal_code_length' => [ 'description' => '' , 'default' => 5, 'overridable' => true],
    'province_lookup' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'result_count_max' => [ 'description' => '' , 'default' => 5, 'overridable' => true],
    'service_body_id' => [ 'description' => '', 'default' => null, 'overridable' => true],
    'sms_ask' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'sms_bias_bypass' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'sms_helpline_keyword' => ['description' => '', 'default' => 'talk', 'overridable' => true],
    'title' => [ 'description' => '' , 'default' => '', 'overridable' => true],
    'toll_free_province_bias' => [ 'description' => '' , 'default' => '', 'overridable' => true],
    'tomato_helpline_routing' => [ 'description' => '', 'default' => false, 'overridable' => true],
    'voice' => [ 'description' => '' , 'default' => 'woman', 'overridable' => true],
    'word_language' => [ 'description' => '' , 'default' => 'en-US', 'overridable' => true],
    'include_location_text' => [ 'description' => '' , 'default' => false, 'overridable' => true],
    'speech_gathering' => [ 'description' => '' , 'default' => true, 'overridable' => true],
];
checkBlacklist();
static $available_languages = [
    "en-US" => "English",
    "pig-latin" => "Igpay Atinlay",
    "pt-BR" => "Português (Brazil)",
    "fr-CA" => "Français (Canada)"
];

static $available_prompts = [
    "greeting",
    "voicemail_greeting"
];

foreach ($available_languages as $available_language_key => $available_language_value) {
    foreach ($available_prompts as $available_prompt) {
        $settings_whitelist[str_replace("-", "_", $available_language_key) . "_" . $available_prompt] = [ 'description' => '', 'default' => null, 'overridable' => true];
    }
}

include_once __DIR__.'/../lang/'.getWordLanguage().'.php';

$google_maps_endpoint = "https://maps.googleapis.com/maps/api/geocode/json?key=" . trim($google_maps_api_key);
$timezone_lookup_endpoint = "https://maps.googleapis.com/maps/api/timezone/json?key=" . trim($google_maps_api_key);
static $date_calculations_map = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
static $numbers = ["zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine"];
static $tomato_url = "https://tomato.na-bmlt.org/main_server";

class VolunteerInfo {
    public $title;
    public $start;
    public $end;
    public $weekday_id;
    public $weekday;
    public $sequence;
    public $time_zone;
    public $contact = SpecialPhoneNumber::UNKNOWN;
    public $color;
    public $gender;
    public $type = VolunteerType::PHONE;
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

class ServiceBodyConfiguration {
    public $service_body_id;
    public $service_body_name;
    public $volunteer_routing_enabled = false;
    public $volunteer_routing_redirect = false;
    public $volunteer_routing_redirect_id = 0;
    public $forced_caller_id_enabled = false;
    public $forced_caller_id_number = SpecialPhoneNumber::UNKNOWN;
    public $call_timeout = 20;
    public $volunteer_sms_notification_enabled = false;
    public $gender_routing_enabled = false;
    public $call_strategy = CycleAlgorithm::LOOP_FOREVER;
    public $primary_contact_number_enabled = false;
    public $primary_contact_number = SpecialPhoneNumber::UNKNOWN;
    public $primary_contact_email_enabled = false;
    public $primary_contact_email;
    public $moh = "https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical";
    public $moh_count = 1;
    public $sms_routing_enabled = false;
    public $sms_strategy = CycleAlgorithm::RANDOMIZER;
}

class CycleAlgorithm {
    const LOOP_FOREVER = 0;
    const CYCLE_AND_VOICEMAIL = 1;
    const RANDOMIZER = 2;
    const BLASTING = 3;
}

class DataType {
    const YAP_CONFIG = "_YAP_CONFIG_";
    const YAP_DATA = "_YAP_DATA_";
}

class SpecialPhoneNumber {
    const VOICE_MAIL = "voicemail";
    const UNKNOWN = "0000000000";
}

class SettingSource {
    const QUERYSTRING = "Transaction Override";
    const SESSION = "Session Override";
    const CONFIG = "config.php";
    const DEFAULT_SETTING = "Factory Default";
}

class VolunteerType {
    const PHONE = "PHONE";
    const SMS = "SMS";
}

class MeetingResultSort {
    const TODAY = 0;
}

class VolunteerGender {
    const UNSPECIFIED = 0;
    const MALE = 1;
    const FEMALE = 2;

    static function getGenderById($genderId) {
        switch ($genderId) {
            case 1:
                return "MALE";
            case 2:
                return "FEMALE";
            default:
                return "";
        }
    }
}

class NoVolunteersException extends Exception {}

class UpgradeAdvisor {
    private static $all_good = true;
    private static $settings = [
        'title',
        'bmlt_root_server',
        'google_maps_api_key',
        'twilio_account_sid',
        'twilio_auth_token',
        'bmlt_username',
        'bmlt_password'
    ];

    private static $database_settings = [
        'mysql_hostname',
        'mysql_username',
        'mysql_password',
        'mysql_database'
    ];

    private static $email_settings = [
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_secure',
        'smtp_from_address',
        'smtp_from_name'
    ];

    private static function isThere($setting) {
        return isset($GLOBALS[$setting]) && strlen($GLOBALS[$setting]) > 0;
    }

    private static function getState($status, $message) {
        return ["status"=>$status, "message"=>$message];
    }

    public static function getStatus() {
        foreach (UpgradeAdvisor::$settings as $setting) {
            if (!UpgradeAdvisor::isThere($setting)) {
                return UpgradeAdvisor::getState(false, "Missing required setting: " . $setting);
            }
        }

        $root_server_settings = json_decode(get(getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServerInfo"));

        if (strpos(getHelplineBMLTRootServer(), 'index.php')) {
            return UpgradeAdvisor::getState(false,"Your root server points to index.php. Please make sure to set it to just the root directory.");
        }

        if (!isset($root_server_settings)) {
            return UpgradeAdvisor::getState(false, "Your root server returned no server information.  Double-check that you have the right root server url.");
        }

        if ($root_server_settings[0]->semanticAdmin != "1") {
            return UpgradeAdvisor::getState(false, "Semantic Admin not enabled on your root server, be sure to set the variable mentioned here: https://bmlt.magshare.net/semantic/semantic-administration.");
        }

        $googleapi_settings = json_decode(get($GLOBALS['google_maps_endpoint'] . "&address=91409"));

        if ($googleapi_settings->status == "REQUEST_DENIED") {
            return UpgradeAdvisor::getState(false, "Your Google Maps API key came back with the following error. " . $googleapi_settings->error_message. " Please make sure you have the 'Google Maps Geocoding API' enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/");
        }

        $timezone_settings = json_decode(get($GLOBALS['timezone_lookup_endpoint'] . "&location=34.2011137,-118.475058&timestamp=" . time()));

        if ($timezone_settings->status == "REQUEST_DENIED") {
            return UpgradeAdvisor::getState(false, "Your Google Maps API key came back with the following error. " . $timezone_settings->errorMessage. " Please make sure you have the 'Google Time Zone API' enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/");
        }

        try {
            require_once 'twilio-client.php';
            foreach ($twilioClient->incomingPhoneNumbers->read() as $number) {
                if (basename($number->voiceUrl)) {
                    if (!strpos($number->voiceUrl, '.php')
                        && !strpos($number->voiceUrl, 'twiml')
                        && !strpos($number->voiceUrl, '/?')
                        && substr($number->voiceUrl, -1) !== "/") {
                        return UpgradeAdvisor::getState(false, $number->phoneNumber . " webhook should end either with `/` or `/index.php`");
                    }
                }
            }

        } catch ( \Twilio\Exceptions\ConfigurationException $e ) {
            log_debug("Missing Twilio Credentials");
        }

        if (has_setting('smtp_host')) {
            foreach (UpgradeAdvisor::$email_settings as $setting) {
                if (!UpgradeAdvisor::isThere($setting)) {
                    return UpgradeAdvisor::getState(false, "Missing required email setting: " . $setting);
                }
            }
        }

        if (isset($GLOBALS['mysql_hostname'])) {
            if ($GLOBALS['mysql_hostname'] == "localhost") {
                return UpgradeAdvisor::getState(false, "Use 127.0.0.1 instead of localhost.");
            }

            try {
                $conn = new PDO(sprintf("mysql:host=%s;dbname=%s", $GLOBALS['mysql_hostname'], $GLOBALS['mysql_database']), $GLOBALS['mysql_username'], $GLOBALS['mysql_password']);
            } catch (PDOException $e) {
                return UpgradeAdvisor::getState( false, $e->getMessage());
            }
        }

        if (UpgradeAdvisor::$all_good) {
            return UpgradeAdvisor::getState(true, "Ready To Yap!");
        }
    }
}


function checkBlacklist() {
    if (has_setting('blocklist') && strlen(setting('blocklist')) > 0 && isset($_REQUEST['Caller'])) {
        $blocklist_items = explode(",", setting('blocklist'));
        foreach ($blocklist_items as $blocklist_item) {
            if (strpos($blocklist_item, $_REQUEST['Caller']) === 0) {
                header("content-type: text/xml");
                echo "<?xml version='1.0' encoding='UTF-8'?>\n<Response><Reject/></Response>";
                exit;
            }
        }
    }
}

function getWordLanguage() {
    foreach ($GLOBALS['available_languages'] as $key => $available_language) {
        if ($key == setting('word_language')) {
            return $key;
        }
    }

    return "";
}

function word($name) {
    return isset($GLOBALS['override_' . $name]) ? $GLOBALS['override_' . $name] : $GLOBALS[$name];
}

function getNumberForWord($name) {
    $numbers = $GLOBALS['numbers'];
    for ($n = 0; $n < count($numbers); $n++) {
        if ($name == $numbers[$n]) {
            return $n;
        }
    }
}

function getWordForNumber($number) {
    return word($GLOBALS['numbers'][$number]);
}

function has_setting($name) {
    return !is_null(setting($name));
}

function setting($name) {
    if (isset($GLOBALS['settings_whitelist'][$name]) && $GLOBALS['settings_whitelist'][$name]['overridable']) {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        } else if (isset($_SESSION["override_" . $name])) {
            return $_SESSION["override_" . $name];
        }
    }

    if (isset($GLOBALS[$name])) {
        return $GLOBALS[$name];
    } else if (isset($GLOBALS['settings_whitelist'][$name]['default'])) {
        return $GLOBALS['settings_whitelist'][$name]['default'];
    }

    return null;
}

function setting_source($name) {
    if (isset($_REQUEST[$name])) {
        return SettingSource::QUERYSTRING;
    } else if (isset($_SESSION["override_" . $name])) {
        return SettingSource::SESSION;
    } else if (isset($GLOBALS[$name])) {
        return SettingSource::CONFIG;
    } else if (isset($GLOBALS['settings_whitelist'][$name]['default'])) {
        return SettingSource::DEFAULT_SETTING;
    } else {
        return "NOT SET";
    }
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
            . "&components=" . urlencode(setting('location_lookup_bias')));
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
    if (has_setting('sms_bias_bypass') && json_decode(setting('sms_bias_bypass'))) {
        return "";
    } elseif (isset($_REQUEST['ToState']) && strlen($_REQUEST['ToState']) > 0) {
        return $_REQUEST['ToState']; // Retrieved from Twilio metadata
    } elseif (has_setting('toll_free_province_bias')) {
        return setting('toll_free_province_bias'); // Override for Tollfree
    } else {
        return "";
    }
}

function helplineSearch($latitude, $longitude) {
    $helpline_search_radius = setting('helpline_search_radius');
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetSearchResults&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width=" . $helpline_search_radius;
    $search_url = str_replace("{LONGITUDE}", $longitude, str_replace("{LATITUDE}", $latitude, $bmlt_search_endpoint));

    if (has_setting('helpline_search_unpublished') && json_decode(setting('helpline_search_unpublished'))) {
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
    $bmlt_base_url = $GLOBALS['bmlt_root_server'] . "/client_interface/json/?switcher=GetSearchResults";
    $bmlt_search_endpoint = setting('custom_query');
    if (has_setting('ignore_formats')) {
        $bmlt_search_endpoint .= getFormatString(setting('ignore_formats'), true);
    }

    $magic_vars = ["{LATITUDE}", "{LONGITUDE}", "{DAY}"];
    $magic_swap = [$latitude, $longitude, $day];
    $custom_magic_vars = [];
    preg_match('/(\{SETTING_.*\})/U', $bmlt_search_endpoint, $custom_magic_vars);
    foreach ($custom_magic_vars as $custom_magic_var) {
        array_push($magic_vars, $custom_magic_var);
        array_push($magic_swap, setting(strtolower(preg_replace('/(\{SETTING_(.*)\})/U', "$2", $custom_magic_var))));
    }

    $search_url = str_replace($magic_vars, $magic_swap, $bmlt_search_endpoint);
    $final_url = $bmlt_base_url . $search_url;

    try {
        $search_response = get($final_url);
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
            if (strpos($bmlt_search_endpoint, "{DAY}")) {
                if (!isItPastTime($search_results[$i]->weekday_tinyint, $search_results[$i]->start_time)) {
                    array_push($filteredList, $search_results[$i]);
                }
            } else {
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
        str_replace("&", "&amp;", $filtered_list->location_text),
        str_replace("&", "&amp;", $filtered_list->location_street
                                        . ($filtered_list->location_municipality !== "" ? " " . $filtered_list->location_municipality : "")
                                        . ($filtered_list->location_province !== "" ? ", " . $filtered_list->location_province : "")));

}

function getServiceBodyCoverage($latitude, $longitude) {
    $search_results = helplineSearch($latitude, $longitude);
    $service_bodies = getServiceBodies();
    $already_checked = [];

    // Must do this because the BMLT returns an empty object instead of an empty array.
    if (!is_array($search_results)) {
        throw new Exception(word('helpline_no_results_found_retry'));
    }

    for ($j = 0; $j < count($search_results); $j++) {
        $service_body_id = $search_results[$j]->service_body_bigint;
        if (in_array($service_body_id, $already_checked)) continue;
        for ($i = 0; $i < count($service_bodies); $i++) {
            if ($service_bodies[$i]->id == $service_body_id) {
                if (strlen($service_bodies[$i]->helpline) > 0 || getServiceBodyConfiguration($service_bodies[$i]->id)->volunteer_routing_enabled) {
                    return $service_bodies[$i];
                } else {
                    array_push($already_checked, $service_bodies[$i]->id);
                }
            }
        }
    }
}

function getTimezoneList() {
    return DateTimeZone::listIdentifiers(DateTimeZone::ALL);
}

function setTimeZoneForLatitudeAndLongitude($latitude, $longitude) {
    $time_zone_results = getTimeZoneForCoordinates($latitude, $longitude);
    date_default_timezone_set($time_zone_results->timeZoneId);
}

function getMeetings($latitude, $longitude, $results_count, $today = null, $tomorrow = null) {
    if ($latitude != null & $longitude != null) {
        setTimeZoneForLatitudeAndLongitude($latitude, $longitude);
        $graced_date_time = (new DateTime())->modify(sprintf("-%s minutes", setting('grace_minutes')));
        if ($today == null) $today = $graced_date_time->format("w") + 1;
        if ($tomorrow == null) $tomorrow = $graced_date_time->modify("+24 hours")->format("w") + 1;
    }

    $meeting_results = new MeetingResults();
    $meeting_results = meetingSearch($meeting_results, $latitude, $longitude, $today);
    if (count($meeting_results->filteredList) < $results_count) {
        $meeting_results = meetingSearch($meeting_results, $latitude, $longitude, $tomorrow);
    }

    if ($meeting_results->originalListCount > 0) {
        if ($today == null) {
            setTimeZoneForLatitudeAndLongitude(
                $meeting_results->filteredList[0]->latitude,
                $meeting_results->filteredList[0]->longitude);

            $today = (new DateTime())->format("w") + 1;
        }

        $sort_day_start = setting('meeting_result_sort') == MeetingResultSort::TODAY
            ? $today : setting('meeting_result_sort');

        $days = array_column($meeting_results->filteredList, 'weekday_tinyint');
        $today_str = strval($sort_day_start);
        $meeting_results->filteredList = array_merge(
            array_splice($meeting_results->filteredList, array_search($today_str, $days)),
            array_splice($meeting_results->filteredList, 0)
        );
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
        ->modify(sprintf("-%s minutes", setting('grace_minutes')))
        ->modify($GLOBALS['date_calculations_map'][$meeting_day])->format("Y-m-d");
    $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $meeting_time))
        ->modify(sprintf("+%s minutes", setting('grace_minutes')));
    return $mod_meeting_datetime;
}

function getServiceBodies() {
    $bmlt_search_endpoint = getHelplineBMLTRootServer() . "/client_interface/json/?switcher=GetServiceBodies";
    return json_decode(get($bmlt_search_endpoint));
}

function getServiceBody($service_body_id) {
    $service_bodies = getServiceBodies();
    foreach ($service_bodies as $service_body) {
        if ($service_body->id == $service_body_id) return $service_body;
    }

    return null;
}

function getServiceBodyDetailForUser() {
    $service_bodies = admin_GetServiceBodiesForUser();
    $service_body_detail = getServiceBodies();
    $user_service_bodies = [];
    $service_bodies_check = isset($service_bodies) && !is_array($service_bodies)
        ? array($service_bodies) : $service_bodies;

    foreach ($service_bodies_check as $service_body) {
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
    log_debug("helpline_data_length:" . strlen($helpline_data));
    $data_bmlt_encoded .= "&meeting_field[]=contact_phone_1," . $helpline_data;

    return post($url, $data_bmlt_encoded, false, $_SESSION['username']);
}

function getAllHelplineData($data_type) {
    return getHelplineData(0, $data_type);
}

function getVolunteerRoutingEnabledServiceBodies() {
    $all_helpline_data = getAllHelplineData(DataType::YAP_CONFIG);
    $service_bodies = getServiceBodyDetailForUser();
    $helpline_enabled = array();

    for ($x = 0; $x < count($all_helpline_data); $x++) {
        $config = getServiceBodyConfigurationData($all_helpline_data[$x]);
        if ($config->volunteer_routing_enabled || $config->sms_routing_enabled) {
            for ($y = 0; $y < count($service_bodies); $y++) {
                if ($config->service_body_id == intval($service_bodies[$y]->id) ) {
                    $config->service_body_name = $service_bodies[$y]->name;
                    array_push($helpline_enabled, $config);
                }
            }
        }
    }

    return $helpline_enabled;
}

function getServiceBodyConfigurationData($helplineData) {
    $config = new ServiceBodyConfiguration();
    if (isset($helplineData)) {
        $data = $helplineData['data'][0];
        $config->service_body_id = $helplineData['service_body_id'];

        foreach ($data as $key => $value) {
            if (strpos($key, 'override_') === 0 && strlen($value) > 0) {
                $_SESSION[$key] = $value;
            }
        }

        $config->volunteer_routing_enabled = str_exists($data->volunteer_routing, "volunteers");
        $config->volunteer_routing_redirect = $data->volunteer_routing == "volunteers_redirect";
        $config->volunteer_routing_redirect_id = $config->volunteer_routing_redirect ? $data->volunteers_redirect_id : 0;
        $config->forced_caller_id_enabled = isset($data->forced_caller_id) && strlen($data->forced_caller_id) > 0;
        $config->forced_caller_id_number = $config->forced_caller_id_enabled ? $data->forced_caller_id : SpecialPhoneNumber::UNKNOWN;
        $config->call_timeout = isset($data->call_timeout) && strlen($data->call_timeout > 0) ? intval($data->call_timeout) : 20;
        $config->volunteer_sms_notification_enabled = isset($data->volunteer_sms_notification) && $data->volunteer_sms_notification != "no_sms";
        $config->gender_routing_enabled = isset($data->gender_routing) && intval($data->gender_routing) == 1;
        $config->call_strategy = isset($data->call_strategy) ? intval($data->call_strategy) : $config->call_strategy;
        $config->primary_contact_number_enabled = isset($data->primary_contact) && strlen($data->primary_contact) > 0;
        $config->primary_contact_number = $config->primary_contact_number_enabled ? $data->primary_contact : "";
        $config->primary_contact_email_enabled = isset($data->primary_contact_email) && strlen($data->primary_contact_email) > 0;
        $config->primary_contact_email = $config->primary_contact_email_enabled ? $data->primary_contact_email : "";
        $config->moh = isset($data->moh) && strlen($data->moh) > 0 ? $data->moh : $config->moh;
        $config->moh_count = count(explode(",", $config->moh));
        $config->sms_routing_enabled = $data->volunteer_routing == "volunteers_and_sms";
        $config->sms_strategy = isset($data->sms_strategy) ? intval($data->sms_strategy) : $config->sms_strategy;
    }

    return $config;
}

function getServiceBodyConfiguration($service_body_id) {
    $helplineData = getHelplineData($service_body_id, DataType::YAP_CONFIG);
    return getServiceBodyConfigurationData($helplineData[0]);
}

function getHelplineData($service_body_id, $data_type = DataType::YAP_DATA) {
    $helpline_data_items = [];
    auth_bmlt($GLOBALS['bmlt_username'], $GLOBALS['bmlt_password'], true);
    $helpline_data = json_decode(get(getHelplineBMLTRootServer()
                                     . "/client_interface/json/?switcher=GetSearchResults"
                                     . (($service_body_id != 0) ? "&services=" . $service_body_id : "")
                                     . "&meeting_key=meeting_name&meeting_key_value=" . $data_type
                                     . "&advanced_published=0"));

    if ($helpline_data != null) {
        foreach ( $helpline_data as $item ) {
            $json_string = str_replace( ';', ',', html_entity_decode( explode( '#@-@#', $item->contact_phone_1 )[2] ) );
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
        ->modify($GLOBALS['date_calculations_map'][$shift_day])->format("Y-m-d");
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

function getHelplineVolunteersActiveNow($service_body_int, $volunteer_type = VolunteerType::PHONE, $volunteer_gender = VolunteerGender::UNSPECIFIED) {
    try {
        $volunteers = json_decode( getHelplineSchedule( $service_body_int ) );
        $activeNow  = [];
        for ( $v = 0; $v < count( $volunteers ); $v ++ ) {
            date_default_timezone_set( $volunteers[ $v ]->time_zone );
            $current_time = new DateTime();
            if ( ($current_time >= ( new DateTime( $volunteers[ $v ]->start ) )
                 && $current_time <= ( new DateTime( $volunteers[ $v ]->end ) ) )
                 && (!isset($volunteers[$v]->type) || str_exists($volunteers[$v]->type, $volunteer_type ))
                 && ($volunteer_gender !== VolunteerGender::UNSPECIFIED && isset($volunteers[$v]->gender) && $volunteer_gender == $volunteers[$v]->gender)) {
                array_push( $activeNow, $volunteers[ $v ] );
            }
        }

        return $activeNow;
    } catch (NoVolunteersException $nve) {
        throw $nve;
    }
}

function getHelplineVolunteer($service_body_int,
                              $tracker,
                              $cycle_algorithm = CycleAlgorithm::LOOP_FOREVER,
                              $volunteer_type = VolunteerType::PHONE,
                              $volunteer_gender = VolunteerGender::UNSPECIFIED) {
    try {
        $volunteers = getHelplineVolunteersActiveNow( $service_body_int, $volunteer_type, $volunteer_gender);
        log_debug("getHelplineVolunteer():: activeVolunteers: " . var_export($volunteers, true) . ", service_body_id: " . $service_body_int . ", volunteer_type: " . $volunteer_type);
        if ( isset( $volunteers ) && count( $volunteers ) > 0 ) {
            if ( $cycle_algorithm == CycleAlgorithm::CYCLE_AND_VOICEMAIL ) {
                if ( $tracker > count( $volunteers ) - 1 ) {
                    return SpecialPhoneNumber::VOICE_MAIL;
                }

                return $volunteers[ $tracker ]->contact;
            } else if ( $cycle_algorithm == CycleAlgorithm::LOOP_FOREVER ) {
                return $volunteers[ $tracker % count( $volunteers ) ]->contact;
            } else if ( $cycle_algorithm == CycleAlgorithm::RANDOMIZER ) {
                return $volunteers[ rand( 0, count( $volunteers ) - 1 ) ]->contact;
            } else if ( $cycle_algorithm == CycleAlgorithm::BLASTING ) {
                $volunteers_all = [];
                foreach ($volunteers as $volunteer) {
                    array_push($volunteers_all, $volunteer->contact);
                }

                return join(",", $volunteers_all);
            }
        } else {
            return SpecialPhoneNumber::UNKNOWN;
        }
    } catch (NoVolunteersException $nve) {
        return SpecialPhoneNumber::UNKNOWN;
    }
}

function getHelplineSchedule($service_body_int) {
    $helplineData = getHelplineData($service_body_int);
    if (count($helplineData) > 0) {
        $volunteers    = $helplineData[0];
        $finalSchedule = getVolunteerInfo( $volunteers );

        usort( $finalSchedule, function ( $a, $b ) {
            return $a->sequence > $b->sequence;
        } );

        return json_encode( $finalSchedule );
    } else {
        throw new NoVolunteersException();
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
        if (isset($volunteer->volunteer_enabled) && $volunteer->volunteer_enabled &&
            isset($volunteer->volunteer_phone_number) && strlen($volunteer->volunteer_phone_number) > 0) {
            $volunteerShiftSchedule = dataDecoder($volunteer->volunteer_shift_schedule);
            foreach ($volunteerShiftSchedule as $vsi) {
                $volunteerInfo             = new VolunteerInfo();
                $volunteerInfo->type       = isset($vsi->type) ? $vsi->type : $volunteerInfo->type;
                $volunteerInfo->title      = $volunteer->volunteer_name . " (" . $volunteerInfo->type . ") " . (isset($volunteer->volunteer_gender) ? VolunteerGender::getGenderById($volunteer->volunteer_gender) : "");
                $volunteerInfo->time_zone  = $vsi->tz;
                $volunteerInfo->start      = getNextShiftInstance( $vsi->day, $vsi->start_time, $volunteerInfo->time_zone )->format( "Y-m-d H:i:s" );
                $volunteerInfo->end        = getNextShiftInstance( $vsi->day, $vsi->end_time, $volunteerInfo->time_zone )->format( "Y-m-d H:i:s" );
                $volunteerInfo->weekday_id = $vsi->day;
                $volunteerInfo->weekday    = $GLOBALS['days_of_the_week'][ $vsi->day ];
                $volunteerInfo->sequence   = $v;
                $volunteerInfo->contact    = $volunteer->volunteer_phone_number;
                $volunteerInfo->color      = "#" . getNameHashColorCode($volunteerInfo->title);
                $volunteerInfo->gender     = isset($volunteer->volunteer_gender) ? $volunteer->volunteer_gender : VolunteerGender::UNSPECIFIED;
                array_push( $finalSchedule, $volunteerInfo );
            }
        }
    }

    return $finalSchedule;
}

function getNameHashColorCode($str) {
    $code = dechex(crc32($str));
    $code = substr($code, 0, 6);
    return $code;
}

function dataEncoder($dataObject) {
    return base64_encode(json_encode($dataObject));
}

function dataDecoder($dataString) {
    return json_decode(base64_decode($dataString));
}

function str_exists($subject, $needle) {
    return strpos($subject, $needle) !== false;
}

function sort_on_field(&$objects, $on, $order = 'ASC') {
    usort($objects, function($a, $b) use ($on, $order) {
        return $order === 'DESC'
            ? "return -strcmp(\$a->{$on},\$b->{$on});"
            : "return strcmp(\$a->{$on},\$b->{$on});";
    });
}

function getHelplineBMLTRootServer() {
    if (json_decode(setting('tomato_helpline_routing'))) {
        return $GLOBALS['tomato_url'];
    } else if (has_setting('helpline_bmlt_root_server')) {
        return setting( 'helpline_bmlt_root_server' );
    } else {
        return $GLOBALS['bmlt_root_server'];
    }
}

function auth_bmlt($username, $password, $master = false) {
    $ch = curl_init();
    $auth_endpoint = (isset($GLOBALS['alt_auth_method']) && $GLOBALS['alt_auth_method'] ? '/index.php' : '/local_server/server_admin/xml.php');
    curl_setopt($ch, CURLOPT_URL, getHelplineBMLTRootServer() . $auth_endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_COOKIEJAR,  ($master ? 'master' : $username) . '_cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap' );
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'admin_action=login&c_comdef_admin_login='.$username.'&c_comdef_admin_password='.urlencode($password));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER,  false);
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return preg_match('/^OK$/', $res) == 1;
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
    log_debug($url);
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
    log_debug($url);
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
    log_debug($url);
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

function sms_chunk_split($msg) {
    $chunk_width = 1575;
    $chunks = wordwrap($msg, $chunk_width, '\n');
    return explode('\n', $chunks);
}

function get_jft($sms = false) {
    $d = new DOMDocument();
    $d->validateOnParse = true;
    $result = null;

    if (setting('word_language') == 'en-US') {
        $url = 'https://www.jftna.org/jft/';
        $jft_language_dom_element = "table";
        $copyright_info = '';
        $preg_search_lang = "\r\n";
        $preg_replace_lang = "\n\n";
    } else if (setting('word_language') == 'pt-BR' || setting('word_language') == 'pt-PT') {
        $url = 'http://www.na.org.br/meditacao';
        $jft_language_dom_element = '*[@class=\'content-home\']';
        $copyright_info = 'Todos os direitos reservados à: http://www.na.org.br';
        $preg_search_lang = "\r\n";
        $preg_replace_lang = "\n";
    } else if (setting('word_language') == 'es-ES') {
        $url = 'https://forozonalatino.org/sxh';
        $jft_language_dom_element = '*[@id=\'sx-wrapper\']';
        $copyright_info = 'Servicio del Foro Zonal Latinoamericano, Copyright 2017 NA World Services, Inc. Todos los Derechos Reservados.';
        $preg_search_lang = "\r\n\s";
        $preg_replace_lang = " ";
    } else if (setting('word_language') == 'fr-FR' || setting('word_language') == 'ca-FR') {
        $url = 'https://jpa.narcotiquesanonymes.org';
        $jft_language_dom_element = '*[@class=\'contenu-principal\']';
        $copyright_info = 'Copyright (c) 2007-'.date("Y").', NA World Services, Inc. All Rights Reserved';
        $preg_search_lang = "\r\n";
        $preg_replace_lang = "\n\n";
    }

    $jft = new DOMDocument;
    libxml_use_internal_errors(true);
    $d->loadHTML(get($url));
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    $xpath = new DOMXpath($d);
    $body = $xpath->query("//$jft_language_dom_element");
    foreach ($body as $child) {
        $jft->appendChild($jft->importNode($child, true));
    }
    $result .= $jft->saveHTML();

    $stripped_results = strip_tags( $result );
    $without_tabs     = str_replace( "\t", "", $stripped_results );
    $trim_results     = trim($without_tabs);
    if ($sms == true) {
        $without_htmlentities = html_entity_decode($trim_results);
        $without_extranewlines = preg_replace("/[$preg_search_lang]+/", "$preg_replace_lang", $without_htmlentities);
        $message = sms_chunk_split($without_extranewlines);
        $finalMessage  = array();
        if (count($message) > 1) {
            for ($i = 0; $i < count($message); $i++) {
                $jft_message = "(" .($i + 1). " of " .count($message). ")\n" .$message[$i];
                array_push($finalMessage,$jft_message);
            }
        }
        else {
            array_push($finalMessage,$message);
        }
        return $finalMessage;
    }
    else {
        $final_array = explode( "\n", $trim_results );
        array_push($final_array, $copyright_info);
        return $final_array;
    }
}

function getIvrResponse($redirected_from = null, $prior_digit = null, $expected_exacts = array(), $expected_likes = array()) {
    $response = "0";

    if (isset($_REQUEST['Digits'])) {
        $response = $_REQUEST['Digits'];
    } elseif (isset($_REQUEST['SpeechResult'])) {
        $response = $_REQUEST['SpeechResult'];
    }

    if (count($expected_exacts) > 0 || count($expected_likes) > 0) {
        $found_at_least_once = false;
        foreach ($expected_exacts as $expected_exact) {
            if ($expected_exact == $response) $found_at_least_once = true;
        }

        if (!$found_at_least_once) {
            foreach ($expected_likes as $expected_like) {
                if (str_exists($response, $expected_like)) {
                    $found_at_least_once = true;
                }
            }
        }

        if (!$found_at_least_once) {
            $qs = $prior_digit != null ? "?Digits=" . $prior_digit : "";

            echo "<Response><Say voice=\"" . $GLOBALS['voice'] . "\" language=\"" . $GLOBALS['language'] . "\">" . word('you_might_have_invalid_entry') . "</Say><Redirect>" . $redirected_from . $qs . "</Redirect></Response>";
            exit();
        }
    }

    return $response;
}

function getInputType() {
    if (has_setting('speech_gathering') && json_decode(setting('speech_gathering'))) {
        $inputType = "speech dtmf";
    } else {
        $inputType = "dtmf";
    }
    return $inputType;
}

function getPressWord() {
    if (has_setting('speech_gathering') && json_decode(setting('speech_gathering'))) {
        $pressWord = word('press_or_say');
    } else {
        $pressWord = word('press');
    }
    return $pressWord;
}
