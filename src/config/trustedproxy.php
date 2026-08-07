<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Set TRUSTED_PROXIES when Yap sits behind a reverse proxy, load balancer,
    | or tunnel (ngrok, Cloudflare, Apache mod_proxy, etc.). Twilio signature
    | validation compares against $request->fullUrl(), which only honors
    | X-Forwarded-* headers from trusted proxies.
    |
    | - Unset / empty: trust no proxies (direct connections; safest default).
    | - *: trust the connecting IP (typical for ngrok / single-hop LB).
    | - Comma-separated IPs/CIDRs: trust only those proxy addresses.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
