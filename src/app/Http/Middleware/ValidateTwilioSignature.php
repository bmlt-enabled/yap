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

        // Validate against the URL the framework resolved (honoring the trusted
        // proxy configuration in TrustProxies), never raw client-supplied
        // forwarding headers.
        $url = $request->fullUrl();

        // Twilio signs POST requests using the POST body params only; for GET the
        // query string is already part of the URL.
        $params = $request->isMethod('POST') ? $request->post() : [];

        if (!$validator->validate($signature, $url, $params)) {
            Log::warning('Invalid Twilio signature rejected', [
                'url' => $url,
                'ip' => $request->ip(),
            ]);
            return response('Forbidden', 403);
        }

        return $next($request);
    }
}
