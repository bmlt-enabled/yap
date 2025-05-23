<?php

namespace App\Services;

use App\Constants\LocationSearchMethod;
use App\Constants\MeetingResultSort;
use App\Constants\SearchType;
use App\Constants\SettingSource;
use App\Structures\Localizations;
use DateTimeZone;

class SettingsService
{
    private string $version = "4.5.0";
    private array $allowlist = [
        'announce_servicebody_volunteer_routing' => ['description' => '/helpline/announce_servicebody_volunteer_routing' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'blocklist' => ['description' => '/general/blocklist' , 'default' => '', 'overridable' => true, 'hidden' => false],
        'bmlt_root_server' => ['description' => 'The root server to use.' , 'default' => '', 'overridable' => false, 'hidden' => false],
        'bmlt_auth' => ['description' => '' , 'default' => true, 'overridable' => false, 'hidden' => false],
        'call_routing_filter' => ['description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
        'config' => ['description' => '' , 'default' => null, 'overridable' => true, 'hidden' => true],
        'custom_geocoding' => ['description' => '/general/custom-geocoding', 'default' => [], 'overridable' => true, 'hidden' => false],
        'custom_extensions' => ['description' => '/helpline/custom-extensions', 'default' => [0 => ''], 'overridable' => true, 'hidden' => false],
        'custom_query' => ['description' => '/meeting-search/custom-query', 'default' => '&sort_results_by_distance=1&long_val={LONGITUDE}&lat_val={LATITUDE}&geo_width={SETTING_MEETING_SEARCH_RADIUS}&weekdays={DAY}', 'overridable' => true, 'hidden' => false],
        'digit_map_search_type' => ['description' => '/helpline/custom-extensions/', 'default' => ['1' => SearchType::VOLUNTEERS, '2' => SearchType::MEETINGS, '3' => SearchType::JFT, '4' => SearchType::SPAD, '9' => SearchType::DIALBACK], 'overridable' => true, 'hidden' => false],
        'digit_map_location_search_method' => ['description' => '', 'default' => ['1' => LocationSearchMethod::VOICE, '2' => LocationSearchMethod::DTMF, '3' => SearchType::JFT, '4' => SearchType::SPAD], 'overridable' => true, 'hidden' => false],
        'disable_postal_code_gather' => ['description' => '/general/disabling-postal-code-gathering', 'default' => false, 'overridable' => true, 'hidden' => false],
        'docs_base' => ['description' => '', 'default' => 'https://yap.bmlt.app', 'overridable' => true, 'hidden' => true],
        'extension_dial' => ['description' => '/helpline/extension-dial', 'default' => false, 'overridable' => true, 'hidden' => false],
        'fallback_number' => ['description' => '/general/fallback' , 'default' => null, 'overridable' => true, 'hidden' => false],
        'gather_hints' => ['description' => '/general/voice-recognition-options' , 'default' => '', 'overridable' => true, 'hidden' => false],
        'gather_language' => ['description' => '/general/voice-recognition-options' , 'default' => 'en-US', 'overridable' => true, 'hidden' => false],
        'gender_no_preference' => ['description' => '/helpline/specialized-routing', 'default' => false, 'overridable' => true, 'hidden' => false],
        'grace_minutes' => ['description' => '/meeting-search/grace-period' , 'default' => 15, 'overridable' => true, 'hidden' => false],
        'helpline_bmlt_root_server' => ['description' => '/helpline/different-bmlt-for-routing' , 'default' => null, 'overridable' => false, 'hidden' => false],
        'helpline_fallback' => ['description' => '/general/fallback', 'default' => '', 'overridable' => true, 'hidden' => false],
        'helpline_search_radius' => ['description' => '/helpline/helpline-search-radius' , 'default' => 30, 'overridable' => true, 'hidden' => false],
        'ignore_formats' => ['description' => '/meeting-search/ignoring-certain-formats' , 'default' => null, 'overridable' => true, 'hidden' => false],
        'include_format_details' => ['description' => '/meeting-search/venue-options' , 'default' => [], 'overridable' => true, 'hidden' => false],
        'include_distance_details'  => ['description' => '/meeting-search/sms-options#adding-distance-details' , 'default' => null, 'overridable' => true, 'hidden' => false],
        'include_location_text' => ['description' => '/meeting-search/sms-options#adding-location-text', 'default' => false, 'overridable' => true, 'hidden' => false],
        'include_map_link' => ['description' => '/meeting-search/sms-options#adding-map-links' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'include_unpublished' => ['description' => '/meeting-search/including-unpublished' , 'default' => 0, 'overridable' => true, 'hidden' => false],
        'infinite_searching' => ['description' => '/meeting-search/post-call-options#infinite-searches' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'initial_pause' => ['description' => '/general/initial-pause' , 'default' => 2, 'overridable' => true, 'hidden' => false],
        'jft_option' => ['description' => '/miscellaneous/playback-for-readings' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'language' => ['description' => '/general/language-options' , 'default' =>  'en-US', 'overridable' => true, 'hidden' => false],
        'language_selections' => ['description' => '/general/language-options', 'default' => null, 'overridable' => true, 'hidden' => false],
        'language_selections_greeting' => ['description' => '/general/language-options', 'default' => null, 'overridable' => true, 'hidden' => false],
        'language_selections_tagging' => ['description' => '/general/language-options', 'default' => null, 'overridable' => true, 'hidden' => false],
        'location_lookup_bias' => ['description' => '/general/location-lookup-bias' , 'default' => 'country:us', 'overridable' => true, 'hidden' => false],
        'meeting_result_sort' => ['description' => '/meeting-search/sorting-results' , 'default' => MeetingResultSort::TODAY, 'overridable' => true, 'hidden' => false],
        'meeting_search_radius' => ['description' => '/meeting-search/meeting-search-radius' , 'default' => -50, 'overridable' => true, 'hidden' => false],
        'mobile_check' => ['description' => '/meeting-search/mobile-check' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'phone_number_validation' => ['description' => '/general/phone-number-validation' , 'default' => 'US', 'overridable' => true, 'hidden' => false],
        'postal_code_length' => ['description' => '/general/postal-code-lengths' , 'default' => 5, 'overridable' => true, 'hidden' => false],
        'province_lookup' => ['description' => '/general/stateprovince-lookup' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'province_lookup_list' => ['description' => '/general/stateprovince-lookup' , 'default' => [], 'overridable' => true, 'hidden' => false],
        'pronunciations' => ['descriptions' => '/meeting-search/pronunciations', 'default' => [], 'overridable' => true, 'hidden' => false],
        'result_count_max' => ['description' => '/meeting-search/results-counts-maximums' , 'default' => 5, 'overridable' => true, 'hidden' => false],
        'say_links' => ['description' => '/meeting-search/say-links', 'default' => false, 'overridable' => true, 'hidden' => false],
        'service_body_id' => ['description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
        'service_body_config_id' => ['description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
        'sms_ask' => ['description' => '/meeting-search/post-call-options#making-sms-results-for-voice-calls-optional' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'sms_disable' => ['description' => '/meeting-search/post-call-options#disable-meeting-results-sms' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'sms_bias_bypass' => ['description' => '' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'sms_blackhole' => ['description' => '/meeting-search/sms-options#blackhole' , 'default' => '', 'overridable' => true, 'hidden' => false],
        'sms_combine' => ['description' => '/meeting-search/sms-options#combine-results' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'sms_dialback_options' => ['description' => '/helpline/dialback' , 'default' => 0, 'overridable' => true, 'hidden' => false],
        'sms_helpline_keyword' => ['description' => '/helpline/sms-volunteer-routing', 'default' => 'talk', 'overridable' => true, 'hidden' => false],
        'sms_summary_page' => ['description' => '/meeting-search/results-counts-maximums', 'default' => false, 'overridable' => true, 'hidden' => false],
        'spad_option' => ['description' => '/miscellaneous/playback-for-readings' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'speech_gathering' => ['description' => '/general/voice-recognition-options', 'default' => false, 'overridable' => true, 'hidden' => false],
        'suppress_voice_results' => ['description' => '/meeting-search/post-call-options#suppress-voice-results', 'default' => false, 'overridable' => true, 'hidden' => false],
        'time_format' => ['description' => '', 'default' => 'g:i A', 'overridable' => true, 'hidden' => false],
        'timezone_default' => ['description' => '', 'default' => null, 'overridable' => true, 'hidden' => false],
        'title' => ['description' => '' , 'default' => '', 'overridable' => true, 'hidden' => false],
        'toll_province_bias' => ['description' => '/general/tollfree-province-bias' , 'default' => null, 'overridable' => true, 'hidden' => false],
        'toll_free_province_bias' => ['description' => '/general/tollfree-province-bias' , 'default' => '', 'overridable' => true, 'hidden' => false],
        'tomato_helpline_routing' => ['description' => '/helpline/tomato-helpline-routing', 'default' => false, 'overridable' => true, 'hidden' => false],
        'tomato_meeting_search' => ['description' => '/meeting-search/tomato-meeting-search', 'default' => false, 'overridable' => true, 'hidden' => false],
        'tomato_url' => ['description' => '' , 'default' => 'https://aggregator.bmltenabled.org/main_server', 'overridable' => true, 'hidden' => false],
        'twilio_account_sid' => ['description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
        'twilio_auth_token' => ['description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
        'voice' => ['description' => '/general/language-options', 'default' => 'Polly.Kendra', 'overridable' => true, 'hidden' => false],
        'voicemail_playback_grace_hours' => ['description' => '', 'default' => 48, 'overridable' => true, 'hidden' => false],
        'volunteer_auto_answer' => ['description' => '/helpline/volunteer-auto-answer', 'default'=>false, 'overridable' => true, 'hidden' => false],
        'word_language' => ['description' => '', 'default' => 'en-US', 'overridable' => true, 'hidden' => false]
    ];

    public static array $dateCalculationsMap =
        [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    private static array $numbers =
        ["zero", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine"];
    private array $settings = [];
    private array $available_languages = [
        "en-US" => "English",
        "en-AU" => "English (Australian)",
        "es-US" => "Español (United States)",
        "pig-latin" => "Igpay Atinlay",
        "pt-BR" => "Português (Brazil)",
        "fr-CA" => "Français (Canada)",
        "it-IT" => "Italian (Italy)"
    ];
    private array $available_prompts = [
        "greeting",
        "voicemail_greeting",
        "custom_extensions_greeting"
    ];
    private array $requiredSettings = [
        'title',
        'bmlt_root_server',
        'google_maps_api_key',
        'twilio_account_sid',
        'twilio_auth_token',
        'mysql_hostname',
        'mysql_username',
        'mysql_password',
        'mysql_database'
    ];
    private array $emailSettings = [
        'smtp_host',
        'smtp_username',
        'smtp_password',
        'smtp_secure',
        'smtp_from_address',
        'smtp_from_name'
    ];

    private object $localizations;
    private string $shortLanguage;
    private bool $randomConferences = true;

    public function __construct()
    {
        @include($this->getConfigFilenameForEnvironment());
        $this->settings = get_defined_vars();
        $this->localizations = new Localizations();

        foreach ($this->available_languages as $available_language_key => $available_language_value) {
            foreach ($this->available_prompts as $available_prompt) {
                $this->allowlist[str_replace("-", "_", $available_language_key) . "_" . $available_prompt] = [ 'description' => '', 'default' => null, 'overridable' => true, 'hidden' => false];
                $this->allowlist[str_replace("-", "_", $available_language_key) . "_voice"] = [ 'description' => '', 'default' => 'alice', 'overridable' => true, 'hidden' => false];
            }
        }

        $this->shortLanguage = $this->getWordLanguage() === "da-DK" ? "dk" : explode("-", $this->getWordLanguage())[0];

        foreach ($this->settings as $setting_key => $setting_value) {
            $this->setLocalizationOverride($setting_key, $setting_value);
        }

        foreach (session()->all() as $sessionKey => $sessionValue) {
            $this->setLocalizationOverride($sessionKey, $sessionValue);
        }
    }

    public function getConfigFilenameForEnvironment(): string
    {
        return !getenv("ENVIRONMENT") ? base_path() . '/config.php' :
            base_path() . '/config.' . getenv("ENVIRONMENT") . '.php';
    }

    public function getShortLanguage(): string
    {
        return $this->shortLanguage;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function allowlist(): array
    {
        return $this->allowlist;
    }

    public function has($name): bool
    {
        return !is_null($this->get($name));
    }

    public function geocodingApiUri(): string
    {
        return sprintf(
            "https://maps.googleapis.com/maps/api/geocode/json?key=%s",
            $this->get("google_maps_api_key")
        );
    }

    public function timezoneApiUri(): string
    {
        return sprintf(
            "https://maps.googleapis.com/maps/api/timezone/json?key=%s",
            $this->get("google_maps_api_key")
        );
    }

    public function get($name)
    {
        if (isset($this->allowlist[$name]) && $this->allowlist[$name]['overridable']) {
            if (request()->has($name) && $this->allowlist[$name]['hidden'] !== true) {
                return request()->get($name);
            } elseif (session()->has("override_" . $name)) {
                return session()->get("override_" . $name);
            }
        }

        if ($name === "google_maps_api_key" && env("GOOGLE_MAPS_API_KEY")) {
            return env("GOOGLE_MAPS_API_KEY");
        } elseif (isset($this->settings[$name])) {
            return $this->settings[$name];
        } elseif (isset($this->allowlist[$name]['default'])) {
            return $this->allowlist[$name]['default'];
        }

        return null;
    }

    public function source($name): string
    {
        if (request()->has($name)) {
            return SettingSource::QUERYSTRING;
        } elseif (session()->has("override_" . $name)) {
            return SettingSource::SESSION;
        } elseif (isset($GLOBALS[$name])) {
            return SettingSource::CONFIG;
        } elseif (isset($this->allowlist[$name]['default'])) {
            return SettingSource::DEFAULT_SETTING;
        } else {
            return "NOT SET";
        }
    }

    public function getDigitForAction($setting, $action)
    {
        $searchTypeSequence = $this->getDigitMapSequence($setting);
        foreach ($searchTypeSequence as $digit => $type) {
            if ($type == $action) {
                return $digit;
            }
        }
    }

    public function getDigitMapSequence($setting)
    {
        $digitMap = $this->getDigitMap($setting);
        ksort($digitMap);
        return $digitMap;
    }

    public function getDigitMap($setting)
    {
        $digitMapSetting = $this->get($setting);

        if ($setting == 'language_selections') {
            $language_selection_digit_map = [];
            for ($i = 0; $i <= count(explode(",", $this->get('language_selections'))); $i++) {
                $language_selection_digit_map[] = $i;
            }

            return $language_selection_digit_map;
        }

        if (!json_decode($this->get('jft_option'))) {
            if (($key = array_search(SearchType::JFT, $digitMapSetting)) !== false) {
                unset($digitMapSetting[$key]);
            }
        }

        if (!json_decode($this->get('spad_option'))) {
            if (($key = array_search(SearchType::SPAD, $digitMapSetting)) !== false) {
                unset($digitMapSetting[$key]);
            }
        }

        if (json_decode($this->get('disable_postal_code_gather'))) {
            if (($key = array_search(LocationSearchMethod::DTMF, $digitMapSetting)) !== false) {
                unset($digitMapSetting[$key]);
            }
        }

        return $digitMapSetting;
    }

    public function getPossibleDigits($setting)
    {
        return array_keys($this->getDigitMap($setting));
    }

    public function set($name, $value): void
    {
        $this->settings[$name] = $value;
    }

    public function setWord($word, $value, $language = null): void
    {
        if ($language == null) {
            $language = $this->getWordLanguage();
        }
        $this->localizations->getLocalization($language)[$word] = $value;
    }

    public function getWordLanguage(): string
    {
        foreach ($this->available_languages as $key => $available_language) {
            if ($key == $this->get('word_language')) {
                return $key;
            }
        }

        return "";
    }

    public function getPressWord($language = null)
    {
        return $this->has('speech_gathering')
        && json_decode($this->get('speech_gathering')) ? $this->word('press_or_say', $language) : $this->word('press', $language);
    }

    public function word($name, $language = null)
    {
        if ($language == null) {
            $language = $this->getWordLanguage();
        }
        return $this->localizations->getLocalization($language)['override_' . $name] ?? $this->localizations->getLocalization($language)[$name];
    }

    public function availableLanguages(): array
    {
        return $this->available_languages;
    }

    public function languageSelections(): array
    {
        if ($this->has('language_selections_tagging') && $this->get('language_selections_tagging') !== "") {
            return explode(",", $this->get('language_selections_tagging'));
        } else {
            return explode(",", $this->get('language_selections'));
        }
    }

    public function getNumberForWord($name)
    {
        $numbers = self::$numbers;
        for ($n = 0; $n < count($numbers); $n++) {
            if ($name == $numbers[$n]) {
                return $n;
            }
        }
    }

    public function getWordForNumber($number, $language = null)
    {
        return $this->word(self::$numbers[$number], $language);
    }

    public function getBMLTRootServer(): string
    {
        if (json_decode($this->get('tomato_meeting_search'))) {
            return $this->get('tomato_url');
        } else {
            return $this->get('bmlt_root_server');
        }
    }

    public function getAdminBMLTRootServer(): string
    {
        if ($this->has('helpline_bmlt_root_server')) {
            return $this->get('helpline_bmlt_root_server');
        } else {
            return $this->get('bmlt_root_server');
        }
    }

    public function getInputType(): string
    {
        return $this->has('speech_gathering') &&
        json_decode($this->get('speech_gathering')) ? "speech dtmf" : "dtmf";
    }

    public function voice($current_language = null)
    {
        if (!isset($current_language)) {
            $current_language = str_replace("-", "_", $this->get('language'));
        }

        if ($this->has($current_language . "_voice")) {
            return $this->get($current_language . "_voice");
        } else {
            return $this->get('voice');
        }
    }

    public function getSessionLink(): string
    {
        // Attempt to retrieve the session ID from the query string or session
        if (request()->has('ysk')) {
            $session_id = request()->get('ysk');
        } elseif (session()->has('_token')) {
            $session_id = session()->getId();
        } else {
            $session_id = null;
        }

        // Return the query string parameter if a session ID is found
        return isset($session_id) ? "&ysk={$session_id}" : "";
    }

    public function minimalRequiredSettings(): array
    {
        return $this->requiredSettings;
    }

    public function emailSettings(): array
    {
        return $this->emailSettings;
    }

    public function getCurrentTime(): string
    {
        return gmdate("Y-m-d H:i:s");
    }

    /**
     * @param int|string $session_key
     * @param mixed $session_value
     * @return void
     */
    public function setLocalizationOverride(int|string $session_key, mixed $session_value):  void
    {
        $language = $this->getWordLanguage();
        $stripped_key_test = str_replace("override_", "", $session_key);
        if (isset($this->localizations->getLocalization($language)[$stripped_key_test])) {
            $this->localizations->getLocalization($language)[$session_key] = $session_value;
        }

        foreach ($this->availableLanguages() as $language_key => $language_value) {
            $language_key_with_underscore = sprintf("%s_", str_replace("-", "_", $language_key));
            if (str_contains($session_key, $language_key_with_underscore)) {
                $stripped_key_test = str_replace($language_key_with_underscore, "", str_replace("override_", "", $session_key));
                if (isset($this->localizations->getLocalization($language_key)[$stripped_key_test])) {
                    $this->localizations->getLocalization($language_key)[$stripped_key_test] = $session_value;
                }
            }
        }
    }
}
