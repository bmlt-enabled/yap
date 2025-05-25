<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery\MockInterface;
use Twilio\Rest\Client;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public string $conferenceName;
    public MockInterface|Client $twilioClient;

    protected function createTestResponse($response, $request = null)
    {
        return ExtendedTestResponse::fromBaseResponse($response);
    }
}
