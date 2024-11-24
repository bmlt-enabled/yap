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
        // Increment or initialize 'no_answer_count' in the session
        $noAnswerCount = session()->get('no_answer_count', 0) + 1;
        session()->put('no_answer_count', $noAnswerCount);

        // Check if 'no_answer_count' has reached 'no_answer_max'
        if ($noAnswerCount == session()->get('no_answer_max')) {
            $masterCallSid = session()->get('master_callersid');
            $voicemailUrl = session()->get('voicemail_url');

            if ($this->client()->calls($masterCallSid)->fetch()->status === TwilioCallStatus::INPROGRESS) {
                Log::debug("Call blasting no answer, calling voicemail.");
                $this->client()->calls($masterCallSid)->update([
                    "method" => "GET",
                    "url" => $voicemailUrl,
                ]);
            } else {
                Log::debug("Caller hung up before we could send to voicemail.");
            }
        }
    }

    private function mobileCheck($from)
    {
        if (!session()->has('is_mobile')) {
            $isMobile = true;

            if ($this->settings()->has('mobile_check') && json_decode($this->settings()->get('mobile_check'))) {
                $phoneNumber = $this->client()->lookups->v1->phoneNumbers($from)
                    ->fetch(['type' => 'carrier']);

                if ($phoneNumber->carrier['type'] !== 'mobile') {
                    $isMobile = false;
                }
            }

            session()->put('is_mobile', $isMobile);
        }

        return session()->get('is_mobile');
    }
}
