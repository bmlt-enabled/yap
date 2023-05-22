<?php

namespace App\Services;

use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

class TwilioService
{
    protected Client $client;
    protected SettingsService $settings;
    const ANONYMOUS_NUMBER = "266696687";

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
        try {
            $client = new Client(
                $this->settings->get("twilio_account_sid"),
                $this->settings->get("twilio_auth_token")
            );
            $this->setup($client);
        } catch (ConfigurationException $e) {
            error_log("Missing Twilio Credentials");
            throw $e;
        }
    }

    public function setup(Client $client)
    {
        $this->client = $client;
    }

    public function client()
    {
        return $this->client;
    }

    public function hup($callSid): void
    {
        $this->client->calls($callSid)->update(array('status' => 'completed'));
    }

    public function sendSms($message)
    {

        if (isset($_REQUEST['From']) && isset($_REQUEST['To'])
            && str_replace("+", "", $_REQUEST["From"]) != self::ANONYMOUS_NUMBER && $this->mobileCheck()) {
            $this->client->messages->create($_REQUEST['From'], array("from" => $_REQUEST['To'], "body" => $message));
        }
    }

    public function incrementNoAnswerCount()
    {
        $_SESSION['no_answer_count'] = !isset($_SESSION['no_answer_count']) ? 1 : $_SESSION['no_answer_count'] + 1;
        if ($_SESSION['no_answer_count'] == $_SESSION['no_answer_max']) {
            $this->settings->logDebug("Call blasting no answer, calling voicemail.");
            $this->client->calls($_SESSION['master_callersid'])->update(array(
                "method" => "GET",
                "url" => $_SESSION['voicemail_url']
            ));
        }
    }

    private function mobileCheck()
    {
        if (!isset($_SESSION['is_mobile'])) {
            $is_mobile = true;
            if ($this->settings->has('mobile_check') && json_decode($this->settings->get('mobile_check'))) {
                $phone_number = $this->client->lookups->v1->phoneNumbers($_REQUEST['From'])
                    ->fetch(array("type" => "carrier"));
                if ($phone_number->carrier['type'] !== 'mobile') {
                    $is_mobile = false;
                }
            }
            $_SESSION['is_mobile'] = $is_mobile;
        }

        return $_SESSION['is_mobile'];
    }
}
