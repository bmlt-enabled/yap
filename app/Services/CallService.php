<?php

namespace App\Services;

use App\Constants\SearchType;
use App\Constants\SpecialPhoneNumber;
use App\Models\RecordType;
use App\Repositories\ReportsRepository;
use App\Repositories\VoicemailRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class CallService extends Service
{
    protected ReportsRepository $reports;
    protected VoicemailRepository $voicemail;
    protected TwilioService $twilio;
    protected Request $request;

    public function __construct(
        ReportsRepository $reports,
        TwilioService $twilio,
        VoicemailRepository $voicemail,
        Request $request
    ) {
        parent::__construct(App::make(SettingsService::class));
        $this->reports = $reports;
        $this->twilio = $twilio;
        $this->voicemail = $voicemail;
        $this->request = $request;
    }

    public function getVoicemail()
    {
        return $this->voicemail;
    }

    public function getIvrResponse($request, $expected_exacts = array(), $expected_likes = array(), $field = 'Digits')
    {
        $response = "0";

        if ($request->has($field)) {
            $response = $request->get($field);
        } elseif ($request->has('SpeechResult')) {
            $response = intval($request->get('SpeechResult'));
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

    public function insertCallEventRecord($eventId, $meta = null): void
    {
        if (request()->has('CallSid')) {
            $callSid = request()->get('CallSid');
            $type = RecordType::PHONE;
        } elseif (request()->has('SmsSid')) {
            $callSid = request()->get('SmsSid');
            $type = RecordType::SMS;
        } else {
            return;
        }

        $metaAsJson = isset($meta) ? json_encode($meta) : null;
        $serviceBodyId = $this->settings->get('service_body_id');
        date_default_timezone_set('UTC');
        $this->reports->insertCallEventRecord($callSid, $eventId, $serviceBodyId, $metaAsJson, $type);
    }

    public function insertCallRecord($callRecord): void
    {
        date_default_timezone_set('UTC');
        $this->reports->insertCallRecord($callRecord);
    }

    public function insertAlert($alertId, $payload): void
    {
        date_default_timezone_set('UTC');
        $this->reports->insertAlert($alertId, $payload);
    }

    public function setConferenceParticipant($friendlyname, $callsid, $role): void
    {
        $conferences = $this->twilio->client()->conferences->read(array ("friendlyName" => $friendlyname ));
        $conferencesid = $conferences[0]->sid;
        $this->reports->setConferenceParticipant($friendlyname, $conferencesid, $callsid, $role);
    }

    public function getConferencePartipant($callsid): array
    {
        return $this->reports->getConferenceParticipant($callsid);
    }

    public function getOutboundDialingCallerId($serviceBodyCallHandling)
    {
        if ($serviceBodyCallHandling->forced_caller_id_enabled) {
            return $serviceBodyCallHandling->forced_caller_id_number;
        } else if (request()->has("Caller")) {
            return request()->get("Caller");
        } else if (request()->has("caller_id")) {
            return request()->get("caller_id");
        } else {
            return SpecialPhoneNumber::UNKNOWN;
        }
    }

    public function getDialbackString($callsid, $dialbackNumber, $option)
    {
        $dialback_string = "";
        # Bitwise detection
        if ($this->settings->get('sms_dialback_options') & $option) {
            $pin_lookup = $this->reports->lookupPinForCallSid($callsid);
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
            $digit = intval($request->get('SpeechResult'));
        } elseif ($request->has($field)) {
            $digit = intval($request->get($field));
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
        } elseif (request()->has('ToState') && strlen(request()->get('ToState')) > 0) {
            return request()->get('ToState'); // Retrieved from Twilio metadata
        } elseif ($this->settings->has('toll_free_province_bias')) {
            return $this->settings->get('toll_free_province_bias'); // Override for Tollfree
        } else {
            return "";
        }
    }
}
