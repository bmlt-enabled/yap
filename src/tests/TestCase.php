<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Twilio\Rest\Client;
use PHPUnit\Framework\Assert;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public string $conferenceName;
    public MockInterface|Client $twilioClient;

    protected function createTestResponse($response)
    {
        return ExtendedTestResponse::fromBaseResponse($response);
    }
}
