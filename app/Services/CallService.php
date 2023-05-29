<?php

namespace App\Services;

use App\Constants\EventId;
use App\Constants\SearchType;
use App\Constants\SpecialPhoneNumber;
use App\Repositories\ReportsRepository;

class CallService
{
    protected SettingsService $settings;
    protected ReportsRepository $reports;
    protected TwilioService $twilio;

    public function __construct(
        SettingsService $settings,
        ReportsRepository $reports,
        TwilioService $twilio,
    ) {
        $this->settings = $settings;
        $this->reports = $reports;
        $this->twilio = $twilio;
    }

    public function checkSMSBlackhole()
    {
        if ($this->settings->has('sms_blackhole') && strlen($this->settings->get('sms_blackhole')) > 0
            && isset($_REQUEST['From'])) {
            $sms_blackhole_items = explode(",", ($this->settings->get('sms_blackhole')));
            foreach ($sms_blackhole_items as $sms_blackhole_item) {
                if (str_starts_with($sms_blackhole_item, $_REQUEST['From'])) {
                    insertCallEventRecord(EventId::SMS_BLACKHOLED);
                    return;
                }
            }
        }
    }

    public function getIvrResponse($request, $expected_exacts = array(), $expected_likes = array(), $field = 'Digits')
    {
        $response = "0";

        if ($request->has($field)) {
            $response = $request->query($field);
        } elseif ($request->has('SpeechResult')) {
            $response = intval($request->query('SpeechResult'));
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
                    if (str_contains($response, $expected_like)) {
                        $found_at_least_once = true;
                    }
                }
            }

            if (!$found_at_least_once) {
                return null;
            }
        }

        return $response;
    }

    public function insertCallEventRecord($eventid, $meta = null): void
    {
        $this->reports->insertCallEventRecord($eventid, $meta);
    }

    public function insertCallRecord($callRecord): void
    {
        $this->reports->insertCallRecord($callRecord);
    }

    public function getConferenceName($service_body_id): string
    {
        return $service_body_id . "_" . rand(1000000, 9999999) . "_" . time();
    }

    public function setConferenceParticipant($friendlyname, $callsid, $role): void
    {
        $conferences = $this->twilio->client()->conferences->read(array ("friendlyName" => $friendlyname ));
        $conferencesid = $conferences[0]->sid;
        $this->reports->setConferenceParticipant($friendlyname, $conferencesid, $callsid, $role);
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

    function getDialbackString($callsid, $dialbackNumber, $option)
    {
        $dialback_string = "";
        # Bitwise detection
        if ($this->settings->get('sms_dialback_options') & $option) {
            $pin_lookup = lookupPinForCallSid($callsid);
            if (count($pin_lookup) > 0) {
                $dialback_digit_map_digit = $this->getOptionForSearchType(SearchType::DIALBACK);
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

    private function getOptionForSearchType($searchType)
    {
        foreach ($this->settings->get("digit_map_search_type") as $digit => $value) {
            if ($value == $searchType) {
                return $digit;
            }
        }
        return 0;
    }

    public function isDialbackPinValid($pin): array
    {
        return $this->reports->isDialbackPinValid($pin);
    }

    public function getDigitResponse($request, $setting, $field = 'SearchType')
    {
        $digitMap = $this->settings->getDigitMap($setting);
        if ($field === 'Digits'
            && $this->settings->has('speech_gathering')
            && json_encode($this->settings->get('speech_gathering'))
            && $request->has('SpeechResult')) {
            $digit = intval($request->query('SpeechResult'));
        } elseif ($request->has($field)) {
            $digit = intval($request->query($field));
        } else {
            return null;
        }

        if (array_key_exists($digit, $digitMap)) {
            return $digitMap[$digit];
        } else {
            return null;
        }
    }

    public function getProvince()
    {
        if ($this->settings->has('sms_bias_bypass') && json_decode($this->settings->get('sms_bias_bypass'))) {
            return "";
        } elseif ($this->settings->has('toll_province_bias')) {
            return $this->settings->get('toll_province_bias');
        } elseif (isset($_REQUEST['ToState']) && strlen($_REQUEST['ToState']) > 0) {
            return $_REQUEST['ToState']; // Retrieved from Twilio metadata
        } elseif ($this->settings->has('toll_free_province_bias')) {
            return $this->settings->get('toll_free_province_bias'); // Override for Tollfree
        } else {
            return "";
        }
    }
}
