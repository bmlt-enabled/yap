<?php

namespace App\Services;

use App\Constants\SmsDialbackOptions;
use App\Constants\SmtpPorts;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;

class VoicemailService extends Service
{
    protected PHPMailer $mailer;
    protected TwilioService $twilio;
    protected CallService $call;

    public function __construct(PHPMailer $mailer = null, TwilioService $twilio, CallService $call)
    {
        if ($mailer == null) {
            $mailer = new PHPMailer(true);
        }

        parent::__construct(App::make(SettingsService::class));

        $this->mailer = $mailer;
        $this->twilio = $twilio;
        $this->call = $call;
    }

    public function sendSmsForVoicemail($callsid, $recordingUrl, $recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber): void
    {
        $caller_id = $this->call->getOutboundDialingCallerId($serviceBodyCallHandling);
        $dialbackString = $this->call->getDialbackString($callsid, $caller_id, SmsDialbackOptions::VOICEMAIL_NOTIFICATION);
        $body = sprintf("You have a message from the %s helpline from caller %s. Voicemail Link %s.mp3. %s", $serviceBodyName, $callerNumber, $recordingUrl, $dialbackString);
        Log::debug("SMS Body: " . $body);

        foreach ($recipients as $recipient) {
            $this->twilio->client()->messages->create(
                $recipient,
                array(
                    "from" => $caller_id,
                    "body" => $body
                )
            );
        }
    }

    public function sendEmailForVoicemail($callsid, $recordingUrl, $recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber): void
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->settings->get('smtp_host');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->settings->get('smtp_username');
            $this->mailer->Password = $this->settings->get('smtp_password');
            $this->mailer->SMTPSecure = $this->settings->get('smtp_secure');
            if ($this->settings->has('smtp_alt_port')) {
                $this->mailer->Port = $this->settings->get('smtp_alt_port');
            } elseif ($this->settings->get('smtp_secure') == 'tls') {
                $this->mailer->Port = SmtpPorts::TLS;
            } elseif ($this->settings->get('smtp_secure') == 'ssl') {
                $this->mailer->Port = SmtpPorts::SSL;
            }
            $this->mailer->setFrom($this->settings->get('smtp_from_address'), $this->settings->get('smtp_from_name'));
            $this->mailer->isHTML(true);
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }
            $recordingUrlWithExtension = sprintf("%s.mp3", $recordingUrl);
            $recordingDataString = Http::get($recordingUrlWithExtension);
            $this->mailer->addStringAttachment($recordingDataString, $recordingUrlWithExtension);
            $body = "You have a message from the " . $serviceBodyName . " helpline from caller " . $callerNumber . ", " . $recordingUrl. ".mp3  ";
            $caller_id = $this->call->getOutboundDialingCallerId($serviceBodyCallHandling);
            $dialbackString = $this->call->getDialbackString($callsid, $caller_id, SmsDialbackOptions::VOICEMAIL_NOTIFICATION);
            $body .= $dialbackString;
            $this->mailer->Body = $body;
            $this->mailer->Subject = 'Helpline Voicemail from ' . $serviceBodyName;
            $this->mailer->send();
        } catch (Exception $e) {
            Log::critical('Message could not be sent. Mailer Error: ' . $e);
        }
    }
}
