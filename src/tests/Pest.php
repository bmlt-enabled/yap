<?php

use App\Services\SettingsService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\FakeTwilioHttpClient;
use Tests\TestCase;
use Tests\TwilioTestUtility;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        env("ENVIRONMENT", "test");
        $_COOKIE["PHPSESSID"] = "fake";
        $this->artisan('migrate:fresh');
    })
    ->in('Feature');

function setupTwilioService(): TwilioTestUtility
{
    $utility = new TwilioTestUtility();
    $fakeHttpClient = new FakeTwilioHttpClient();
    $utility->client = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $utility->twilio = mock(TwilioService::class)->makePartial();
    $utility->settings = new SettingsService();
    app()->instance(SettingsService::class, $utility->settings);
    app()->instance(TwilioService::class, $utility->twilio);
    $utility->twilio->shouldReceive("client")->withArgs([])->andReturn($utility->client);
    $utility->twilio->shouldReceive("settings")->andReturn($utility->settings);
    return $utility;
}
