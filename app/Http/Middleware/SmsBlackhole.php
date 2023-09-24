<?php

namespace App\Http\Middleware;

use App\Constants\EventId;
use App\Services\CallService;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SmsBlackhole
{

    private SettingsService $settings;
    private CallService $callService;

    public function __construct(SettingsService $settings, CallService $callService)
    {
        $this->settings = $settings;
        $this->callService = $callService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->settings->has("sms_blackhole") &&
            strlen($this->settings->get("sms_blackhole") > 0) && $request->has("From")) {
            $sms_blackhole_items = explode(",", ($this->settings->get('sms_blackhole')));
            foreach ($sms_blackhole_items as $sms_blackhole_item) {
                if (str_starts_with($sms_blackhole_item, $request->get('From'))) {
                    $this->callService->insertCallEventRecord(EventId::SMS_BLACKHOLED);
                    return response()->view('blackhole')->header("Content-Type", "text/xml");
                }
            }
        }

        return $next($request);
    }
}
