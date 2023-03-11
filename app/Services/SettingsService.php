<?php

namespace App\Services;

use App\Constants\LocationSearchMethod;
use App\Constants\MeetingResultSort;
use App\Constants\SearchType;

class SettingsService
{
    private $allowlist = [
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
        'fallback_number' => ['description' => '/general/fallback' , 'default' => '', 'overridable' => true, 'hidden' => false],
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
        'location_lookup_bias' => ['description' => '/general/location-lookup-bias' , 'default' => 'country:us', 'overridable' => true, 'hidden' => false],
        'meeting_result_sort' => ['description' => '/meeting-search/sorting-results' , 'default' => MeetingResultSort::TODAY, 'overridable' => true, 'hidden' => false],
        'meeting_search_radius' => ['description' => '/meeting-search/meeting-search-radius' , 'default' => -50, 'overridable' => true, 'hidden' => false],
        'mobile_check' => ['description' => '/meeting-search/mobile-check' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'postal_code_length' => ['description' => '/general/postal-code-lengths' , 'default' => 5, 'overridable' => true, 'hidden' => false],
        'province_lookup' => ['description' => '/general/stateprovince-lookup' , 'default' => false, 'overridable' => true, 'hidden' => false],
        'province_lookup_list' => ['description' => '/general/stateprovince-lookup' , 'default' => [], 'overridable' => true, 'hidden' => false],
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
        'tomato_url' => ['description' => '' , 'default' => 'https://tomato.bmltenabled.org/main_server', 'overridable' => true, 'hidden' => false],
        'twilio_account_sid' => ['description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
        'twilio_auth_token' => ['description' => '', 'default' => '', 'overridable' => true, 'hidden' => true],
        'voice' => ['description' => '/general/language-options', 'default' => 'Polly.Kendra', 'overridable' => true, 'hidden' => false],
        'voicemail_playback_grace_hours' => ['description' => '', 'default' => 48, 'overridable' => true, 'hidden' => false],
        'word_language' => ['description' => '', 'default' => 'en-US', 'overridable' => true, 'hidden' => false]
    ];

    private $settings = [];

    public function __construct()
    {
        @include(!getenv("ENVIRONMENT") ? base_path() . '/config.php' :
            base_path() . '/config.' . getenv("ENVIRONMENT") . '.php');

        $this->settings = get_defined_vars();
    }

    public function has($name): bool
    {
        return !is_null($this->get($name));
    }

    public function get($name)
    {
        if (isset($this->allowlist[$name]) && $this->allowlist[$name]['overridable']) {
            if (isset($_REQUEST[$name]) && $this->allowlist[$name]['hidden'] !== true) {
                return $_REQUEST[$name];
            } elseif (isset($_SESSION["override_" . $name])) {
                return $_SESSION["override_" . $name];
            }
        }

        if (isset($this->settings[$name])) {
            return $this->settings[$name];
        } elseif (isset($this->allowlist[$name]['default'])) {
            return $this->allowlist[$name]['default'];
        }

        return null;
    }

    public function getAdminBMLTRootServer(): string
    {
        if ($this->has('helpline_bmlt_root_server')) {
            return $this->get('helpline_bmlt_root_server');
        } else {
            return $this->get('bmlt_root_server');
        }
    }
}
