<?php

namespace App\Simulator\Scenarios;

use App\Services\FakeTwilio\FakeMailer;
use App\Simulator\SimulationFailed;
use App\Simulator\TwilioSimulator;

/**
 * No volunteer answers, so the caller is sent to voicemail and leaves a message.
 * Twilio then delivers the recording to voicemail-complete, and yap must:
 *
 * - text the primary contact and every on-shift responder volunteer a link to
 *   the recording through the signed /media proxy (#1664), not the raw Twilio
 *   URL, which needs Twilio credentials to play;
 * - email the primary contact the same link, with the recording attached,
 *   downloaded from Twilio with HTTP Basic Auth.
 *
 * The link must play (yap streams the recording) and stop working if the
 * signature is tampered with.
 *
 * The email needs smtp_host on the instance. In fake Twilio mode it is captured
 * in fake_twilio_requests instead of sent; config.test.php sets the SMTP
 * settings whenever TWILIO_FAKE_MODE=true.
 */
class VoicemailNotificationsScenario extends BaseScenario
{
    use DrivesHelplineCalls;

    private const VOLUNTEER = '+15555550701';

    private const PRIMARY_CONTACT = '+15555550700';

    private const PRIMARY_EMAIL = 'voicemail@example.org';

    protected bool $needsSeed = true;

    protected bool $needsServiceBody = true;

    public function name(): string
    {
        return 'voicemail-notifications';
    }

    public function description(): string
    {
        return 'Call: no answer → voicemail → voicemail-complete texts and emails a signed /media link';
    }

    public function run(TwilioSimulator $simulator, ScenarioOptions $options): array
    {
        $this->requireOptions($options);
        ScenarioSeeder::helpline($this->serviceBodyId($options), [
            'primary_contact' => self::PRIMARY_CONTACT,
            'primary_contact_email' => self::PRIMARY_EMAIL,
        ], [
            ScenarioSeeder::volunteer('Voicemail Responder', self::VOLUNTEER, responder: true),
        ]);

        $mark = $simulator->lastOutboundId();
        // sms_dialback_options=2: voicemail notifications carry a dialback PIN.
        $caller = $this->callHelpline($simulator, $options, ['override_sms_dialback_options' => 2]);
        $leg = $this->volunteerLegs($simulator, $mark)[0];
        $this->followRedirect($caller, $this->exhaustVolunteers($simulator, $leg, $mark))
            ->expectHeard('please leave a message after the tone');

        $recordingUrl = $caller->record()->recordingUrl();
        $afterMessage = $mark;

        $link = $this->expectSignedLink($this->onlyText($simulator, $afterMessage, self::PRIMARY_CONTACT, 'voicemail notification')['data']['Body'], $recordingUrl, 'the primary contact SMS');
        $volunteerLink = $this->expectSignedLink($this->onlyText($simulator, $afterMessage, self::VOLUNTEER, 'voicemail notification to the responder')['data']['Body'], $recordingUrl, 'the responder SMS');
        if ($volunteerLink !== $link) {
            throw new SimulationFailed("Expected the responder to get the same link as the primary contact:\n  {$link}\n  {$volunteerLink}");
        }

        $email = $this->expectEmail($simulator, $afterMessage);
        self::expectContains($email['body'], htmlspecialchars($link, ENT_QUOTES), 'the voicemail email');
        $download = $this->expectAuthenticatedDownload($simulator, $afterMessage, $recordingUrl);

        $played = $simulator->fetch($link);
        if ($played->status !== 200 || $played->body === '') {
            throw new SimulationFailed("Expected the signed /media link to play the recording, got HTTP {$played->status}:\n{$played->body}");
        }
        $tampered = $simulator->fetch(substr($link, 0, -1) . (str_ends_with($link, '0') ? '1' : '0'));
        if ($tampered->status !== 403) {
            throw new SimulationFailed("Expected a /media link with a tampered signature to be refused with 403, got HTTP {$tampered->status}.");
        }

        return [
            'no answer → voicemail → recording delivered to voicemail-complete',
            'primary contact and responder texted the signed /media link (not the raw Twilio URL) with a dialback PIN',
            sprintf('voicemail emailed to %s with the same link and the recording attached (%d bytes)', self::PRIMARY_EMAIL, $email['attachments'][0]['bytes']),
            "recording downloaded with Basic Auth as {$download['username']}",
            '/media link plays; a tampered signature is refused (403)',
        ];
    }

    /** The /media link in a notification, checked to point at $recordingUrl and to be signed. */
    private function expectSignedLink(string $body, string $recordingUrl, string $what): string
    {
        if (str_contains($body, $recordingUrl . ' ') || str_contains($body, $recordingUrl . '.mp3')) {
            throw new SimulationFailed("Expected {$what} to link to the /media proxy, not the raw Twilio recording URL:\n  {$body}");
        }
        if (!preg_match('#(https?://\S+?/media\?\S+?)\.?(?:\s|$)#', $body, $m)) {
            throw new SimulationFailed("Expected {$what} to contain a /media link:\n  {$body}");
        }

        $link = $m[1];
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        if (($query['recording'] ?? null) !== $recordingUrl || empty($query['signature'])) {
            throw new SimulationFailed("Expected {$what} to carry a signed /media link for {$recordingUrl}, got {$link}");
        }
        self::expectContains($body, 'PIN:', $what);

        return $link;
    }

    /** @return array<string, mixed> the captured email */
    private function expectEmail(TwilioSimulator $simulator, int $mark): array
    {
        $emails = $simulator->outbound(FakeMailer::RESOURCE, $mark);
        if (count($emails) !== 1) {
            throw new SimulationFailed(sprintf(
                'Expected voicemail-complete to send one email, but it sent %d. The instance needs smtp_host (and the other '
                . 'smtp_* settings) configured; in fake Twilio mode the email is captured instead of sent.',
                count($emails),
            ));
        }

        $email = $emails[0]['data'];
        if (!in_array(self::PRIMARY_EMAIL, $email['to'] ?? [], true)) {
            throw new SimulationFailed('Expected the voicemail email to go to ' . self::PRIMARY_EMAIL . ', it went to ' . implode(', ', $email['to'] ?? []));
        }
        if (count($email['attachments'] ?? []) !== 1 || ($email['attachments'][0]['bytes'] ?? 0) === 0) {
            throw new SimulationFailed('Expected the voicemail email to attach the recording, attachments: ' . json_encode($email['attachments'] ?? []));
        }

        return $email;
    }

    /** @return array<string, mixed> how yap authenticated the attachment download */
    private function expectAuthenticatedDownload(TwilioSimulator $simulator, int $mark, string $recordingUrl): array
    {
        $downloads = array_values(array_filter(
            $simulator->outbound('recordings.media', $mark),
            fn (array $request) => $request['url'] === $recordingUrl . '.mp3',
        ));
        $download = $downloads[0]['data'] ?? [];
        if (count($downloads) !== 1 || ($download['auth'] ?? null) !== 'basic' || !($download['credentials_match'] ?? false)) {
            throw new SimulationFailed(sprintf(
                'Expected the recording to be downloaded once from %s.mp3 with Basic Auth using the account SID and auth token, got: %s',
                $recordingUrl,
                json_encode(array_column($downloads, 'data')),
            ));
        }

        return $download;
    }
}
