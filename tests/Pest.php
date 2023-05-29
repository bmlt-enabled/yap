<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Models\Timezone;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;
use Tests\TwilioTestUtility;

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

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
