<?php

namespace Tests\Support;

use App\Simulator\SimulatorResponse;
use App\Simulator\Transport;

/**
 * Simulator transport that dispatches through the test kernel instead of the
 * network, so yap:simulate's scenarios run inside the Pest suite. Requests
 * still go through the full middleware stack, signature validation included.
 */
class KernelTransport implements Transport
{
    /** @var array<string, array<string, string>> */
    private array $jars = [];

    public function send(string $method, string $url, array $params, array $headers, string $jar): SimulatorResponse
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        $response = test()->call($method, $url, $params, $this->jars[$jar] ?? [], [], $server);

        foreach ($response->headers->getCookies() as $cookie) {
            $this->jars[$jar][$cookie->getName()] = $cookie->getValue();
        }

        return new SimulatorResponse($response->getStatusCode(), (string) $response->getContent(), $url);
    }
}
