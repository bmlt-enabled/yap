<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Trusted proxies for this application.
     *
     * Defaults to trusting none. Set TRUSTED_PROXIES when Yap runs behind a
     * reverse proxy or load balancer so $request->fullUrl() matches the public
     * URL Twilio signed. Signature validation does not make trusting proxies
     * "safe" on its own — it depends on those same forwarded headers — so
     * proxy trust is opt-in via config/trustedproxy.php (TRUSTED_PROXIES env).
     *
     * @var array|string|null
     */
    protected $proxies = null;

    /**
     * Headers used to reconstruct the client-facing URL behind a proxy.
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_PREFIX |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
