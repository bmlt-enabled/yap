<?php

namespace App\Simulator;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;

/**
 * Resolves the URLs found in TwiML (Gather action, Redirect, Number url, ...)
 * the way Twilio does: relative to the URL of the document they came from.
 */
class UrlResolver
{
    public static function resolve(string $base, string $reference): string
    {
        return self::normalize((string) UriResolver::resolve(new Uri($base), new Uri(trim($reference))));
    }

    /**
     * @param array<string, scalar|array> $params
     */
    public static function withQuery(string $url, array $params): string
    {
        if ($params === []) {
            return self::normalize($url);
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return self::normalize($url . $separator . http_build_query($params));
    }

    /**
     * Encode the URL exactly as the HTTP client will put it on the wire, so the
     * signature is computed over the same bytes yap validates against.
     */
    public static function normalize(string $url): string
    {
        return (string) new Uri($url);
    }
}
