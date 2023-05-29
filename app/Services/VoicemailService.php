<?php

namespace App\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Constants\SmsDialbackOptions;

class VoicemailService
{
    protected PHPMailer $mailer;
    protected TwilioService $twilio;
    protected CallService $call;
    protected SettingsService $settings;

    public function __construct(
        PHPMailer $mailer = null,
        TwilioService $twilio,
        CallService $call,
        SettingsService $settings)
    {
        if ($mailer == null) {
            $mailer = new PHPMailer(true);
        }

        $this->mailer = $mailer;
        $this->twilio = $twilio;
        $this->call = $call;
        $this->settings = $settings;
    }

    public function sendSmsForVoicemail($callsid, $recordingUrl, $recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber): void
    {
        $caller_id = $this->call->getOutboundDialingCallerId($serviceBodyCallHandling);
        $dialbackString = $this->call->getDialbackString($callsid, $caller_id, SmsDialbackOptions::VOICEMAIL_NOTIFICATION);

        foreach ($recipients as $recipient) {
            $this->twilio->client()->messages->create(
                $recipient,
                array(
                    "from" => $caller_id,
                    "body" => sprintf("You have a message from the %s helpline from caller %s. Voicemail Link %s.mp3. %s", $serviceBodyName, $callerNumber, $recordingUrl, $dialbackString)
                )
            );
        }
    }

    public function sendEmailForVoicemail($recordingUrl, $recipients, $serviceBodyName, $callerNumber): void
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
                $this->mailer->Port = 587;
            } elseif ($this->settings->get('smtp_secure') == 'ssl') {
                $this->mailer->Port = 465;
            }
            $this->mailer->setFrom($this->settings->get('smtp_from_address'), $this->settings->get('smtp_from_name'));
            $this->mailer->isHTML(true);
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }
            $this->mailer->addStringAttachment(file_get_contents($recordingUrl . ".mp3"), $recordingUrl . ".mp3");
            $this->mailer->Body = "You have a message from the " . $serviceBodyName . " helpline from caller " . $callerNumber . ", " . $recordingUrl. ".mp3";
            $this->mailer->Subject = 'Helpline Voicemail from ' . $serviceBodyName;
            $this->mailer->send();
        } catch (Exception $e) {
            $this->settings->logDebug('Message could not be sent. Mailer Error: ' . $this->mailer->ErrorInfo);
        }
    }
}
