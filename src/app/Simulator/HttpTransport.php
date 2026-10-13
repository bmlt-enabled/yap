<?php

namespace App\Simulator;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

/**
 * Real HTTP, for driving a running instance (php artisan serve, docker, a hosted
 * environment). Redirects are not followed: Twilio does not follow HTTP
 * redirects on webhooks, and a 3xx from yap is a bug worth seeing.
 */
class HttpTransport implements Transport
{
    private Client $client;

    /** @var array<string, CookieJar> */
    private array $jars = [];

    public function __construct(int $timeout = 30)
    {
        $this->client = new Client([
            'allow_redirects' => false,
            'http_errors' => false,
            'timeout' => $timeout,
        ]);
    }

    public function send(string $method, string $url, array $params, array $headers, string $jar): SimulatorResponse
    {
        $options = [
            'headers' => $headers,
            'cookies' => $this->jars[$jar] ??= new CookieJar(),
        ];

        if (strtoupper($method) === 'GET') {
            $url = UrlResolver::withQuery($url, $params);
        } else {
            $options['form_params'] = $params;
        }

        $response = $this->client->request($method, $url, $options);

        return new SimulatorResponse($response->getStatusCode(), (string) $response->getBody(), $url);
    }
}
