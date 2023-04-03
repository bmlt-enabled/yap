<?php

namespace App\Services;

use App\Constants\EventId;
use App\Repositories\ReportsRepository;

class CallService
{
    protected SettingsService $settings;
    protected ReportsRepository $reports;

    public function __construct(
        SettingsService $settings,
        ReportsRepository $reports)
    {
        $this->settings = $settings;
        $this->reports = $reports;
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
                    if (str_exists($response, $expected_like)) {
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

    public function insertCallEventRecord($eventid, $meta = null)
    {
        $this->reports->insertCallEventRecord($eventid, $meta);
    }

    public function insertCallRecord($callRecord)
    {
        $this->reports->insertCallRecord($callRecord);
    }

    public function getConferenceName($service_body_id)
    {
        return $service_body_id . "_" . rand(1000000, 9999999) . "_" . time();
    }

    public function isDialbackPinValid($pin)
    {
        return $this->reports->isDialbackPinValid($pin);
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
