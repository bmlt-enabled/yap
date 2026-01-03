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
     * Validates that the request is actually from Twilio by checking
     * the X-Twilio-Signature header against the request body.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $authToken = $this->settings->get('twilio_auth_token');

        // If no auth token configured, skip validation (for development)
        if (empty($authToken)) {
            Log::warning('Twilio signature validation skipped: no auth token configured');
            return $next($request);
        }

        $validator = new RequestValidator($authToken);
        $signature = $request->header('X-Twilio-Signature', '');

        // Build the URL that Twilio used to sign the request
        // When behind a proxy (ngrok, load balancer), we need to use the original URL
        $url = $this->getSignatureUrl($request);

        // For POST requests, Twilio signs using POST body params only (not query params)
        // $request->all() merges both, so we need to use post() for POST requests
        $params = $request->isMethod('POST') ? $request->post() : [];

        Log::debug('Twilio signature validation attempt', [
            'url' => $url,
            'method' => $request->method(),
            'params_count' => count($params),
            'has_signature' => !empty($signature),
        ]);

        if (!$validator->validate($signature, $url, $params)) {
            Log::warning('Invalid Twilio signature rejected', [
                'url' => $url,
                'ip' => $request->ip(),
            ]);
            return response('Forbidden', 403);
        }

        return $next($request);
    }

    /**
     * Get the URL that Twilio used to sign the request.
     *
     * When behind a reverse proxy (ngrok, load balancer, etc.), the URL
     * Laravel sees may differ from what Twilio sent the request to.
     * We reconstruct the original URL from forwarded headers.
     *
     * @param Request $request
     * @return string
     */
    protected function getSignatureUrl(Request $request): string
    {
        // Check for X-Original-Host (set by some proxies) or X-Forwarded-Host
        $host = $request->header('X-Original-Host')
            ?? $request->header('X-Forwarded-Host')
            ?? $request->getHost();

        // Check for X-Forwarded-Proto for the scheme
        $scheme = $request->header('X-Forwarded-Proto') ?? $request->getScheme();

        // Use the raw REQUEST_URI to preserve exact encoding as Twilio sent it
        // Laravel's getRequestUri() may re-encode the query string differently
        $requestUri = $_SERVER['REQUEST_URI'] ?? $request->getRequestUri();

        $url = $scheme . '://' . $host . $requestUri;

        Log::debug('Twilio signature validation URL constructed', [
            'constructed_url' => $url,
            'scheme' => $scheme,
            'host' => $host,
            'request_uri' => $requestUri,
            'x_forwarded_host' => $request->header('X-Forwarded-Host'),
            'x_forwarded_proto' => $request->header('X-Forwarded-Proto'),
        ]);

        return $url;
    }
}
