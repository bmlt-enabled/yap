<?php

namespace Tests;

use Illuminate\Testing\TestResponse;
use Tests\Support\TwilioSignatureTestHelper;

/**
 * CallScenario that signs every webhook like Twilio, for tests where signature
 * validation is genuinely enabled (TWILIO_DISABLE_SIGNATURE_VALIDATION=false).
 */
class SignedCallScenario extends CallScenario
{
    private string $authToken;

    public function __construct(array $settings = [], ?string $authToken = null)
    {
        parent::__construct($settings);
        $this->authToken = $authToken ?? (string) $this->utility->settings->get('twilio_auth_token');
    }

    protected function call($method, string $uri, array $data): TestResponse
    {
        $trustedProxies = config('trustedproxy.proxies');
        $server = TwilioSignatureTestHelper::signedServer(
            $method,
            $uri,
            $data,
            [],
            is_string($trustedProxies) ? $trustedProxies : null,
            $this->authToken,
        );

        return test()->call($method, $uri, $data, [], [], $server);
    }
}
