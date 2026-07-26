<?php

namespace App\Http\Controllers;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Proxies Twilio-hosted media (call recordings / voicemails) so that browsers
 * and email clients never talk to api.twilio.com directly.
 *
 * Twilio now enforces HTTP Basic Auth on stored media URLs, so a raw
 * api.twilio.com recording link returns a 401 to anyone clicking it from an
 * email, SMS, or the admin UI. This endpoint authenticates to Twilio
 * server-side with the configured Account SID / Auth Token and streams the
 * audio back to the client.
 *
 * The endpoint is public (recipients of voicemail notifications are not logged
 * in) but is protected by a Laravel signed URL: the `recording` parameter is
 * HMAC-signed with the app key, so it cannot be tampered with or forged to
 * proxy an arbitrary URL. As defense-in-depth the recording host is also
 * restricted to Twilio.
 */
class MediaController extends Controller
{
    // Twilio serves stored recordings from this host. RecordingUrl values in
    // webhook callbacks look like:
    //   https://api.twilio.com/2010-04-01/Accounts/AC.../Recordings/RE...
    private const TWILIO_MEDIA_HOST = "api.twilio.com";

    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Build a signed proxy URL for a raw Twilio recording URL. The generated
     * link points at this controller and can be safely embedded in emails,
     * SMS, and the admin UI. Returns an empty string when no recording is
     * available so callers can render nothing rather than a broken link.
     */
    public static function proxyUrl(?string $recordingUrl): string
    {
        if (empty($recordingUrl)) {
            return "";
        }

        return URL::signedRoute("media", ["recording" => $recordingUrl]);
    }

    public function stream(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403, "Invalid or missing signature.");
        }

        $recordingUrl = $request->query("recording");
        if (empty($recordingUrl) || parse_url($recordingUrl, PHP_URL_HOST) !== self::TWILIO_MEDIA_HOST) {
            abort(403, "Unsupported media host.");
        }

        $response = Http::withBasicAuth(
            $this->settings->get("twilio_account_sid"),
            $this->settings->get("twilio_auth_token")
        )->get(sprintf("%s.mp3", $recordingUrl));

        if (!$response->successful()) {
            Log::error(sprintf(
                "Failed to fetch Twilio media (%d) for %s",
                $response->status(),
                $recordingUrl
            ));
            abort(502, "Unable to retrieve recording.");
        }

        return response($response->body(), 200)
            ->header("Content-Type", "audio/mpeg")
            ->header("Content-Disposition", 'inline; filename="recording.mp3"')
            ->header("Cache-Control", "private, max-age=3600");
    }
}
