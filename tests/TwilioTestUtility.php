<?php

namespace Tests;

use App\Services\SettingsService;
use App\Services\TwilioService;
use Twilio\Rest\Client;

class TwilioTestUtility
{
    public TwilioService $twilio;
    public SettingsService $settings;
    public Client $client;
}
