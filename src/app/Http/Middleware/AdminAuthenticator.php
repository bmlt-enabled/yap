<?php

namespace App\Http\Middleware;

use App\Services\AuthenticationService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminAuthenticator
{
    private AuthenticationService $authn;

    public function __construct(AuthenticationService $authn)
    {
        $this->authn = $authn;
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
        if (!$this->authn->verify()) {
            return to_route("adminLogin");
        }

        return $next($request);
    }
}
