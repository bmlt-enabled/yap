<?php
if (!file_exists('config.php')) {
    header(sprintf('Location: %s', str_exists($_SERVER['REQUEST_URI'], 'admin') ? 'installer.php' : 'admin/installer.php'), true, 302);
    exit();
}
if (isset($_GET["ysk"])) {
    session_id($_GET["ysk"]);
}
@session_start();
require_once(!getenv("ENVIRONMENT") ? base_path() . '/config.php' : base_path() . '/config.' . getenv("ENVIRONMENT") . '.php');
$ignored_vars = ['__path', '__env', '__data', 'includePath', 'ignored_vars', 'app', 'errors', 'key', 'value'];
foreach (get_defined_vars() as $key => $value) {
    if (!in_array($key, $ignored_vars)) {
        $GLOBALS[$key] = $value;
    }
}
require_once 'constants.php';
require_once 'migrations.php';
require_once 'queries.php';
require_once 'logging.php';
if (isset($_GET["CallSid"])) {
    insertSession($_GET["CallSid"]);
}
$GLOBALS['version']  = "4.3.0";
$GLOBALS['settings_allowlist'] = [
    'announce_servicebody_volunteer_routing' => [ 'description' => '/helpline/announce_servicebody_volunteer_routing' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'blocklist' => [ 'description' => '/general/blocklist' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'bmlt_root_server' => [ 'description' => 'The root server to use.' , 'default' => '', 'overridable' => false, 'hidden' => false],
    'bmlt_auth' => [ 'description' => '' , 'default' => true, 'overridable' => false, 'hidden' => false ],
    'call_routing_filter' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'config' => [ 'description' => '' , 'default' => null, 'overridable' => true, 'hidden' => true],
    'custom_geocoding' => ['description' => '/general/custom-geocoding', 'default' => [], 'overridable' => true, 'hidden' => false],
    'custom_extensions' => ['description' => '/helpline/custom-extensions', 'default' => [0 => ''], 'overridable' => true, 'hidden' => false],
    'custom_query' => ['description' => '/meeting-search/custom-query', 'default' => '&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width={SETTING_MEETING_SEARCH_RADIUS}&weekdays={DAY}', 'overridable' => true, 'hidden' => false],
    'digit_map_search_type' => [ 'description' => '/helpline/custom-extensions/', 'default' => ['1' => SearchType::VOLUNTEERS, '2' => SearchType::MEETINGS, '3' => SearchType::JFT, '4' => SearchType::SPAD, '9' => SearchType::DIALBACK], 'overridable' => true, 'hidden' => false],
    'digit_map_location_search_method' => [ 'description' => '', 'default' => ['1' => LocationSearchMethod::VOICE, '2' => LocationSearchMethod::DTMF, '3' => SearchType::JFT, '4' => SearchType::SPAD], 'overridable' => true, 'hidden' => false],
    'disable_postal_code_gather' => [ 'description' => '/general/disabling-postal-code-gathering', 'default' => false, 'overridable' => true, 'hidden' => false],
    'docs_base' => [ 'description' => '', 'default' => 'https://yap.bmlt.app', 'overridable' => true, 'hidden' => true],
    'extension_dial' => [ 'description' => '/helpline/extension-dial', 'default' => false, 'overridable' => true, 'hidden' => false],
    'fallback_number' => [ 'description' => '/general/fallback' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'gather_hints' => [ 'description' => '/general/voice-recognition-options' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'gather_language' => [ 'description' => '/general/voice-recognition-options' , 'default' => 'en-US', 'overridable' => true, 'hidden' => false],
    'gender_no_preference' => ['description' => '/helpline/specialized-routing', 'default' => false, 'overridable' => true, 'hidden' => false],
    'grace_minutes' => [ 'description' => '/meeting-search/grace-period' , 'default' => 15, 'overridable' => true, 'hidden' => false],
    'helpline_bmlt_root_server' => [ 'description' => '/helpline/different-bmlt-for-routing' , 'default' => null, 'overridable' => false, 'hidden' => false],
    'helpline_fallback' => [ 'description' => '/general/fallback', 'default' => '', 'overridable' => true, 'hidden' => false],
    'helpline_search_radius' => [ 'description' => '/helpline/helpline-search-radius' , 'default' => 30, 'overridable' => true, 'hidden' => false],
    'ignore_formats' => [ 'description' => '/meeting-search/ignoring-certain-formats' , 'default' => null, 'overridable' => true, 'hidden' => false],
    'include_format_details' => [ 'description' => '/meeting-search/venue-options' , 'default' => [], 'overridable' => true, 'hidden' => false],
    'include_distance_details'  => [ 'description' => '/meeting-search/sms-options#adding-distance-details' , 'default' => null, 'overridable' => true, 'hidden' => false],
    'include_location_text' => [ 'description' => '/meeting-search/sms-options#adding-location-text', 'default' => false, 'overridable' => true, 'hidden' => false],
    'include_map_link' => [ 'description' => '/meeting-search/sms-options#adding-map-links' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'include_unpublished' => [ 'description' => '/meeting-search/including-unpublished' , 'default' => 0, 'overridable' => true, 'hidden' => false],
    'infinite_searching' => [ 'description' => '/meeting-search/post-call-options#infinite-searches' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'initial_pause' => [ 'description' => '/general/initial-pause' , 'default' => 2, 'overridable' => true, 'hidden' => false],
    'jft_option' => [ 'description' => '/miscellaneous/playback-for-readings' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'language' => [ 'description' => '/general/language-options' , 'default' =>  'en-US', 'overridable' => true, 'hidden' => false],
    'language_selections' => [ 'description' => '/general/language-options', 'default' => null, 'overridable' => true, 'hidden' => false],
    'location_lookup_bias' => [ 'description' => '/general/location-lookup-bias' , 'default' => 'country:us', 'overridable' => true, 'hidden' => false],
    'meeting_result_sort' => [ 'description' => '/meeting-search/sorting-results' , 'default' => MeetingResultSort::TODAY, 'overridable' => true, 'hidden' => false],
    'meeting_search_radius' => [ 'description' => '/meeting-search/meeting-search-radius' , 'default' => -50, 'overridable' => true, 'hidden' => false],
    'mobile_check' => [ 'description' => '/meeting-search/mobile-check' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'postal_code_length' => [ 'description' => '/general/postal-code-lengths' , 'default' => 5, 'overridable' => true, 'hidden' => false],
    'province_lookup' => [ 'description' => '/general/stateprovince-lookup' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'province_lookup_list' => [ 'description' => '/general/stateprovince-lookup' , 'default' => [], 'overridable' => true, 'hidden' => false],
    'result_count_max' => [ 'description' => '/meeting-search/results-counts-maximums' , 'default' => 5, 'overridable' => true, 'hidden' => false],
    'say_links' => [ 'description' => '/meeting-search/say-links', 'default' => false, 'overridable' => true, 'hidden' => false],
    'service_body_id' => [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
    'service_body_config_id' => [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
    'sms_ask' => [ 'description' => '/meeting-search/post-call-options#making-sms-results-for-voice-calls-optional' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_disable' => [ 'description' => '/meeting-search/post-call-options#disable-meeting-results-sms' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_bias_bypass' => [ 'description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_blackhole' => [ 'description' => '/meeting-search/sms-options#blackhole' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'sms_combine' => [ 'description' => '/meeting-search/sms-options#combine-results' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'sms_dialback_options' => [ 'description' => '/helpline/dialback' , 'default' => 0, 'overridable' => true, 'hidden' => false],
    'sms_helpline_keyword' => ['description' => '/helpline/sms-volunteer-routing', 'default' => 'talk', 'overridable' => true, 'hidden' => false],
    'sms_summary_page' => ['description' => '/meeting-search/results-counts-maximums', 'default' => false, 'overridable' => true, 'hidden' => false],
    'spad_option' => [ 'description' => '/miscellaneous/playback-for-readings' , 'default' => false, 'overridable' => true, 'hidden' => false],
    'speech_gathering' => [ 'description' => '/general/voice-recognition-options', 'default' => false, 'overridable' => true, 'hidden' => false],
    'suppress_voice_results' => [ 'description' => '/meeting-search/post-call-options#suppress-voice-results', 'default' => false, 'overridable' => true, 'hidden' => false],
    'time_format' => ['description' => '', 'default' => 'g:i A', 'overridable' => true, 'hidden' => false],
    'timezone_default' => ['description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
    'title' => [ 'description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'toll_province_bias' => [ 'description' => '/general/tollfree-province-bias' , 'default' => null, 'overridable' => true, 'hidden' => false],
    'toll_free_province_bias' => [ 'description' => '/general/tollfree-province-bias' , 'default' => '', 'overridable' => true, 'hidden' => false],
    'tomato_helpline_routing' => [ 'description' => '/helpline/tomato-helpline-routing', 'default' => false, 'overridable' => true, 'hidden' => false],
    'tomato_meeting_search' => [ 'description' => '/meeting-search/tomato-meeting-search', 'default' => false, 'overridable' => true, 'hidden' => false],
    'tomato_url' => [ 'description' => '' , 'default' => 'https://tomato.bmltenabled.org/main_server', 'overridable' => true, 'hidden' => false],
    'twilio_account_sid' => [ 'description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
    'twilio_auth_token' => [ 'description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
    'voice' => [ 'description' => '/general/language-options', 'default' => 'Polly.Kendra', 'overridable' => true, 'hidden' => false],
    'voicemail_playback_grace_hours' => [ 'description' => '', 'default' => 48, 'overridable' => true, 'hidden' => false],
    'word_language' => [ 'description' => '', 'default' => 'en-US', 'overridable' => true, 'hidden' => false]
];
$GLOBALS['available_languages'] = [
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

foreach ($GLOBALS['available_languages'] as $available_language_key => $available_language_value) {
    foreach ($available_prompts as $available_prompt) {
        $GLOBALS['settings_allowlist'][str_replace("-", "_", $available_language_key) . "_" . $available_prompt] = [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false];
        $GLOBALS['settings_allowlist'][str_replace("-", "_", $available_language_key) . "_voice"] = [ 'description' => '', 'default' => 'alice', 'overridable' => true, 'hidden' => false];
    }
}
require_once 'session.php';
checkBlocklist();
if (has_setting('config')) {
    include_once __DIR__ . '/../../config_'.setting('config').'.php';
}
include_once __DIR__ . '/../../lang/' .getWordLanguage().'.php';
$GLOBALS['short_language'] = getWordLanguage() === "da-DK" ? "dk" : explode("-", getWordLanguage())[0];

$GLOBALS['google_maps_endpoint'] = "https://maps.googleapis.com/maps/api/geocode/json?key=" . trim($GLOBALS['google_maps_api_key']);
$GLOBALS['timezone_lookup_endpoint'] = "https://maps.googleapis.com/maps/api/timezone/json?key=" . trim($GLOBALS['google_maps_api_key']);
$GLOBALS['date_calculations_map'] = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
$GLOBALS['numbers'] = ["zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine"];

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

class CallConfig
{
    public $voicemail_url;
    public $volunteer_routing_params;
    public $options;
    public $volunteer;
}

class AlertId
{
    const STATUS_CALLBACK_MISSING = 1;
}

class SmsDialbackOptions
{
    const VOLUNTEER_NOTIFICATION = 1;
    const VOICEMAIL_NOTIFICATION = 2;
}

class CacheType
{
    const SESSION = 1;
    const DATABASE = 2;
}

class ReadingType
{
    const JFT = 1;
    const SPAD = 2;
}

class AdminInterfaceRights
{
    const MANAGE_USERS = 1;
}

class EventStatusId
{
    const VOICEMAIL_DELETED = 1;
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
    const VOICEMAIL_PLAYBACK = 16; // Dead feature
    const DIALBACK = 17;
    const PROVINCE_LOOKUP_LIST = 18;
    const MEETING_SEARCH_SMS = 19;
    const VOLUNTEER_SEARCH_SMS = 20;
    const JFT_LOOKUP_SMS = 21;
    const SMS_BLACKHOLED = 22;
    const SPAD_LOOKUP = 23;
    const SPAD_LOOKUP_SMS = 24;

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
                return "Caller Consented to Receive SMS; Meeting Search Location Gathered";
            case self::HELPLINE_ROUTE:
                return "Helpline Route";
            case self::VOICEMAIL_PLAYBACK:
                return "Voicemail Playback";
            case self::DIALBACK:
                return "Dialback";
            case self::PROVINCE_LOOKUP_LIST:
                return "Province Lookup List";
            case self::MEETING_SEARCH_SMS:
                return "Meeting Search via SMS";
            case self::VOLUNTEER_SEARCH_SMS:
                return "Volunteer Search via SMS";
            case self::JFT_LOOKUP_SMS:
                return "JFT Looksup via SMS";
            case self::SMS_BLACKHOLED:
                return "SMS Blackholed";
            case self::SPAD_LOOKUP:
                return "SPAD Lookup";
            case self::SPAD_LOOKUP_SMS:
                return "SPAD Lookup via SMS";
        }
    }
}

class Coordinates
{
    public $location;
    public $latitude;
    public $longitude;
}

class MeetingResults
{
    public $originalListCount = 0;
    public $filteredList = [];
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

class RecordType
{
    const PHONE = 1;
    const SMS = 2;

    public static function getTypeById($id)
    {
        switch ($id) {
            case RecordType::PHONE:
                return "CALL";
            case RecordType::SMS:
                return "SMS";
        }
    }
}

class CurlException extends Exception
{
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

    public static function getState($status = null, $message = null, $warnings = "")
    {
        try {
            $build = file_get_contents("build.txt", false);
        } catch (Exception $e) {
            $build = $e->getMessage();
        }
        return ["status"=>$status, "message"=>$message, "warnings"=>$warnings, "version"=>$GLOBALS['version'], "build"=>str_replace("\n", "", $build)];
    }

    public static function isAllowedError($exceptionName)
    {
        if (isset($GLOBALS["exclude_errors_on_login_page"]) && isset($_REQUEST['run_exclude_errors_check'])) {
            return !in_array($exceptionName, $GLOBALS["exclude_errors_on_login_page"]);
        }

        return true;
    }

    public static function getStatus()
    {
        $warnings = "";
//        foreach ($GLOBALS['required_config_settings'] as $setting) {
//            if (!self::isThere($setting)) {
//                return self::getState(false, "Missing required setting: " . $setting);
//            }
//        }

        $root_server_settings = json_decode(get(sprintf('%s/client_interface/json/?switcher=GetServerInfo', getAdminBMLTRootServer()), false, 3600));

        if (strpos(getAdminBMLTRootServer(), 'index.php')) {
            return self::getState(false, "Your root server points to index.php. Please make sure to set it to just the root directory.", $warnings);
        }

        if (!isset($root_server_settings)) {
            return self::getState(false, "Your root server returned no server information.  Double-check that you have the right root server url.", $warnings);
        } else {
            if ($root_server_settings[0]->semanticAdmin === "0") {
                return self::getState(false, "Your root server has semanticAdmin disabled, please enable it.  https://bmlt.app/semantic/semantic-administration/", $warnings);
            }
        }

        foreach (setting("digit_map_search_type") as $digit => $value) {
            if ($digit === 0) {
                return self::getState(false, "You cannot use 0 as an option for `digit_map_search_type`.", $warnings);
            }
        }

        try {
            $googleapi_settings = json_decode(get(sprintf("%s&address=91409", $GLOBALS['google_maps_endpoint']), false, 3600));

            if ($googleapi_settings->status == "REQUEST_DENIED") {
                return self::getState(false, "Your Google Maps API key came back with the following error. " . $googleapi_settings->error_message. " Please make sure you have the Google Maps Geocoding API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/", $warnings);
            }

            $timezone_settings = json_decode(get(sprintf("%s&location=34.2011137,-118.475058&timestamp=%d", $GLOBALS['timezone_lookup_endpoint'], time() - (time() % 1800)), false));

            if ($timezone_settings->status == "REQUEST_DENIED") {
                return self::getState(false, "Your Google Maps API key came back with the following error. " . $timezone_settings->errorMessage. " Please make sure you have the Google Time Zone API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/", $warnings);
            }
        } catch (CurlException $e) {
            return self::getState(false, "HTTP Error connecting to Google Maps API, check your network settings.", $warnings);
        }

        $alerts = getMisconfiguredPhoneNumbersAlerts(AlertId::STATUS_CALLBACK_MISSING);
        if (count($alerts) > 0) {
            $misconfiguredPhoneNumbers = [];
            foreach ($alerts as $alert) {
                array_push($misconfiguredPhoneNumbers, $alert['payload']);
            }

            $warnings = sprintf("%s is/are phone numbers that are missing Twilio Call Status Changes Callback status.php webhook. This will not allow call reporting to work correctly.  For more information review the documentation page https://github.com/bmlt-enabled/yap/wiki/Call-Detail-Records.", implode(",", $misconfiguredPhoneNumbers));
        }

        try {
            foreach ($GLOBALS['twilioClient']->incomingPhoneNumbers->read() as $number) {
                if (basename($number->voiceUrl)) {
                    if (!strpos($number->voiceUrl, '.php')
                        && !strpos($number->voiceUrl, 'twiml')
                        && !strpos($number->voiceUrl, '/?')
                        && substr($number->voiceUrl, -1) !== "/") {
                        return self::getState(false, $number->phoneNumber . " webhook should end either with `/` or `/index.php`", $warnings);
                    }
                }
            }
        } catch (\Twilio\Exceptions\RestException $e) {
            return self::getState(false, "Twilio Rest Error: " . $e->getMessage(), $warnings);
        } catch (\Twilio\Exceptions\ConfigurationException $e) {
            if (self::isAllowedError("twilioMissingCredentials")) {
                return self::getState(false, "Twilio Configuration Error: " . $e->getMessage(), $warnings);
            }
        }

        if (has_setting('smtp_host')) {
            foreach (self::$email_settings as $setting) {
                if (!self::isThere($setting)) {
                    return self::getState(false, "Missing required email setting: " . $setting, $warnings);
                }
            }
        }

        if (isset($GLOBALS['mysql_hostname'])) {
            try {
                $db = new Database();
                $db->close();
            } catch (PDOException $e) {
                return self::getState(false, $e->getMessage(), $warnings);
            }
        }

        if (UpgradeAdvisor::$all_good) {
            return UpgradeAdvisor::getState(true, "Ready To Yap!", $warnings);
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

function getOptionForSearchType($searchType)
{
    foreach (setting("digit_map_search_type") as $digit => $value) {
        if ($value == $searchType) {
            return $digit;
        }
    }
    return 0;
}

function getDialbackString($callsid, $dialbackNumber, $option)
{
    $dialback_string = "";
    # Bitwise detection
    if (setting('sms_dialback_options') & $option) {
        $pin_lookup = lookupPinForCallSid($callsid);
        if (count($pin_lookup) > 0) {
            $dialback_digit_map_digit = getOptionForSearchType(SearchType::DIALBACK);
            $dialback_string = sprintf(
                "Tap to dialback: %s,,,%s,,,%s#.  PIN: %s",
                $dialbackNumber,
                $dialback_digit_map_digit,
                $pin_lookup[0]['pin'],
                $pin_lookup[0]['pin']
            );
        }
    }

    return $dialback_string;
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

function getAdminBMLTRootServer()
{
    if (has_setting('helpline_bmlt_root_server')) {
        return setting('helpline_bmlt_root_server');
    } else {
        return setting('bmlt_root_server');
    }
}

function getTimezoneList()
{
    return DateTimeZone::listIdentifiers(DateTimeZone::ALL);
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

function canManageUsers()
{
    return (isset($_SESSION['auth_is_admin']) && boolval($_SESSION['auth_is_admin'])) ||
        (isset($_SESSION['auth_permissions']) && (intval($_SESSION['auth_permissions']) & AdminInterfaceRights::MANAGE_USERS));
}

function isTopLevelAdmin()
{
    return (isset($_SESSION['auth_is_admin']) && boolval($_SESSION['auth_is_admin']));
}

function getServiceBodiesRightsIds()
{
    $ids = [];

    foreach (getServiceBodiesRights() as $service_body) {
        array_push($ids, $service_body->id);
    }

    return $ids;
}

function getServiceBodiesRights()
{
    if (isset($_SESSION['auth_mechanism'])) {
        if ($_SESSION['auth_mechanism'] == AuthMechanism::V1) {
            $url = sprintf('%s/local_server/server_admin/json.php?admin_action=get_permissions', getAdminBMLTRootServer());
            $service_bodies_for_user = json_decode(get($url, true));

            if ($service_bodies_for_user == null) {
                return null;
            }

            if (!is_array($service_bodies_for_user->service_body)) {
                $service_bodies_for_user = array($service_bodies_for_user->service_body);
            } elseif (isset($service_bodies_for_user->service_body)) {
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
            'shares' => json_encode(isset($groupsDataObj->group_shared_service_bodies) ? $groupsDataObj->group_shared_service_bodies : [])
        ]);
    }

    return $groupsArray;
}

function getServiceBodyCallHandlingData($helplineData)
{
    $config = new ServiceBodyCallHandling();
    if (isset($helplineData)) {
        $data = json_decode($helplineData['data'])[0];
        if (isset($data)) {
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
    }

    return $config;
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
    } elseif (is_array($subject)) {
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
    return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +yap';
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

function getCurrentTime()
{
    return gmdate("Y-m-d H:i:s");
}
