<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SessionKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Restore session from previous yap session key.
        if ($request->has("ysk")) {
            session_id($request->get("ysk"));
        }

        return $next($request);
    }
}
