<?php

namespace App\Services;

use App\Constants\TwilioCallStatus;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

class TwilioService extends Service
{
    protected Client $client;
    const ANONYMOUS_NUMBER = "266696687";

    public function __construct()
    {
        parent::__construct(App::make(SettingsService::class));
        try {
            $this->client = new Client(
                $this->settings->get("twilio_account_sid"),
                $this->settings->get("twilio_auth_token")
            );
        } catch (ConfigurationException $e) {
            Log::critical("Missing Twilio Credentials");
            throw $e;
        }
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function hup($callSid): void
    {
        $this->client()->calls($callSid)->update(array('status' => TwilioCallStatus::COMPLETED));
    }

    public function sendSms($message, $from, $to): void
    {
        if (isset($from) && isset($to)
            && str_replace("+", "", $from) != self::ANONYMOUS_NUMBER && $this->mobileCheck($from)) {
            $this->client()->messages->create($from, array("from" => $to, "body" => $message));
        }
    }

    public function incrementNoAnswerCount(): void
    {
        $_SESSION['no_answer_count'] = !isset($_SESSION['no_answer_count']) ? 1 : $_SESSION['no_answer_count'] + 1;
        if ($_SESSION['no_answer_count'] == $_SESSION['no_answer_max']) {
            if ($this->client()->calls($_SESSION['master_callersid'])->fetch()->status === TwilioCallStatus::INPROGRESS) {
                Log::debug("Call blasting no answer, calling voicemail.");
                $this->client()->calls($_SESSION['master_callersid'])->update(array(
                    "method" => "GET",
                    "url" => $_SESSION['voicemail_url']
                ));
            } else {
                Log::debug("Caller hung up before we could send to voicemail.");
            }
        }
    }

    private function mobileCheck($from)
    {
        if (!isset($_SESSION['is_mobile'])) {
            $is_mobile = true;
            if ($this->settings()->has('mobile_check') && json_decode($this->settings()->get('mobile_check'))) {
                $phone_number = $this->client()->lookups->v1->phoneNumbers($from)
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
