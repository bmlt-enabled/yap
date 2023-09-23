<?php

use App\Repositories\DatabaseMigrationRepository;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;
use Tests\TwilioTestUtility;

// Called before each test.
uses(Tests\TestCase::class)->beforeEach(function () {
    $migrationsRepository = Mockery::mock(DatabaseMigrationRepository::class);
    $migrationsRepository->shouldReceive('getVersion')
        ->withNoArgs()->andReturn(100);
    app()->instance(DatabaseMigrationRepository::class, $migrationsRepository);
})->in('Feature');

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
