<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Security\RequestValidator;

class ValidateTwilioSignature
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Handle an incoming request.
     *
     * Verifies that the request actually originated from Twilio by validating
     * the X-Twilio-Signature header against the request URL and body. Fails
     * closed: if no auth token is configured, or the signature is missing or
     * invalid, the request is rejected with HTTP 403.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Development-only bypass. Off by default, and only ever honored outside
        // of production, so a misconfigured production deployment can never skip
        // validation. Used by the test suite (see phpunit.xml).
        $bypass = !app()->environment('production')
            && config('twilio.disable_signature_validation', false);

        if ($bypass) {
            Log::warning('Twilio signature validation bypassed (non-production dev flag enabled)');
            return $next($request);
        }

        $authToken = $this->settings->get('twilio_auth_token');

        // Fail closed: with no auth token we cannot verify the signature, so the
        // request is rejected rather than allowed through.
        if (empty($authToken)) {
            Log::warning('Twilio signature validation failed: no auth token configured', [
                'ip' => $request->ip(),
            ]);
            return response('Forbidden', 403);
        }

        $validator = new RequestValidator($authToken);
        $signature = $request->header('X-Twilio-Signature', '');

        // Twilio signs POST requests using the POST body params only; for GET the
        // query string is already part of the URL.
        $params = $request->isMethod('POST') ? $request->post() : [];

        $urls = $this->candidateUrls($request);

        foreach ($urls as $url) {
            if ($validator->validate($signature, $url, $params)) {
                return $next($request);
            }
        }

        Log::warning('Invalid Twilio signature rejected', [
            'method' => $request->method(),
            'urls' => $urls,
            'has_signature' => $signature !== '',
            'ip' => $request->ip(),
        ]);
        return response('Forbidden', 403);
    }

    /**
     * The URLs to validate the signature against, most faithful first.
     *
     * Twilio computes its signature over the exact URL it requested, so the
     * query string has to be compared byte for byte. $request->fullUrl() cannot
     * do that: it rebuilds the query string through Symfony's
     * normalizeQueryString(), which sorts the parameters alphabetically and
     * re-encodes them as RFC 3986 (a space becomes %20 rather than +). Every
     * webhook URL that carries a query string — each Gather action and status
     * callback in the IVR — therefore hashed differently than Twilio signed it
     * and was rejected with a 403 (#1573).
     *
     * getRequestUri() is the raw, unmodified request target, so pairing it with
     * the resolved scheme and host (honoring TrustProxies, never raw
     * client-supplied forwarding headers) reproduces the signed URL exactly.
     *
     * @param Request $request
     * @return string[]
     */
    protected function candidateUrls(Request $request): array
    {
        $urls = [$request->getSchemeAndHttpHost() . $request->getRequestUri()];

        // Kept as a fallback for deployments where a rewrite leaves REQUEST_URI
        // as something other than the URL Twilio called, and for the trailing
        // slash fullUrl() strips. Trying a second server-derived URL does not
        // weaken the check: a forged request still has to produce a valid HMAC
        // over one of them, which is impossible without the auth token.
        $fullUrl = $request->fullUrl();
        if (!in_array($fullUrl, $urls, true)) {
            $urls[] = $fullUrl;
        }

        return $urls;
    }
}
