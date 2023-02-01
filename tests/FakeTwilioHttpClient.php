<?php

namespace Tests;

use Twilio\Http\Client;
use Twilio\Http\Response;

class FakeTwilioHttpClient implements Client
{
    public function request(
        string $method,
        string $url,
        array $params = [],
        array $data = [],
        array $headers = [],
        string $user = null,
        string $password = null,
        int $timeout = null
    ): Response {
        return new Response(200, "dude");
    }
}
