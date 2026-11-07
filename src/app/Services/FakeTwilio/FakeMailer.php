<?php

namespace App\Services\FakeTwilio;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * A PHPMailer that records the message instead of delivering it.
 *
 * send() still runs preSend(), so addresses are validated and the MIME message
 * (attachments included) is built exactly as for real. Only postSend(), the
 * hop to the SMTP server, is replaced: the message is stored in
 * fake_twilio_requests as resource "email.send", next to the Twilio requests the
 * same voicemail produced, where the simulator reads it back.
 */
class FakeMailer extends PHPMailer
{
    public const RESOURCE = 'email.send';

    public function postSend()
    {
        $attachments = [];
        foreach ($this->getAttachments() as $attachment) {
            // [0] content or path, [1] filename, [2] name, ... [5] is a string attachment
            $attachments[] = [
                'name' => $attachment[2],
                'bytes' => $attachment[5] ? strlen($attachment[0]) : (int) @filesize($attachment[0]),
            ];
        }

        (new FakeTwilioStore())->recordRequest('SMTP', "smtp://{$this->Host}", self::RESOURCE, [], [
            'from' => $this->From,
            'to' => array_column($this->getToAddresses(), 0),
            'subject' => $this->Subject,
            'body' => $this->Body,
            'alt_body' => $this->AltBody,
            'attachments' => $attachments,
        ], 250, null);

        return true;
    }
}
