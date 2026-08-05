<?php

namespace Tests;

use Twilio\AuthStrategy\AuthStrategy;
use Twilio\Http\Client;
use Twilio\Http\Response;

/**
 * A Twilio HTTP client that answers every request with the 401 the real API
 * returns for bad credentials.
 *
 * The Twilio SDK does not use the Http facade, so Http::preventStrayRequests()
 * cannot see it. Without this, UpgradeAdvisorTest's "fake twilio credentials"
 * cases reached api.twilio.com for real and asserted on the live 401 - the one
 * place in the suite that depended on a third party returning an *error*.
 *
 * Twilio\Page::processResponse() builds the exception message from this body,
 * so the JSON below is what produces
 * "[HTTP 401] Unable to fetch page: Authentication Error - invalid username".
 */
class FakeTwilioUnauthorizedHttpClient implements Client
{
    public function request(
        string $method,
        string $url,
        array $params = [],
        array $data = [],
        array $headers = [],
        ?string $user = null,
        ?string $password = null,
        ?int $timeout = null,
        ?AuthStrategy $authStrategy = null
    ): Response {
        return new Response(401, json_encode([
            "code" => 20003,
            "detail" => "Your AccountSid or AuthToken was incorrect.",
            "message" => "Authentication Error - invalid username",
            "more_info" => "https://www.twilio.com/docs/errors/20003",
            "status" => 401,
        ]));
    }
}
