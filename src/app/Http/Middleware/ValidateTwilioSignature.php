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
        $url = $request->fullUrl();
        $params = $request->all();

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
