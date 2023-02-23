<?php

namespace App\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use SmsDialbackOptions;

class VoicemailService
{
    protected PHPMailer $mailer;

    public function __construct(PHPMailer $mailer = null)
    {
        if ($mailer == null) {
            $mailer = new PHPMailer(true);
        }

        $this->mailer = $mailer;
    }

    public function sendSmsForVoicemail($callsid, $recordingUrl, $recipients, $serviceBodyCallHandling, $serviceBodyName, $callerNumber)
    {
        $caller_id = getOutboundDialingCallerId($serviceBodyCallHandling);
        $dialbackString = getDialbackString($callsid, $caller_id, SmsDialbackOptions::VOICEMAIL_NOTIFICATION);

        foreach ($recipients as $recipient) {
            $GLOBALS['twilioClient']->messages->create(
                $recipient,
                array(
                    "from" => $caller_id,
                    "body" => sprintf("You have a message from the %s helpline from caller %s. Voicemail Link %s.mp3. %s", $serviceBodyName, $callerNumber, $recordingUrl, $dialbackString)
                )
            );
        }
    }

    public function sendEmailForVoicemail($recordingUrl, $recipients, $serviceBodyName, $callerNumber)
    {
        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = setting('smtp_host');
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = setting('smtp_username');
            $this->mailer->Password = setting('smtp_password');
            $this->mailer->SMTPSecure = setting('smtp_secure');
            if (has_setting('smtp_alt_port')) {
                $this->mailer->Port = setting('smtp_alt_port');
            } elseif (setting('smtp_secure') == 'tls') {
                $this->mailer->Port = 587;
            } elseif (setting('smtp_secure') == 'ssl') {
                $this->mailer->Port = 465;
            }
            $this->mailer->setFrom(setting('smtp_from_address'), setting('smtp_from_name'));
            $this->mailer->isHTML(true);
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress($recipient);
            }
            $this->mailer->addStringAttachment(file_get_contents($recordingUrl . ".mp3"), $recordingUrl . ".mp3");
            $this->mailer->Body = "You have a message from the " . $serviceBodyName . " helpline from caller " . $callerNumber . ", " . $recordingUrl. ".mp3";
            $this->mailer->Subject = 'Helpline Voicemail from ' . $serviceBodyName;
            $this->mailer->send();
        } catch (Exception $e) {
            log_debug('Message could not be sent. Mailer Error: ' . $this->mailer->ErrorInfo);
        }
    }
}
