<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\Request;

class AdminAuthenticator
{
    private AuthenticationService $authn;

    public function __construct(AuthenticationService $authn) {
        $this->authn = $authn;
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
        if (!$this->authn->verify()) {
            return to_route("adminLogin");
        };

        return $next($request);
    }
}
