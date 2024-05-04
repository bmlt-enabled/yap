<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
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
