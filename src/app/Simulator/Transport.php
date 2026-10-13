<?php

namespace App\Simulator;

/**
 * Carries a signed webhook request to a yap instance.
 *
 * Twilio keeps cookies per call, which is what lets yap's session survive the
 * dozen webhook requests that make up one call. A transport therefore keeps a
 * cookie jar per $jar key; the simulator uses one jar per call leg.
 */
interface Transport
{
    /**
     * @param array<string, scalar|array> $params query params for GET, form body for POST
     * @param array<string, string> $headers
     */
    public function send(string $method, string $url, array $params, array $headers, string $jar): SimulatorResponse;
}
