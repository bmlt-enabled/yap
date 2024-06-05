<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CallBlocklist
{

    private SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }


    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->settings->has("blocklist") &&
            strlen($this->settings->get("blocklist") > 0) &&
            $request->has("Caller")) {
            $blocklistItems = explode(",", $this->settings->get('blocklist'));
            foreach ($blocklistItems as $blocklistItem) {
                if (str_starts_with($blocklistItem, $request->get('Caller'))) {
                    return response()->view('rejectCall')->header("Content-Type", "text/xml; charset=utf-8");
                }
            }
        }

        return $next($request);
    }
}
