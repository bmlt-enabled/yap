<?php

namespace Tests\Support;

use Illuminate\Http\Request;
use Twilio\Security\RequestValidator;

/**
 * Build the URL and signature Twilio would send for a webhook request, mirroring
 * ValidateTwilioSignature (getSchemeAndHttpHost + getRequestUri + POST body params).
 */
class TwilioSignatureTestHelper
{
    public const AUTH_TOKEN = 'testtoken';

    public const TRUSTED_PROXY_HEADERS =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_PREFIX |
        Request::HEADER_X_FORWARDED_AWS_ELB;

    /**
     * Reconstruct the URL the middleware will validate against.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, string>  $server
     */
    public static function validationUrl(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        ?string $trustedProxies = null,
    ): string {
        $request = Request::create($uri, $method, $parameters, [], [], $server);
        self::applyTrustedProxies($request, $trustedProxies);

        return $request->getSchemeAndHttpHost() . $request->getRequestUri();
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, string>  $server
     */
    public static function signature(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        ?string $trustedProxies = null,
        string $authToken = self::AUTH_TOKEN,
    ): string {
        $url = self::validationUrl($method, $uri, $parameters, $server, $trustedProxies);
        $bodyParams = strtoupper($method) === 'POST' ? $parameters : [];

        return (new RequestValidator($authToken))->computeSignature($url, $bodyParams);
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<string, string>  $server
     * @return array<string, string>
     */
    public static function signedServer(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        ?string $trustedProxies = null,
        string $authToken = self::AUTH_TOKEN,
    ): array {
        $server['HTTP_X_TWILIO_SIGNATURE'] = self::signature(
            $method,
            $uri,
            $parameters,
            $server,
            $trustedProxies,
            $authToken,
        );

        return $server;
    }

    private static function applyTrustedProxies(Request $request, ?string $trustedProxies): void
    {
        if ($trustedProxies === null || $trustedProxies === '') {
            return;
        }

        if ($trustedProxies === '*' || $trustedProxies === '**') {
            $request->setTrustedProxies(
                [$request->server->get('REMOTE_ADDR')],
                self::TRUSTED_PROXY_HEADERS,
            );

            return;
        }

        $ips = array_map('trim', explode(',', $trustedProxies));
        $request->setTrustedProxies($ips, self::TRUSTED_PROXY_HEADERS);
    }
}
