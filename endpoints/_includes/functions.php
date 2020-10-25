<?php
if (!file_exists('../config.php')) {
    header(sprintf('Location: %s', str_exists($_SERVER['REQUEST_URI'], 'admin') ? 'installer.php' : 'admin/installer.php'), true, 302);
    exit();
}
if (isset($_GET["ysk"])) {
    session_id($_GET["ysk"]);
}
session_start();
require_once(!getenv("ENVIRONMENT") ? __DIR__ . '/../../config.php' : __DIR__ . '/../../config.' . getenv("ENVIRONMENT") . '.php');
require_once 'constants.php';
require_once 'migrations.php';
require_once 'queries.php';
require_once 'logging.php';
static $version  = "4.0.0";
static $settings_allowlist = [
    'announce_servicebody_volunteer_routing' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'blocklist' => [ 'description' => 'Allows for blocking a specific list of phone numbers https://github.com/bmlt-enabled/yap/wiki/Blocklist' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'bmlt_root_server' => [ 'description' => 'The root server to use.' , 'default' => '', 'overridable' => false, 'hidden' => false],
    'bmlt_auth' => [ 'description' => '' , 'default' => true, 'overridable' => false, 'hidden' => false ],
    'call_routing_filter' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'config' => [ 'description' => '' , 'default' => null, 'overridable' => true, 'hidden' => true],
    'custom_extensions' => ['description' => '', 'default' => [0 => ''], 'overridable' => true, 'hidden' => false],
    'custom_query' => ['description' => '', 'default' => '&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width={SETTING_MEETING_SEARCH_RADIUS}&weekdays={DAY}', 'overridable' => true, 'hidden' => false],
    'digit_map_search_type' => [ 'description' => '', 'default' => ['1' => SearchType::VOLUNTEERS, '2' => SearchType::MEETINGS, '3' => SearchType::JFT, '8' => SearchType::VOICEMAIL_PLAYBACK, '9' => SearchType::DIALBACK], 'overridable' => true, 'hidden' => false],
    'digit_map_location_search_method' => [ 'description' => '', 'default' => ['1' => LocationSearchMethod::VOICE, '2' => LocationSearchMethod::DTMF, '3' => SearchType::JFT], 'overridable' => true, 'hidden' => false],
    'extension_dial' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'fallback_number' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'gather_hints' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'gather_language' => [ 'description' => '' , 'default' => 'en-US', 'overridable' => true, 'hidden' => false],
    'grace_minutes' => [ 'description' => '' , 'default' => 15, 'overridable' => true, 'hidden' => false],
    'helpline_bmlt_root_server' => [ 'description' => '' , 'default' => null, 'overridable' => false, 'hidden' => false],
    'helpline_fallback' => [ 'description' => '', 'default' => '', 'overridable' => true, 'hidden' => false],
    'helpline_search_radius' => [ 'description' => '' , 'default' => 30, 'overridable' => true, 'hidden' => false],
    'ignore_formats' => [ 'description' => '' , 'default' => null, 'overridable' => true, 'hidden' => false],
    'include_format_details' => [ 'description' => '' , 'default' => [], 'overridable' => true, 'hidden' => false],
    'include_distance_details'  => [ 'description' => '' , 'default' => null, 'overridable' => true, 'hidden' => false],
    'include_location_text' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'include_map_link' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'include_unpublished' => [ 'description' => '' , 'default' => 0, 'overridable' => true, 'hidden' => false],
    'infinite_searching' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'initial_pause' => [ 'description' => '' , 'default' => 2, 'overridable' => true, 'hidden' => false],
    'jft_option' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'language' => [ 'description' => '' , 'default' =>  'en-US', 'overridable' => true, 'hidden' => false],
    'language_selections' => [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
    'location_lookup_bias' => [ 'description' => '' , 'default' => 'country:us', 'overridable' => true, 'hidden' => false],
    'meeting_result_sort' => [ 'description' => '' , 'default' => MeetingResultSort::TODAY, 'overridable' => true, 'hidden' => false],
    'meeting_search_radius' => [ 'description' => '' , 'default' => -50, 'overridable' => true, 'hidden' => false],
    'mobile_check' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'postal_code_length' => [ 'description' => '' , 'default' => 5, 'overridable' => true, 'hidden' => false],
    'province_lookup' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'province_lookup_list' => [ 'description' => '' , 'default' => [], 'overridable' => true, 'hidden' => false],
    'result_count_max' => [ 'description' => '' , 'default' => 5, 'overridable' => true, 'hidden' => false],
    'say_links' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'service_body_id' => [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
    'service_body_config_id' => [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
    'sms_ask' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_bias_bypass' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_combine' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_helpline_keyword' => ['description' => '', 'default' => 'talk', 'overridable' => true, 'hidden' => false],
    'sms_summary_page' => ['description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'speech_gathering' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'suppress_voice_results' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'time_format' => ['description' => '', 'default' => 'g:i A', 'overridable' => true, 'hidden' => false],
    'title' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'toll_province_bias' => [ 'description' => '' , 'default' => null, 'overridable' => true, 'hidden' => false],
    'toll_free_province_bias' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'tomato_helpline_routing' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'tomato_meeting_search' => [ 'description' => '', 'default' => false, 'overridable' => true, 'hidden' => false],
    'tomato_url' => [ 'description' => '' , 'default' => 'https://tomato.bmltenabled.org/main_server', 'overridable' => true, 'hidden' => false],
    'twilio_account_sid' => [ 'description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
    'twilio_auth_token' => [ 'description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
    'voice' => [ 'description' => '', 'default' => 'Polly.Kendra', 'overridable' => true, 'hidden' => false],
    'voicemail_playback_grace_hours' => [ 'description' => '', 'default' => 48, 'overridable' => true, 'hidden' => false],
    'word_language' => [ 'description' => '', 'default' => 'en-US', 'overridable' => true, 'hidden' => false]
];
static $available_languages = [
    "en-US" => "English",
    "en-AU" => "English (Australian)",
    "es-US" => "Español (United States)",
    "pig-latin" => "Igpay Atinlay",
    "pt-BR" => "Português (Brazil)",
    "fr-CA" => "Français (Canada)",
    "it-IT" => "Italian (Italy)"
];

static $available_prompts = [
    "greeting",
    "voicemail_greeting"
];

foreach ($available_languages as $available_language_key => $available_language_value) {
    foreach ($available_prompts as $available_prompt) {
        $settings_allowlist[str_replace("-", "_", $available_language_key) . "_" . $available_prompt] = [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false];
        $settings_allowlist[str_replace("-", "_", $available_language_key) . "_voice"] = [ 'description' => '', 'default' => 'alice', 'overridable' => true, 'hidden' => false];
    }
}
require_once 'session.php';
checkBlocklist();
if (has_setting('config')) {
    include_once __DIR__ . '/../../config_'.setting('config').'.php';
}
include_once __DIR__ . '/../../lang/' .getWordLanguage().'.php';
$GLOBALS['short_language'] = getWordLanguage() === "da-DK" ? "dk" : explode("-", getWordLanguage())[0];

$google_maps_endpoint = "https://maps.googleapis.com/maps/api/geocode/json?key=" . trim($google_maps_api_key);
$timezone_lookup_endpoint = "https://maps.googleapis.com/maps/api/timezone/json?key=" . trim($google_maps_api_key);
static $date_calculations_map = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
static $numbers = ["zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine"];

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
class SearchType
{
    const NONE = -1;
    const VOLUNTEERS = 1;
    const MEETINGS = 2;
    const JFT = 3;
    const CUSTOM_EXTENSIONS = 998;
    const VOICEMAIL_PLAYBACK = 999;
    const DIALBACK = 1000;
}

class AlertId
{
    const STATUS_CALLBACK_MISSING = 1;
}

class CacheType
{
    const SESSION = 1;
    const DATABASE = 2;
}

class AdminInterfaceRights
{
    const MANAGE_USERS = 1;
}

class EventId
{
    const VOLUNTEER_SEARCH = 1;
    const MEETING_SEARCH = 2;
    const JFT_LOOKUP = 3;
    const VOICEMAIL = 4;
    const VOLUNTEER_DIALED = 5;
    const VOLUNTEER_ANSWERED = 6;
    const VOLUNTEER_REJECTED = 7;
    const VOLUNTEER_NOANSWER = 8;
    const VOLUNTEER_ANSWERED_BUT_CALLER_HUP = 9;
    const CALLER_IN_CONFERENCE = 10;
    const VOLUNTEER_HUP = 11;
    const VOLUNTEER_IN_CONFERENCE = 12;
    const CALLER_HUP = 13;
    const MEETING_SEARCH_LOCATION_GATHERED = 14;
    const HELPLINE_ROUTE = 15;
    const VOICEMAIL_PLAYBACK = 16;
    const DIALBACK = 17;
    const PROVINCE_LOOKUP_LIST = 18;

    public static function getEventById($id)
    {
        switch ($id) {
            case self::VOLUNTEER_SEARCH:
                return "Volunteer Search";
            case self::MEETING_SEARCH:
                return "Meeting Search";
            case self::JFT_LOOKUP:
                return "JFT Lookup";
            case self::VOICEMAIL:
                return "Voicemail";
            case self::VOLUNTEER_DIALED:
                return "Volunteer Dialed";
            case self::VOLUNTEER_ANSWERED:
                return "Volunteer Answered";
            case self::VOLUNTEER_REJECTED:
                return "Volunteer Rejected Call";
            case self::VOLUNTEER_NOANSWER:
                return "Volunteer No Answer";
            case self::VOLUNTEER_ANSWERED_BUT_CALLER_HUP:
                return "Volunteer Answered but Caller Hungup";
            case self::CALLER_IN_CONFERENCE:
                return "Caller Waiting for Volunteer";
            case self::VOLUNTEER_HUP:
                return "Volunteer Hungup";
            case self::VOLUNTEER_IN_CONFERENCE:
                return "Volunteer Connected To Caller";
            case self::CALLER_HUP:
                return "Caller Hungup";
            case self::MEETING_SEARCH_LOCATION_GATHERED:
                return "Meeting Search Location Gathered";
            case self::HELPLINE_ROUTE:
                return "Helpline Route";
            case self::VOICEMAIL_PLAYBACK:
                return "Voicemail Playback";
            case self::DIALBACK:
                return "Dialback";
            case self::PROVINCE_LOOKUP_LIST:
                return "Province Lookup List";
        }
    }
}

class LocationSearchMethod
{
    const NONE = -1;
    const VOICE = 4;
    const DTMF = 5;
}

class VolunteerInfo
{
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
    public $shadow = VolunteerShadowOption::UNSPECIFIED;
    public $responder = VolunteerResponderOption::UNSPECIFIED;
    public $type = VolunteerType::PHONE;
    public $language;
}

class Volunteer
{
    public $phoneNumber;
    public $volunteerInfo;

    public function __construct($phoneNumber, $volunteerInfo = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->volunteerInfo = $volunteerInfo;
    }
}

class Coordinates
{
    public $location;
    public $latitude;
    public $longitude;
}

class DurationInterval
{
    public $hours;
    public $minutes;
    public $seconds;

    public function getDurationFormat()
    {
        return $this->hours . " hours " . $this->minutes . " minutes " . $this->seconds . " seconds";
    }
}

class MeetingResults
{
    public $originalListCount = 0;
    public $filteredList = [];
}

class CallRecord
{
    public $callSid;
    public $start_time;
    public $end_time;
    public $from;
    public $to;
    public $duration;
    public $payload;
}

class ServiceBodyCallHandling
{
    public $service_body_id;
    public $service_body_name;
    public $service_body_parent_id;
    public $service_body_parent_name;
    public $volunteer_routing_enabled = false;
    public $volunteer_routing_redirect = false;
    public $volunteer_routing_redirect_id = 0;
    public $forced_caller_id_enabled = false;
    public $forced_caller_id_number = SpecialPhoneNumber::UNKNOWN;
    public $call_timeout = 20;
    public $volunteer_sms_notification_enabled = false;
    public $gender_routing_enabled = false;
    public $call_strategy = CycleAlgorithm::LINEAR_LOOP_FOREVER;
    public $primary_contact_number_enabled = false;
    public $primary_contact_number = SpecialPhoneNumber::UNKNOWN;
    public $primary_contact_email_enabled = false;
    public $primary_contact_email;
    public $moh = "https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical";
    public $moh_count = 1;
    public $sms_routing_enabled = false;
    public $sms_strategy = CycleAlgorithm::RANDOM_LOOP_FOREVER;
}

class CycleAlgorithm
{
    const LINEAR_LOOP_FOREVER = 0;
    const LINEAR_CYCLE_AND_VOICEMAIL = 1;
    const RANDOM_LOOP_FOREVER = 2;
    const BLASTING = 3;
    const RANDOM_CYCLE_AND_VOICEMAIL = 4;
}

class AuthMechanism
{
    const V1 = "_BMLT_AUTH_";
    const V2 = "_YAP_DB_AUTH_";
}

class DataType
{
    const YAP_CONFIG = "_YAP_CONFIG_";
    const YAP_CONFIG_V2 = "_YAP_CONFIG_V2_";
    const YAP_DATA = "_YAP_DATA_";
    const YAP_CALL_HANDLING_V2 = "_YAP_CALL_HANDLING_V2_";
    const YAP_VOLUNTEERS_V2 = "_YAP_VOLUNTEERS_V2_";
    const YAP_GROUPS_V2 = "_YAP_GROUPS_V2_";
    const YAP_GROUP_VOLUNTEERS_V2 = "_YAP_GROUP_VOLUNTEERS_V2_";
    const YAP_CACHE = "_YAP_CACHE_";
}

class SpecialPhoneNumber
{
    const VOICE_MAIL = "voicemail";
    const UNKNOWN = "0000000000";
}

class SettingSource
{
    const QUERYSTRING = "Transaction Override";
    const SESSION = "Session Override";
    const CONFIG = "config.php";
    const DEFAULT_SETTING = "Factory Default";
}

class VolunteerType
{
    const PHONE = "PHONE";
    const SMS = "SMS";
}

class MeetingResultSort
{
    const TODAY = 0;
}

class VolunteerShadowOption
{
    const UNSPECIFIED = 0;
    const TRAINEE = 1;
    const TRAINER = 2;
}

class VolunteerResponderOption
{
    const UNSPECIFIED = 0;
    const ENABLED = 1;
}

class CallRole
{
    const CALLER = 1;
    const VOLUNTEER = 2;
    const TRAINER = 3;
}

class VolunteerGender
{
    const UNSPECIFIED = 0;
    const MALE = 1;
    const FEMALE = 2;

    public static function getGenderById($genderId)
    {
        switch ($genderId) {
            case VolunteerGender::MALE:
                return "MALE";
            case VolunteerGender::FEMALE:
                return "FEMALE";
            default:
                return "";
        }
    }
}

class VolunteerRoutingParameters
{
    public $service_body_id;
    public $tracker;
    public $cycle_algorithm = CycleAlgorithm::LINEAR_LOOP_FOREVER;
    public $volunteer_type = VolunteerType::PHONE;
    public $volunteer_gender = VolunteerGender::UNSPECIFIED;
    public $volunteer_shadow = VolunteerShadowOption::UNSPECIFIED;
    public $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    public $volunteer_language;
}

class NoVolunteersException extends Exception
{
}
class CurlException extends Exception
{
}

class VolunteerRoutingHelpers
{
    public static function checkVolunteerRoutingTime(DateTime $current_time, $volunteers, $v)
    {
        return ($current_time >= (new DateTime($volunteers[$v]->start))
            && $current_time <= (new DateTime($volunteers[$v]->end)));
    }

    public static function checkVolunteerRoutingLanguage($volunteer_routing_params, $volunteers, $v)
    {
        return in_array($volunteer_routing_params->volunteer_language, $volunteers[$v]->language);
    }

    public static function checkVolunteerRoutingType($volunteer_routing_params, $volunteers, $v)
    {
        return (!isset($volunteers[$v]->type) || str_exists($volunteers[$v]->type, $volunteer_routing_params->volunteer_type));
    }

    public static function checkVolunteerRoutingResponder($volunteer_routing_params, $volunteers, $v)
    {
        return ($volunteer_routing_params->volunteer_responder == VolunteerResponderOption::UNSPECIFIED
            || (($volunteer_routing_params->volunteer_responder !== VolunteerResponderOption::UNSPECIFIED
                && isset($volunteers[$v]->responder)
                && $volunteer_routing_params->volunteer_responder == $volunteers[$v]->responder)));
    }

    public static function checkVolunteerRoutingShadow($volunteer_routing_params, $volunteers, $v)
    {
        return ($volunteer_routing_params->volunteer_shadow == VolunteerShadowOption::UNSPECIFIED
            || (($volunteer_routing_params->volunteer_shadow !== VolunteerShadowOption::UNSPECIFIED
                && isset($volunteers[$v]->shadow)
                && $volunteer_routing_params->volunteer_shadow == $volunteers[$v]->shadow)));
    }

    public static function checkVolunteerRoutingGender($volunteer_routing_params, $volunteers, $v)
    {
        return ($volunteer_routing_params->volunteer_gender == VolunteerGender::UNSPECIFIED
            || (($volunteer_routing_params->volunteer_gender !== VolunteerGender::UNSPECIFIED
                && isset($volunteers[$v]->gender)
                && $volunteer_routing_params->volunteer_gender == $volunteers[$v]->gender)));
    }
}

class UpgradeAdvisor
{
    private static $all_good = true;
    private static $email_settings = [
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_secure',
        'smtp_from_address',
        'smtp_from_name'
    ];

    private static function isThere($setting)
    {
        return isset($GLOBALS[$setting]) && strlen($GLOBALS[$setting]) > 0;
    }

    public static function getState($status = null, $message = null)
    {
        try {
            $build = file_get_contents("../build.txt", false);
        } catch (Exception $e) {
            $build = $e->getMessage();
        }
        return ["status"=>$status, "message"=>$message, "version"=>$GLOBALS['version'], "build"=>str_replace("\n", "", $build)];
    }

    public static function getStatus()
    {
        foreach ($GLOBALS['required_config_settings'] as $setting) {
            if (!self::isThere($setting)) {
                return self::getState(false, "Missing required setting: " . $setting);
            }
        }

        $root_server_settings = json_decode(get(sprintf('%s/client_interface/json/?switcher=GetServerInfo', getAdminBMLTRootServer()), false, 3600));

        if (strpos(getAdminBMLTRootServer(), 'index.php')) {
            return self::getState(false, "Your root server points to index.php. Please make sure to set it to just the root directory.");
        }

        if (!isset($root_server_settings)) {
            return self::getState(false, "Your root server returned no server information.  Double-check that you have the right root server url.");
        } else {
            if ($root_server_settings[0]->semanticAdmin === "0") {
                return self::getState(false, "Your root server has semanticAdmin disabled, please enable it.  https://bmlt.app/semantic/semantic-administration/");
            }
        }

        foreach (setting("digit_map_search_type") as $digit => $value) {
            if ($digit === 0) {
                return self::getState(false, "You cannot use 0 as an option for `digit_map_search_type`.");
            }
        }

        try {
            $googleapi_settings = json_decode(get(sprintf("%s&address=91409", $GLOBALS['google_maps_endpoint']), false, 3600));

            if ($googleapi_settings->status == "REQUEST_DENIED") {
                return self::getState(false, "Your Google Maps API key came back with the following error. " . $googleapi_settings->error_message. " Please make sure you have the 'Google Maps Geocoding API' enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/");
            }

            $timezone_settings = json_decode(get(sprintf("%s&location=34.2011137,-118.475058&timestamp=%d", $GLOBALS['timezone_lookup_endpoint'], time() - (time() % 1800)), false));

            if ($timezone_settings->status == "REQUEST_DENIED") {
                return self::getState(false, "Your Google Maps API key came back with the following error. " . $timezone_settings->errorMessage. " Please make sure you have the 'Google Time Zone API' enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/");
            }
        } catch (CurlException $e) {
            return self::getState(false, "HTTP Error connecting to Google Maps API, check your network settings.");
        }

        $alerts = getMisconfiguredPhoneNumbersAlerts(AlertId::STATUS_CALLBACK_MISSING);
        if (count($alerts) > 0) {
            $misconfiguredPhoneNumbers = [];
            foreach ($alerts as $alert) {
                array_push($misconfiguredPhoneNumbers, $alert['payload']);
            }

            return self::getState(false, sprintf("%s is/are phone numbers that are missing Twilio Call Status Changes Callback status.php webhook. This will not allow call reporting to work correctly.  For more information review the documentation page https://github.com/bmlt-enabled/yap/wiki/Call-Detail-Records.", implode(",", $misconfiguredPhoneNumbers)));
        }

        try {
            require_once 'twilio-client.php';
            foreach ($GLOBALS['twilioClient']->incomingPhoneNumbers->read() as $number) {
                if (basename($number->voiceUrl)) {
                    if (!strpos($number->voiceUrl, '.php')
                        && !strpos($number->voiceUrl, 'twiml')
                        && !strpos($number->voiceUrl, '/?')
                        && substr($number->voiceUrl, -1) !== "/") {
                        return self::getState(false, $number->phoneNumber . " webhook should end either with `/` or `/index.php`");
                    }
                }
            }
        } catch (\Twilio\Exceptions\RestException $e) {
            return self::getState(false, "Twilio Rest Error: " . $e->getMessage());
        }

        if (has_setting('smtp_host')) {
            foreach (self::$email_settings as $setting) {
                if (!self::isThere($setting)) {
                    return self::getState(false, "Missing required email setting: " . $setting);
                }
            }
        }

        if (isset($GLOBALS['mysql_hostname'])) {
            try {
                $db = new Database();
                $db->close();
            } catch (PDOException $e) {
                return self::getState(false, $e->getMessage());
            }
        }

        if (UpgradeAdvisor::$all_good) {
            return UpgradeAdvisor::getState(true, "Ready To Yap!");
        }
    }
}

class ServiceBodyFinder
{
    private $service_bodies;

    public function __construct()
    {
        $this->service_bodies = getServiceBodies();
    }

    public function getServiceBody($service_body_id)
    {
        foreach ($this->service_bodies as $service_body) {
            if ($service_body->id == $service_body_id) {
                return $service_body;
            }
        }
    }
}

class DbConfigFinder
{
    private $config;

    public function __construct()
    {
        $this->configs = getAllDbData(DataType::YAP_CONFIG_V2);
    }

    public function getConfig($service_body_id)
    {
        foreach ($this->configs as $config) {
            if ($config['service_body_id'] == $service_body_id) {
                return $config;
            }
        }

        return null;
    }
}

function checkBlocklist()
{
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

function getWordLanguage()
{
    foreach ($GLOBALS['available_languages'] as $key => $available_language) {
        if ($key == setting('word_language')) {
            return $key;
        }
    }

    return "";
}

function word($name)
{
    return isset($GLOBALS['override_' . $name]) ? $GLOBALS['override_' . $name] : $GLOBALS[$name];
}

function getNumberForWord($name)
{
    $numbers = $GLOBALS['numbers'];
    for ($n = 0; $n < count($numbers); $n++) {
        if ($name == $numbers[$n]) {
            return $n;
        }
    }
}

function getWordForNumber($number)
{
    return word($GLOBALS['numbers'][$number]);
}

function has_setting($name)
{
    return !is_null(setting($name));
}
function setting($name)
{
    if (isset($GLOBALS['settings_allowlist'][$name]) && $GLOBALS['settings_allowlist'][$name]['overridable']) {
        if (isset($_REQUEST[$name]) && $GLOBALS['settings_allowlist'][$name]['hidden'] !== true) {
            return $_REQUEST[$name];
        } else if (isset($_SESSION["override_" . $name])) {
            return $_SESSION["override_" . $name];
        }
    }

    if (isset($GLOBALS[$name])) {
        return $GLOBALS[$name];
    } else if (isset($GLOBALS['settings_allowlist'][$name]['default'])) {
        return $GLOBALS['settings_allowlist'][$name]['default'];
    }

    return null;
}

function voice($current_language = null)
{
    if (!isset($current_language)) {
        $current_language = str_replace("-", "_", setting('language'));
    }

    if (has_setting($current_language . "_voice")) {
        return setting($current_language . "_voice");
    } else {
        return setting('voice');
    }
}

function setting_source($name)
{
    if (isset($_REQUEST[$name])) {
        return SettingSource::QUERYSTRING;
    } else if (isset($_SESSION["override_" . $name])) {
        return SettingSource::SESSION;
    } else if (isset($GLOBALS[$name])) {
        return SettingSource::CONFIG;
    } else if (isset($GLOBALS['settings_allowlist'][$name]['default'])) {
        return SettingSource::DEFAULT_SETTING;
    } else {
        return "NOT SET";
    }
}

function getDigitMap($setting)
{
    $digitMapSetting = setting($setting);

    if ($setting == 'language_selections') {
        $language_selection_digit_map = [];
        for ($i = 0; $i <= count(explode(",", setting('language_selections'))); $i++) {
            array_push($language_selection_digit_map, $i);
        }

        return $language_selection_digit_map;
    }

    if (json_decode(setting('jft_option')) == false) {
        if (($key = array_search(SearchType::JFT, $digitMapSetting)) !== false) {
            unset($digitMapSetting[$key]);
        }
    }

    if (json_decode(setting('disable_postal_code_gather'))) {
        if (($key = array_search(LocationSearchMethod::DTMF, $digitMapSetting)) !== false) {
            unset($digitMapSetting[$key]);
        }
    }

    return $digitMapSetting;
}

function getPossibleDigits($setting)
{
    return array_keys(getDigitMap($setting));
}

function getDigitResponse($setting, $field = 'SearchType')
{
    $digitMap = getDigitMap($setting);
    if ($field === 'Digits'
        && has_setting('speech_gathering')
        && json_encode(setting('speech_gathering'))
        && isset($_REQUEST['SpeechResult'])) {
        $digit = intval($_REQUEST['SpeechResult']);
    } else if (isset($_REQUEST[$field])) {
        $digit = intval($_REQUEST[$field]);
    } else {
        return null;
    }

    if (array_key_exists($digit, $digitMap)) {
        return $digitMap[$digit];
    } else {
        return null;
    }
}

function getDigitMapSequence($setting)
{
    $digitMap = getDigitMap($setting);
    ksort($digitMap);
    return $digitMap;
}

function getDigitForAction($setting, $action)
{
    $searchTypeSequence = getDigitMapSequence($setting);
    foreach ($searchTypeSequence as $digit => $type) {
        if ($type == $action) {
            return $digit;
        }
    }
}

function getOutboundDialingCallerId($serviceBodyCallHandling)
{
    if ($serviceBodyCallHandling->forced_caller_id_enabled) {
        return $serviceBodyCallHandling->forced_caller_id_number;
    } else if (isset($_REQUEST["Caller"])) {
        return $_REQUEST["Caller"];
    } else if (isset($_REQUEST['caller_id'])) {
        return $_REQUEST['caller_id'];
    } else {
        return SpecialPhoneNumber::UNKNOWN;
    }
}

function getConferenceName($service_body_id)
{
    return $service_body_id . "_" . rand(1000000, 9999999) . "_" . time();
}

function getCoordinatesForAddress($address)
{
    $coordinates = new Coordinates();

    if (strlen($address) > 0) {
        $map_details_response = get($GLOBALS['google_maps_endpoint']
            . "&address="
            . urlencode($address)
            . "&components=" . urlencode(setting('location_lookup_bias')), false, 3600);
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

function getTimeZoneForCoordinates($latitude, $longitude)
{
    $time_zone = get(sprintf("%s&location=%s,%s&timestamp=%d", $GLOBALS['timezone_lookup_endpoint'], $latitude, $longitude, time() - (time() % 1800)), false, 3600);
    return json_decode($time_zone);
}

function getProvince()
{
    if (has_setting('sms_bias_bypass') && json_decode(setting('sms_bias_bypass'))) {
        return "";
    } elseif (has_setting('toll_province_bias')) {
        return setting('toll_province_bias');
    } elseif (isset($_REQUEST['ToState']) && strlen($_REQUEST['ToState']) > 0) {
        return $_REQUEST['ToState']; // Retrieved from Twilio metadata
    } elseif (has_setting('toll_free_province_bias')) {
        return setting('toll_free_province_bias'); // Override for Tollfree
    } else {
        return "";
    }
}

function helplineSearch($latitude, $longitude)
{
    $search_url = sprintf(
        "%s/client_interface/json/?switcher=GetSearchResults&data_field_key=longitude,latitude,service_body_bigint&sort_results_by_distance=1&lat_val=%s&long_val=%s&geo_width=%s%s",
        getHelplineRoutingBMLTServer($latitude, $longitude),
        $latitude,
        $longitude,
        setting('helpline_search_radius'),
        setting('call_routing_filter')
    );

    return json_decode(get($search_url, false, 60));
}

function isBMLTServerOwned($latitude, $longitude)
{
    $bmlt_search_endpoint = sprintf(
        '%s/client_interface/json/?switcher=GetSearchResults&data_field_key=root_server_uri&sort_results_by_distance=1&lat_val=%s&long_val=%s&geo_width=%s',
        setting('tomato_url'),
        $latitude,
        $longitude,
        setting('helpline_search_radius')
    );
    $search_results = json_decode(get($bmlt_search_endpoint, false, 60));
    $root_server_uri_from_first_result = $search_results[0]->root_server_uri;
    return str_exists($root_server_uri_from_first_result, getAdminBMLTRootServer());
}

function getHelplineRoutingBMLTServer($latitude, $longitude)
{
    if (json_decode(setting('tomato_helpline_routing')) && !isBMLTServerOwned($latitude, $longitude)) {
        return setting('tomato_url');
    } else {
        return getAdminBMLTRootServer();
    }
}

function getAdminBMLTRootServer()
{
    if (has_setting('helpline_bmlt_root_server')) {
        return setting('helpline_bmlt_root_server');
    } else {
        return setting('bmlt_root_server');
    }
}

function getBMLTRootServer()
{
    if (json_decode(setting('tomato_meeting_search'))) {
        return setting('tomato_url');
    } else {
        return setting('bmlt_root_server');
    }
}

function getFormatString($formats, $ignore = false)
{
    $formatsArray = getIdsFormats($formats);
    $finalString = "";
    for ($i = 0; $i < count($formatsArray); $i++) {
        $finalString .= "&formats[]=" . ($ignore ? "-" : "") . $formatsArray[$i];
    }

    return $finalString;
}

function meetingSearch($meeting_results, $latitude, $longitude, $day)
{
    $bmlt_base_url = sprintf('%s/client_interface/json/?switcher=GetSearchResults&get_used_formats&data_field_key=id_bigint,meeting_name,weekday_tinyint,start_time,location_text,location_info,location_municipality,location_province,location_street,longitude,latitude,distance_in_miles,distance_in_km,formats,virtual_meeting_link,phone_meeting_number,virtual_meeting_additional_info', getBMLTRootServer());
    $bmlt_search_endpoint = setting('custom_query');
    if (has_setting('ignore_formats')) {
        $bmlt_search_endpoint .= getFormatString(setting('ignore_formats'), true);
    }

    if (json_decode(setting('include_unpublished'))) {
        $bmlt_search_endpoint .= "&advanced_published=0";
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
    if (is_array($search_results->meetings) || $search_results->meetings instanceof Countable) {
        $meeting_results->originalListCount += count($search_results->meetings);
    } else {
        return $meeting_results;
    }

    $filteredList = $meeting_results->filteredList;
    if ($search_response !== "{}") {
        for ($i = 0; $i < count($search_results->meetings); $i++) {
            // Hide meetings if they are TC and are not VM formats.
            if (!in_array("VM", explode(",", $search_results->meetings[$i]->formats))
                && in_array("TC", explode(",", $search_results->meetings[$i]->formats))) {
                continue;
            }

            if (strpos($bmlt_search_endpoint, "{DAY}")) {
                if (!isItPastTime($search_results->meetings[$i]->weekday_tinyint, $search_results->meetings[$i]->start_time)) {
                    array_push($filteredList, $search_results->meetings[$i]);
                }
            } else {
                array_push($filteredList, $search_results->meetings[$i]);
            }

            $formats = explode(",", $search_results->meetings[$i]->formats);
            $search_results->meetings[$i]->format_details = [];
            foreach ($formats as $format) {
                foreach ($search_results->formats as $search_result_format) {
                    if ($format === $search_result_format->key_string) {
                        array_push($search_results->meetings[$i]->format_details, $search_result_format);
                    }
                }
            }
        }
    } else {
        $meeting_results->originalListCount += 0;
    }

    $meeting_results->filteredList = $filteredList;
    return $meeting_results;
}

function getResultsString($filtered_list)
{
    $results_string = array(
        "meeting_name" => str_replace("&", "&amp;", $filtered_list->meeting_name),
        "timestamp" => str_replace("&", "&amp;", $GLOBALS['days_of_the_week'][$filtered_list->weekday_tinyint]
                                        . ' ' . (new DateTime($filtered_list->start_time))->format(setting('time_format'))),
        "location" => array(),
        "distance_details" => "",
        "location_links" => array(),
        "links" => array(),
        "format_details" => array(),
        "virtual_meeting_additional_info" => array()
    );

    if (!in_array("TC", explode(",", $filtered_list->formats))) {
        if (has_setting('include_location_text') && json_decode(setting('include_location_text'))) {
            array_push($results_string["location"], str_replace("&", "&amp;", $filtered_list->location_text));
        }

        array_push($results_string["location"], str_replace("&", "&amp;", $filtered_list->location_street
            . ($filtered_list->location_municipality !== "" ? ", " . $filtered_list->location_municipality : "")
            . ($filtered_list->location_province !== "" ? ", " . $filtered_list->location_province : "")));

        if (has_setting('include_distance_details')) {
            if (setting('include_distance_details') == "mi") {
                $results_string["distance_details"] = sprintf("(%s mi)", round($filtered_list->distance_in_miles));
            } else if (setting('include_distance_details') == "km") {
                $results_string["distance_details"] = sprintf("(%s km)", round($filtered_list->distance_in_km));
            }
        }

        if (has_setting('include_map_link') && json_decode(setting('include_map_link'))) {
            array_push($results_string["location_links"], sprintf("https://maps.google.com/maps?q=%s,%s&hl=%s", $filtered_list->latitude, $filtered_list->longitude, $GLOBALS['short_language']));
        }
    }

    if (in_array("VM", explode(",", $filtered_list->formats)) || in_array("HY", explode(",", $filtered_list->formats))) {
        if (isset($filtered_list->virtual_meeting_link) && strlen($filtered_list->virtual_meeting_link) > 0) {
            array_push($results_string["links"], str_replace("&", "&amp;", $filtered_list->virtual_meeting_link));
        }

        if (isset($filtered_list->phone_meeting_number) && strlen($filtered_list->phone_meeting_number) > 0) {
            array_push($results_string["links"], sprintf("tel:%s", str_replace("&", "&amp;", $filtered_list->phone_meeting_number)));
        }

        if (isset($filtered_list->virtual_meeting_additional_info) && strlen($filtered_list->virtual_meeting_additional_info) > 0) {
            array_push($results_string["virtual_meeting_additional_info"], str_replace("&", "&amp;", $filtered_list->virtual_meeting_additional_info));
        }
    }

    if (has_setting('include_format_details') && count(setting('include_format_details')) > 0) {
        $include_format_details = setting('include_format_details');
        foreach ($include_format_details as $include_format_detail) {
            foreach ($filtered_list->format_details as $format_detail) {
                if ($format_detail->key_string === $include_format_detail) {
                    array_push($results_string["format_details"], $format_detail);
                }
            }
        }
    }

    return $results_string;
}

function getServiceBodyCoverage($latitude, $longitude)
{
    $search_results = helplineSearch($latitude, $longitude);
    $service_bodies = getServiceBodiesForRouting($latitude, $longitude);
    $already_checked = [];

    // Must do this because the BMLT returns an empty object instead of an empty array.
    if (!is_array($search_results)) {
        throw new Exception(word('helpline_no_results_found_retry'));
    }

    for ($j = 0; $j < count($search_results); $j++) {
        $service_body_id = $search_results[$j]->service_body_bigint;
        if (in_array($service_body_id, $already_checked)) {
            continue;
        }
        for ($i = 0; $i < count($service_bodies); $i++) {
            if ($service_bodies[$i]->id == $service_body_id) {
                if (strlen($service_bodies[$i]->helpline) > 0 || getServiceBodyCallHandling($service_bodies[$i]->id)->volunteer_routing_enabled) {
                    return $service_bodies[$i];
                } else {
                    array_push($already_checked, $service_bodies[$i]->id);
                }
            }
        }
    }
}

function getTimezoneList()
{
    return DateTimeZone::listIdentifiers(DateTimeZone::ALL);
}

function setTimeZoneForLatitudeAndLongitude($latitude, $longitude)
{
    $time_zone_results = getTimeZoneForCoordinates($latitude, $longitude);
    date_default_timezone_set($time_zone_results->timeZoneId);
}

function getMeetings($latitude, $longitude, $results_count, $today = null, $tomorrow = null)
{
    if ($latitude != null & $longitude != null) {
        setTimeZoneForLatitudeAndLongitude($latitude, $longitude);
        $graced_date_time = (new DateTime())->modify(sprintf("-%s minutes", setting('grace_minutes')));
        if ($today == null) {
            $today = $graced_date_time->format("w") + 1;
        }
        if ($tomorrow == null) {
            $tomorrow = $graced_date_time->modify("+24 hours")->format("w") + 1;
        }
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
                $meeting_results->filteredList[0]->longitude
            );

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

function isItPastTime($meeting_day, $meeting_time)
{
    $next_meeting_time = getNextMeetingInstance($meeting_day, $meeting_time);
    $time_zone_time = new DateTime();
    return $next_meeting_time <= $time_zone_time;
}

function getNextMeetingInstance($meeting_day, $meeting_time)
{
    $mod_meeting_day = (new DateTime())
        ->modify(sprintf("-%s minutes", setting('grace_minutes')))
        ->modify($GLOBALS['date_calculations_map'][$meeting_day])->format("Y-m-d");
    $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $meeting_time))
        ->modify(sprintf("+%s minutes", setting('grace_minutes')));
    return $mod_meeting_datetime;
}

function getServiceBodiesForRouting($latitude, $longitude)
{
    $bmlt_search_endpoint = sprintf('%s/client_interface/json/?switcher=GetServiceBodies', getHelplineRoutingBMLTServer($latitude, $longitude));
    return json_decode(get($bmlt_search_endpoint, false, 3600));
}

function getServiceBodies()
{
    $bmlt_search_endpoint = sprintf('%s/client_interface/json/?switcher=GetServiceBodies', getAdminBMLTRootServer());
    return json_decode(get($bmlt_search_endpoint, false, 3600));
}

function getServiceBody($service_body_id)
{
    $service_bodies = getServiceBodies();
    foreach ($service_bodies as $service_body) {
        if ($service_body->id == $service_body_id) {
            return $service_body;
        }
    }

    return null;
}

function getServiceBodiesForUser($include_general = false)
{
    $service_bodies = getServiceBodiesRights();

    if (isset($service_bodies)) {
        foreach ($service_bodies as $service_body) {
            $parent_service_body = getServiceBody($service_body->parent_id);
            $service_body->parent_name = isset($parent_service_body) ? $parent_service_body->name : "None";
        }

        if ($include_general) {
            array_push($service_bodies, (object)[
                "id" => "0"
            ]);
        }
    } else {
        $service_bodies = [];
    }

    return $service_bodies;
}

function getServiceBodiesForUserRecursively($service_body_id, $service_body_rights = null)
{
    $service_bodies_results = [];

    if ($service_body_rights == null) {
        array_push($service_bodies_results, intval($service_body_id));
        $service_body_rights = getServiceBodiesForUser();
    }

    foreach ($service_body_rights as $service_body) {
        if ($service_body->parent_id == $service_body_id) {
            array_push($service_bodies_results, intval($service_body->id));
            getServiceBodiesForUserRecursively(intval($service_body->id), $service_body_rights);
        }
    }

    return $service_bodies_results;
}

function canManageUsers()
{
    return (isset($_SESSION['auth_is_admin']) && boolval($_SESSION['auth_is_admin'])) ||
        (isset($_SESSION['auth_permissions']) && (intval($_SESSION['auth_permissions']) & AdminInterfaceRights::MANAGE_USERS));
}

function getServiceBodiesRights()
{
    if (isset($_SESSION['auth_mechanism'])) {
        if ($_SESSION['auth_mechanism'] == AuthMechanism::V1) {
            $url = sprintf('%s/local_server/server_admin/json.php?admin_action=get_permissions', getAdminBMLTRootServer());
            $service_bodies_for_user = json_decode(get($url, true));

            if (!is_array($service_bodies_for_user->service_body)) {
                $service_bodies_for_user = array($service_bodies_for_user->service_body);
            } else if (isset($service_bodies_for_user->service_body)) {
                $service_bodies_for_user = $service_bodies_for_user->service_body;
            } else {
                $service_bodies_for_user = array();
            }

            $service_bodies = getServiceBodies();
            $enriched_service_bodies_for_user = array();
            foreach ($service_bodies_for_user as $service_body_for_user) {
                foreach ($service_bodies as $service_body) {
                    if (intval($service_body->id) === $service_body_for_user->id) {
                        array_push($enriched_service_bodies_for_user, $service_body);
                    }
                }
            }

            return $enriched_service_bodies_for_user;
        } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2 && $_SESSION['auth_is_admin']) {
            return getServiceBodies();
        } elseif ($_SESSION['auth_mechanism'] == AuthMechanism::V2) {
            $service_bodies = getServiceBodies();
            $service_body_rights = $_SESSION['auth_service_bodies'];
            $service_bodies_for_user = array();
            foreach ($service_bodies as $service_body) {
                if (in_array($service_body->id, $service_body_rights)) {
                    array_push($service_bodies_for_user, $service_body);
                }
            }

            return $service_bodies_for_user;
        }
    }

    return null;
}

function incrementNoAnswerCount()
{
    $_SESSION['no_answer_count'] = !isset($_SESSION['no_answer_count']) ? 1 : $_SESSION['no_answer_count'] + 1;
    if ($_SESSION['no_answer_count'] == $_SESSION['no_answer_max']) {
        log_debug("Call blasting no answer, calling voicemail.");
        $GLOBALS['twilioClient']->calls($_SESSION['master_callersid'])->update(array(
            "method" => "GET",
            "url" => $_SESSION['voicemail_url']
        ));
    }
}

function admin_GetUserName()
{
    if (!isset($_SESSION['auth_user_name_string'])) {
        $url = sprintf('%s/local_server/server_admin/json.php?admin_action=get_user_info', getAdminBMLTRootServer());
        $get_user_info_response = json_decode(get($url, true), 3600, CacheType::SESSION);
        $user_name = isset($get_user_info_response->current_user) ? $get_user_info_response->current_user->name : $_SESSION['username'];
        $_SESSION['auth_user_name_string'] = $user_name;
    }
    return $_SESSION['auth_user_name_string'];
}

function getGroupsForServiceBody($service_body_id)
{
    $all_groups = getAllDbData(DataType::YAP_GROUPS_V2);
    $final_groups = array();
    foreach ($all_groups as $all_group) {
        if ($all_group['service_body_id'] === $service_body_id
            || (isset(json_decode($all_group['data'])[0]->group_shared_service_bodies) && in_array($service_body_id, json_decode($all_group['data'])[0]->group_shared_service_bodies))) {
            array_push($final_groups, $all_group);
        }
    }

    return $final_groups;
}

function getServiceBodyConfig($service_body_id)
{
    $service_body_finder = new ServiceBodyFinder();
    $db_config_finder = new DbConfigFinder();
    $lookup_id = $service_body_id;
    $config = new StdClass();

    while (true) {
        $config_from_db = $db_config_finder->getConfig($lookup_id);
        if (isset($config_from_db)) {
            $config_obj = json_decode($config_from_db['data']);
            foreach ($GLOBALS['settings_allowlist'] as $setting => $value) {
                if (isset($config_obj[0]->$setting) && !isset($config->$setting)) {
                    if (gettype($value['default']) === "array") {
                        $config->$setting = (array) json_decode(str_replace("'", "\"", $config_obj[0]->$setting));
                    } else {
                        $config->$setting = $config_obj[0]->$setting;
                    }
                }
            }
        }

        $found_service_body = $service_body_finder->getServiceBody($lookup_id);
        if (!isset($found_service_body)) {
            return null;
        }
        $lookup_id = $found_service_body->parent_id;
        if ($lookup_id == 0) {
            return $config;
        }
    }
}

function getVolunteerRoutingEnabledServiceBodies()
{
    $all_helpline_data = getAllDbData(DataType::YAP_CALL_HANDLING_V2);
    $service_bodies = getServiceBodiesForUser();
    $helpline_enabled = array();

    for ($x = 0; $x < count($all_helpline_data); $x++) {
        $config = getServiceBodyCallHandlingData($all_helpline_data[$x]);
        if ($config->volunteer_routing_enabled || $config->sms_routing_enabled) {
            for ($y = 0; $y < count($service_bodies); $y++) {
                if ($config->service_body_id == intval($service_bodies[$y]->id)) {
                    $config->service_body_name = $service_bodies[$y]->name;
                    $config->service_body_parent_id = $service_bodies[$y]->parent_id;
                    $config->service_body_parent_name = $service_bodies[$y]->parent_name;
                    array_push($helpline_enabled, $config);
                }
            }
        }
    }

    return $helpline_enabled;
}

function getGroups($service_body_id)
{
    $groupsData = getDbData($service_body_id, DataType::YAP_GROUPS_V2);
    $groupsArray = [];
    foreach ($groupsData as $group) {
        $groupsDataObj = json_decode($group['data'])[0];
        array_push($groupsArray, (object)[
            'name' => $groupsDataObj->group_name,
            'id' => $group['id'],
            'shares' => json_encode($groupsDataObj->group_shared_service_bodies)
        ]);
    }

    return $groupsArray;
}

function getServiceBodyCallHandlingData($helplineData)
{
    $config = new ServiceBodyCallHandling();
    if (isset($helplineData)) {
        $data = json_decode($helplineData['data'])[0];
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

function getServiceBodyCallHandling($service_body_id)
{
    $helplineData = getDbData($service_body_id, DataType::YAP_CALL_HANDLING_V2);
    return count($helplineData) > 0 ? getServiceBodyCallHandlingData($helplineData[0]) : getServiceBodyCallHandlingData(null);
}

function getNextShiftInstance($shift_day, $shift_time, $shift_tz)
{
    date_default_timezone_set($shift_tz);
    $mod_meeting_day = (new DateTime())
        ->modify($GLOBALS['date_calculations_map'][$shift_day])->format("Y-m-d");
    $mod_meeting_datetime = (new DateTime($mod_meeting_day . " " . $shift_time));
    return $mod_meeting_datetime;
}

function getIdsFormats($types)
{
    $typesArray = explode(",", $types);
    $finalFormats = array();
    $bmlt_search_endpoint = sprintf('%s/client_interface/json/?switcher=GetFormats', getBMLTRootServer());
    $formats = json_decode(get($bmlt_search_endpoint, false, 3600));
    for ($t = 0; $t < count($typesArray); $t++) {
        for ($f = 0; $f < count($formats); $f ++) {
            if ($formats[ $f ]->key_string == $typesArray[$t]) {
                array_push($finalFormats, $formats[ $f ]->id);
            }
        }
    }

    return $finalFormats;
}

function getHelplineVolunteersActiveNow($volunteer_routing_params)
{
    try {
        $volunteers = json_decode(getHelplineSchedule($volunteer_routing_params->service_body_id));
        $activeNow  = [];
        for ($v = 0; $v < count($volunteers); $v++) {
            date_default_timezone_set($volunteers[ $v ]->time_zone);
            $current_time = new DateTime();
            if (VolunteerRoutingHelpers::checkVolunteerRoutingTime($current_time, $volunteers, $v)
                 && VolunteerRoutingHelpers::checkVolunteerRoutingType($volunteer_routing_params, $volunteers, $v)
                 && VolunteerRoutingHelpers::checkVolunteerRoutingGender($volunteer_routing_params, $volunteers, $v)
                 && VolunteerRoutingHelpers::checkVolunteerRoutingShadow($volunteer_routing_params, $volunteers, $v)
                 && VolunteerRoutingHelpers::checkVolunteerRoutingResponder($volunteer_routing_params, $volunteers, $v)
                 && VolunteerRoutingHelpers::checkVolunteerRoutingLanguage($volunteer_routing_params, $volunteers, $v)) {
                array_push($activeNow, $volunteers[ $v ]);
            }
        }

        return $activeNow;
    } catch (NoVolunteersException $nve) {
        throw $nve;
    }
}



function getHelplineVolunteer($volunteer_routing_params)
{
    try {
        $volunteers = getHelplineVolunteersActiveNow($volunteer_routing_params);
        log_debug("getHelplineVolunteer():: activeVolunteers: " . var_export($volunteers, true));
        if (isset($volunteers) && count($volunteers) > 0) {
            if ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL) {
                if ($volunteer_routing_params->tracker > count($volunteers) - 1) {
                    return new Volunteer(SpecialPhoneNumber::VOICE_MAIL);
                }

                return new Volunteer($volunteers[ $volunteer_routing_params->tracker ]->contact, $volunteers[$volunteer_routing_params->tracker]);
            } else if ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::LINEAR_LOOP_FOREVER) {
                $volunteer = $volunteers[ $volunteer_routing_params->tracker % count($volunteers)];
                return new Volunteer($volunteer->contact, $volunteer);
            } else if ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::RANDOM_LOOP_FOREVER) {
                $volunteer = $volunteers[rand(0, count($volunteers) - 1)];
                return new Volunteer($volunteer->contact, $volunteer);
            } else if ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::RANDOM_CYCLE_AND_VOICEMAIL) {
                if (!isset($_SESSION['volunteers_randomized'])) {
                    shuffle($volunteers);
                    $_SESSION['volunteers_randomized'] = $volunteers;
                }

                $volunteers = $_SESSION['volunteers_randomized'];

                if ($volunteer_routing_params->tracker > count($volunteers) - 1) {
                    return new Volunteer(SpecialPhoneNumber::VOICE_MAIL);
                }

                return new Volunteer($volunteers[ $volunteer_routing_params->tracker ]->contact, $volunteers[$volunteer_routing_params->tracker]);
            } else if ($volunteer_routing_params->cycle_algorithm == CycleAlgorithm::BLASTING) {
                $volunteers_numbers = [];

                foreach ($volunteers as $volunteer) {
                    array_push($volunteers_numbers, $volunteer->contact);
                }

                return new Volunteer(join(",", $volunteers_numbers));
            }
        } else {
            return new Volunteer(SpecialPhoneNumber::UNKNOWN);
        }
    } catch (NoVolunteersException $nve) {
        return new Volunteer(SpecialPhoneNumber::UNKNOWN);
    }
}

function getVolunteerInfo($volunteers)
{
    $finalSchedule = [];

    for ($v = 0; $v < count($volunteers); $v++) {
        $volunteer = $volunteers[$v];
        if (isset($volunteer->volunteer_enabled) && $volunteer->volunteer_enabled &&
            isset($volunteer->volunteer_phone_number) && strlen($volunteer->volunteer_phone_number) > 0) {
            $volunteerShiftSchedule = dataDecoder($volunteer->volunteer_shift_schedule);
            foreach ($volunteerShiftSchedule as $vsi) {
                $volunteerInfo             = new VolunteerInfo();
                $volunteerInfo->type       = isset($vsi->type) ? $vsi->type : $volunteerInfo->type;
                $volunteerInfo->title      = $volunteer->volunteer_name . " (" . $volunteerInfo->type . ")"
                    . (isset($volunteer->volunteer_gender) ? " " . VolunteerGender::getGenderById($volunteer->volunteer_gender) : "")
                    . (isset($volunteer->volunteer_language) ? " " . json_encode($volunteer->volunteer_language) : "");
                $volunteerInfo->time_zone  = $vsi->tz;
                $volunteerInfo->start      = str_replace(" ", "T", getNextShiftInstance($vsi->day, $vsi->start_time, $volunteerInfo->time_zone)->format("Y-m-d H:i:s"));
                $volunteerInfo->end        = str_replace(" ", "T", getNextShiftInstance($vsi->day, $vsi->end_time, $volunteerInfo->time_zone)->format("Y-m-d H:i:s"));
                $volunteerInfo->weekday_id = $vsi->day;
                $volunteerInfo->weekday    = $GLOBALS['days_of_the_week'][ $vsi->day ];
                $volunteerInfo->sequence   = $v;
                $volunteerInfo->contact    = $volunteer->volunteer_phone_number;
                $volunteerInfo->color      = "#" . getNameHashColorCode(strval($v+1) . "-" . $volunteerInfo->title);
                $volunteerInfo->gender     = isset($volunteer->volunteer_gender) ? $volunteer->volunteer_gender : VolunteerGender::UNSPECIFIED;
                $volunteerInfo->shadow     = isset($volunteer->volunteer_shadow) ? $volunteer->volunteer_shadow : VolunteerShadowOption::UNSPECIFIED;
                $volunteerInfo->responder  = isset($volunteer->volunteer_responder) ? $volunteer->volunteer_responder : VolunteerResponderOption::UNSPECIFIED;
                $volunteerInfo->language   = isset($volunteer->volunteer_language) && strlen(setting('language_selections')) > 0 ? $volunteer->volunteer_language : array(setting("language"));
                array_push($finalSchedule, $volunteerInfo);
            }
        }
    }

    return $finalSchedule;
}

function getGroupVolunteers($group_id)
{
    $groupData = getDbDataByParentId($group_id, DataType::YAP_GROUP_VOLUNTEERS_V2);
    return isset($groupData[0]['data']) ? json_decode($groupData[0]['data']) : array();
}

function getVolunteers($service_body_id)
{
    $volunteerData = getDbData($service_body_id, DataType::YAP_VOLUNTEERS_V2);
    $volunteerList = [];
    if (count($volunteerData) > 0) {
        $volunteers = json_decode($volunteerData[0]['data']);
        for ($v = 0; $v < count($volunteers); $v++) {
            if (isset($volunteers[$v]->group_id) && isset($volunteers[$v]->group_enabled) && json_decode($volunteers[$v]->group_enabled)) {
                $groupVolunteers = getGroupVolunteers($volunteers[$v]->group_id);
                foreach ($groupVolunteers as $groupVolunteer) {
                    array_push($volunteerList, $groupVolunteer);
                }
            } else {
                array_push($volunteerList, $volunteers[$v]);
            }
        }
    }

    return $volunteerList;
}

function getHelplineSchedule($service_body_int)
{
    $volunteers = getVolunteers($service_body_int);
    if (count($volunteers) > 0) {
        $finalSchedule = getVolunteerInfo($volunteers);

        usort($finalSchedule, function ($a, $b) {
            return $a->sequence > $b->sequence;
        });

        return json_encode($finalSchedule);
    } else {
        throw new NoVolunteersException();
    }
}

function filterOut($volunteers)
{
    $volunteers_array = json_decode($volunteers);
    for ($v = 0; $v < count($volunteers_array); $v++) {
        unset($volunteers_array[$v]->contact);
    }

    return json_encode($volunteers_array);
}

function getNameHashColorCode($str)
{
    $code = dechex(crc32($str));
    $code = substr($code, 0, 6);
    return $code;
}

function dataEncoder($dataObject)
{
    return base64_encode(json_encode($dataObject));
}

function dataDecoder($dataString)
{
    return json_decode(base64_decode($dataString));
}

function str_exists($subject, $needle)
{
    return strpos($subject, $needle) !== false;
}

function get_str_val($subject)
{
    if (is_bool($subject)) {
        if ($subject) {
            return "true";
        } else {
            return "false";
        }
    } else if (is_array($subject)) {
        return strval(json_encode($subject));
    }

    return strval($subject);
}

function sort_on_field(&$objects, $on, $order = 'ASC')
{
    usort($objects, function ($a, $b) use ($on, $order) {
        return $order === 'DESC' ? -strcoll($a->{$on}, $b->{$on}) : strcoll($a->{$on}, $b->{$on});
    });
}

function getResponse($ch, $exec)
{
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    return ['header' => substr($exec, 0, $header_size),
        'body' => substr($exec, $header_size)];
}

$curlResponseHeaders = [];
function getHeaders($curl, $header)
{
    $len = strlen($header);
    $header = explode(':', $header, 2);
    if (count($header) < 2) {
        return $len;
    }

    $GLOBALS['curlResponseHeaders'][strtolower(trim($header[0]))][] = trim($header[1]);

    return $len;
}

function getUserAgent()
{
    return 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0) +yap';
}

function getBMLTAuthSessionCookies()
{
    return isset($_SESSION['bmlt_auth_session']) ? implode(";", $_SESSION['bmlt_auth_session']) : "";
}

function getCookiesFromHeaders()
{
    $cookies = [];

    foreach ($GLOBALS['curlResponseHeaders']['set-cookie'] as $cookie) {
        array_push($cookies, explode(";", $cookie)[0]);
    }

    return $cookies;
}

function auth_v1($username, $password)
{
    session_destroy();
    session_start();
    $ch = curl_init();
    $auth_endpoint = (isset($GLOBALS['alt_auth_method']) && $GLOBALS['alt_auth_method'] ? '/index.php' : '/local_server/server_admin/xml.php');
    curl_setopt($ch, CURLOPT_URL, getAdminBMLTRootServer() . $auth_endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'admin_action=login&c_comdef_admin_login='.$username.'&c_comdef_admin_password='.urlencode($password));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, "getHeaders");
    $exec = curl_exec($ch);
    $res = getResponse($ch, $exec);
    curl_close($ch);
    $is_authed = preg_match('/^OK$/', str_replace(array("\r", "\n"), '', $res['body'])) == 1;
    $_SESSION["bmlt_auth_session"] = $is_authed ? getCookiesFromHeaders() : null;
    return $is_authed;
}

function check_auth()
{
    if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] == AuthMechanism::V1) {
        if (isset($_SESSION['bmlt_auth_session']) && $_SESSION['bmlt_auth_session'] != null) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, sprintf('%s/local_server/server_admin/xml.php?admin_action=get_permissions', getAdminBMLTRootServer()));
            curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
            curl_setopt($ch, CURLOPT_COOKIE, getBMLTAuthSessionCookies());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $res = curl_exec($ch);
            curl_close($ch);
        } else {
            $res = "NOT AUTHORIZED";
        }

        return !preg_match('/NOT AUTHORIZED/', $res);
    } else {
        return true;
    }
}

function logout_auth()
{
    if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] == AuthMechanism::V1) {
        if (isset($_SESSION['bmlt_auth_session']) && $_SESSION['bmlt_auth_session'] != null) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, sprintf('%s/local_server/server_admin/xml.php?admin_action=logout', getAdminBMLTRootServer()));
            curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
            curl_setopt($ch, CURLOPT_COOKIE, getBMLTAuthSessionCookies());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $res = curl_exec($ch);
            curl_close($ch);
        } else {
            $res = "BYE;";
        }

        session_unset();
        return !preg_match('/BYE/', $res);
    } else {
        session_unset();
        return true;
    }
}

function getCache($key, $cache_type = CacheType::DATABASE)
{
    $cache = null;
    $expiry = null;
    $cache_key = sprintf('cache_%s', $key);
    if ($cache_type == CacheType::SESSION) {
        if (isset($_SESSION[$cache_key])) {
            $value = $_SESSION[$cache_key];
        }
    } else if ($cache_type == CacheType::DATABASE) {
        $database_cache_value = getDatabaseCacheValue($cache_key);
        if (count($database_cache_value) > 0) {
            $value = json_decode($database_cache_value[0]['value'], true);
            $expiry = $database_cache_value[0]['expiry'];
        }
    }

    $current_time = gmdate('U');
    if (isset($value['value'])) {
        if ($current_time <= $value['expiry']) {
            log_debug(sprintf("CACHE::STATUS:HIT, TYPE:%d, KEY:%s, EPOCH: %d, EXPIRES:%d", $cache_type, $key, $current_time, $expiry - $current_time));
            return $value['value'];
        } else {
            log_debug(sprintf("CACHE::STATUS:EXPIRED, TYPE:%d, KEY:%s, EPOCH: %d, EXPIRES:%d", $cache_type, $key, $current_time, $expiry - $current_time));
            deleteExpiredCacheValues($current_time);
            return null;
        }
    } else {
        log_debug(sprintf("CACHE::STATUS:MISS, TYPE:%d, KEY:%s, EPOCH: %d, EXPIRES:%d", $cache_type, $key, $current_time, isset($value) ? $expiry - $current_time: 0));
        return null;
    }
}

function setCache($key, $value, $timeout, $cache_type = CacheType::DATABASE)
{
    $cache_key = sprintf('cache_%s', $key);
    $cache_expiry = gmdate('U') + $timeout;
    $cache_value = ["value" => $value, "expiry" => $cache_expiry];
    if ($cache_type == CacheType::SESSION) {
        $_SESSION[$cache_key] = $cache_value;
    } else if ($cache_type == CacheType::DATABASE) {
        setDatabaseCacheValue($cache_key, json_encode($cache_value), $cache_expiry);
    }
}

function get($url, $bmltAuth = false, $cache_expiry = 0, $cache_type = CacheType::DATABASE)
{
    $data = $cache_expiry > 0 ? getCache($url) : null;
    if ($data == null) {
        log_debug($url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
        if ($bmltAuth) {
            curl_setopt($ch, CURLOPT_COOKIE, getBMLTAuthSessionCookies());
        }
        $data = curl_exec($ch);
        $errorno = curl_errno($ch);
        curl_close($ch);
        if ($errorno > 0) {
            throw new CurlException(curl_strerror($errorno));
        } else if ($cache_expiry > 0) {
            setCache($url, $data, $cache_expiry, $cache_type);
        }
    }

    return $data;
}

function sms_chunk_split($msg)
{
    $chunk_width = 1575;
    $chunks = wordwrap($msg, $chunk_width, '\n');
    return explode('\n', $chunks);
}

function get_jft($sms = false)
{
    $d = new DOMDocument();
    $d->validateOnParse = true;
    $result = null;

    if (setting('word_language') == 'en-US' || setting('word_language') == 'en-AU') {
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
    } else if (setting('word_language') == 'es-ES' || setting('word_language') == 'es-US') {
        $url = 'https://forozonalatino.org/sxh';
        $jft_language_dom_element = '*[@id=\'sx-wrapper\']';
        $copyright_info = 'Servicio del Foro Zonal Latinoamericano, Copyright 2017 NA World Services, Inc. Todos los Derechos Reservados.';
        $preg_search_lang = "\r\n\s";
        $preg_replace_lang = " ";
    } else if (setting('word_language') == 'fr-FR' || setting('word_language') == 'fr-CA') {
        $url = 'https://jpa.narcotiquesanonymes.org';
        $jft_language_dom_element = '*[@class=\'contenu-principal\']';
        $copyright_info = 'Copyright (c) 2007-'.date("Y").', NA World Services, Inc. All Rights Reserved';
        $preg_search_lang = "\r\n";
        $preg_replace_lang = "\n\n";
    }

    $jft = new DOMDocument;
    libxml_use_internal_errors(true);
    $d->loadHTML(mb_convert_encoding(get($url, false, 3600), 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    $xpath = new DOMXpath($d);
    $body = $xpath->query("//$jft_language_dom_element");
    foreach ($body as $child) {
        $jft->appendChild($jft->importNode($child, true));
    }
    $result .= $jft->saveHTML();

    $stripped_results = strip_tags($result);
    $without_tabs     = str_replace("\t", "", $stripped_results);
    $trim_results     = trim($without_tabs);
    if ($sms == true) {
        $without_htmlentities = html_entity_decode($trim_results);
        $without_extranewlines = preg_replace("/[$preg_search_lang]+/", "$preg_replace_lang", $without_htmlentities);
        $message = sms_chunk_split($without_extranewlines);
        $finalMessage  = array();
        if (count($message) > 1) {
            for ($i = 0; $i < count($message); $i++) {
                $jft_message = "(" .($i + 1). " of " .count($message). ")\n" .$message[$i];
                array_push($finalMessage, $jft_message);
            }
        } else {
            array_push($finalMessage, $message);
        }
        return $finalMessage;
    } else {
        $final_array = explode("\n", preg_replace('/&(?!#?[a-z0-9]+;)/', '&amp;', html_entity_decode($trim_results, ENT_QUOTES, "UTF-8")));
        array_push($final_array, $copyright_info);
        return $final_array;
    }
}

function getIvrResponse($redirected_from = null, $prior_digit = null, $expected_exacts = array(), $expected_likes = array(), $field = 'Digits')
{
    $response = "0";

    if (isset($_REQUEST[$field])) {
        $response = $_REQUEST[$field];
    } elseif (isset($_REQUEST['SpeechResult'])) {
        $response = intval($_REQUEST['SpeechResult']);
    }

    if (count($expected_exacts) > 0 || count($expected_likes) > 0) {
        $found_at_least_once = false;
        foreach ($expected_exacts as $expected_exact) {
            if ($expected_exact === intval($response)) {
                $found_at_least_once = true;
            }
        }

        if (!$found_at_least_once) {
            foreach ($expected_likes as $expected_like) {
                if (str_exists($response, $expected_like)) {
                    $found_at_least_once = true;
                }
            }
        }

        if (!$found_at_least_once) {
            $qs = $prior_digit != null ? "?Digits=" . $prior_digit : "";?>
            <Response>
                <Say voice="<?php echo voice() ?>" language="<?php echo setting('language') ?>"><?php echo word('you_might_have_invalid_entry') ?></Say>
                <Redirect><?php echo $redirected_from . $qs?></Redirect>
            </Response>
            <?php
            exit();
        }
    }

    return $response;
}

function getWebhookUrl()
{
    $voice_url = str_replace("/endpoints", "", "https://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']);
    if (strpos(basename($voice_url), ".php")) {
        return substr($voice_url, 0, strrpos($voice_url, "/"));
    } else if (strpos($voice_url, "?")) {
        return substr($voice_url, 0, strrpos($voice_url, "?"));
    } else {
        return $voice_url;
    }
}

function getInputType()
{
    return has_setting('speech_gathering') && json_decode(setting('speech_gathering')) ? "speech dtmf" : "dtmf";
}

function getPressWord()
{
    return has_setting('speech_gathering') && json_decode(setting('speech_gathering')) ? word('press_or_say') : word('press');
}

function getCurrentTime()
{
    return gmdate("Y-m-d H:i:s");
}

function unique_stdclass_array($array)
{
    $array = array_map('json_encode', $array);
    $array = array_unique($array);
    return array_map('json_decode', array_values($array));
}

function getReportsServiceBodies()
{
    if (intval($_REQUEST['service_body_id']) == 0) {
        return array_column(getServiceBodiesForUser(true), "id");
    } else if (json_decode($_REQUEST['recurse'])) {
        return getServiceBodiesForUserRecursively($_REQUEST['service_body_id']);
    } else {
        return [$_REQUEST['service_body_id']];
    }
}

function adjustedCallRecords($service_body_ids, $page = 1, $size = 10)
{
    $callRecords = getCallRecords($service_body_ids, $page, $size);

    foreach ($callRecords as &$callRecord) {
        $callEvents = isset($callRecord['call_events']) ? unique_stdclass_array(json_decode($callRecord['call_events'])) : [];

        if (!isset($callEvents)) {
            log_debug("callEvents issue");
        }

        foreach ($callEvents as &$callEvent) {
            $callEvent->parent_callsid = $callRecord['callsid'];
            $callEvent->event_id = EventId::getEventById($callEvent->event_id);
            $callEvent->meta = json_encode($callEvent->meta);
        }
        $callRecord['call_events'] = $callEvents;
    }

    $response['data'] = $callRecords;
    $response['last_page'] = getCallRecordsCount($service_body_ids, $size);

    return $response;
}

function getSessionLink($shouldUriEncode = false)
{
    if (isset($_REQUEST['ysk'])) {
        $session_id = $_REQUEST['ysk'];
    } else if (isset($_REQUEST['PHPSESSID'])) {
        $session_id = $_REQUEST['PHPSESSID'];
    } else if (isset($_COOKIE['PHPSESSID'])) {
        $session_id = $_COOKIE['PHPSESSID'];
    } else {
        $session_id = null;
    }

    return (isset($session_id) ? ($shouldUriEncode ? "&amp;" : "&") . ("ysk=" . $session_id) : "");
}
require_once "legacydata.php";
