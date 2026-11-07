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

        // The test app keeps one session store for every request, and starting a
        // session merges what the handler holds for the cookie's id into the
        // attributes already there. Left alone, a new call would inherit the
        // last call's session (its Gender, its override_service_body_id). Empty
        // it so each request sees only its own call's session, as on a server.
        app('session.store')->flush();

        $response = test()->call($method, $url, $params, $this->jars[$jar] ?? [], [], $server);

        foreach ($response->headers->getCookies() as $cookie) {
            $this->jars[$jar][$cookie->getName()] = $cookie->getValue();
        }

        return new SimulatorResponse($response->getStatusCode(), (string) $response->getContent(), $url);
    }
}
