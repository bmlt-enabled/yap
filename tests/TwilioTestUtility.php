<?php

namespace Tests;

use App\Services\TwilioService;
use Twilio\Rest\Client;

class TwilioTestUtility
{
    public TwilioService $service;
    public Client $client;
}
