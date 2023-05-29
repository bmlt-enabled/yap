<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public string $conferenceName;
    /**
     * @var \Mockery\Mock|(\Mockery\MockInterface&\Twilio\Rest\Client)
     */
    public \Mockery\Mock|\Mockery\MockInterface|\Twilio\Rest\Client $twilioClient;
}
