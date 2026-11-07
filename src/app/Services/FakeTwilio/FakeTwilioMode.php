<?php

namespace App\Services\FakeTwilio;

use App\Http\Controllers\MediaController;
use App\Services\MeditationSource;
use App\Services\SettingsService;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Http;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Http\Message\RequestInterface;

/**
 * Everything besides the Twilio REST client that fake Twilio mode replaces, so a
 * simulated call has no side effects outside the box:
 *
 * - Twilio recording downloads (the voicemail email attachment) are answered
 *   locally and recorded as "recordings.media", with how they authenticated.
 * - Outbound email is recorded as "email.send" instead of sent (FakeMailer).
 * - The JFT and SPAD come from the recorded pages in the repository instead of
 *   na.org, when those pages are present (RecordedMeditationSource).
 *
 * AppServiceProvider installs this when FakeTwilioStore::enabled() at boot.
 */
class FakeTwilioMode
{
    private const TWILIO_MEDIA_HOST = 'api.twilio.com';

    public static function install(Application $app): void
    {
        $app->bind(PHPMailer::class, FakeMailer::class);

        if (RecordedMeditationSource::available()) {
            $app->singleton(MeditationSource::class, fn () => new RecordedMeditationSource());
        }

        Http::globalMiddleware(self::recordingDownloads(...));
    }

    /**
     * Guzzle middleware that answers GETs for Twilio-hosted recordings with
     * placeholder audio. Everything else goes through untouched.
     */
    private static function recordingDownloads(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $uri = $request->getUri();
            if ($uri->getHost() !== self::TWILIO_MEDIA_HOST || !str_contains($uri->getPath(), '/Recordings/')) {
                return $handler($request, $options);
            }

            (new FakeTwilioStore())->recordRequest(
                $request->getMethod(),
                (string) $uri,
                'recordings.media',
                [],
                self::authentication($request),
                200,
                null,
            );

            return Create::promiseFor(new Response(200, ['Content-Type' => 'audio/mpeg'], MediaController::FAKE_RECORDING_BODY));
        };
    }

    /**
     * How the download authenticated, without storing the auth token: Twilio
     * only serves recordings to Basic Auth with the account SID and token.
     *
     * @return array{auth: string, username: ?string, credentials_match: bool}
     */
    private static function authentication(RequestInterface $request): array
    {
        $header = $request->getHeaderLine('Authorization');
        if (!str_starts_with($header, 'Basic ')) {
            return ['auth' => $header === '' ? 'none' : strtok($header, ' '), 'username' => null, 'credentials_match' => false];
        }

        [$username, $password] = array_pad(explode(':', (string) base64_decode(substr($header, 6)), 2), 2, '');
        $settings = app(SettingsService::class);

        return [
            'auth' => 'basic',
            'username' => $username,
            'credentials_match' => $username === (string) $settings->get('twilio_account_sid')
                && hash_equals((string) $settings->get('twilio_auth_token'), $password),
        ];
    }
}
