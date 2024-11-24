<?php

use App\Services\SettingsService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\FakeTwilioHttpClient;
use Tests\TestCase;
use Tests\TwilioTestUtility;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        env("ENVIRONMENT", "test");
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

function getSessionCookieValue($response)
{
    $cookies = $response->headers->getCookies();
    $sessionCookie = collect($cookies)->first(fn($cookie) => $cookie->getName() === 'laravel_session');
    return $sessionCookie->getValue();
}

expect()->extend('hasQueryParam', function ($param, $pattern) {
    $queryString = Str::after($this->value, '?');
    return expect($queryString)->toMatch('/' . $param . '=' . $pattern . '/');
});

expect()->extend('toHaveUrlAndQueryStringMatching', function (string $baseUrl, array $expectedQuery) {
    // Parse the URL
    $parsedUrl = parse_url($this->value);
    $actualBaseUrl = "{$parsedUrl['scheme']}://{$parsedUrl['host']}{$parsedUrl['path']}";

    // Validate the base URL
    expect($actualBaseUrl)->toBe($baseUrl);

    $queryString = $parsedUrl['query'] ?? '';
    $parsedQuery = [];
    foreach (explode('&', $queryString) as $pair) {
        [$key, $value] = explode('=', $pair, 2);
        $parsedQuery[$key] = $value;
    }

    foreach ($expectedQuery as $key => $expectedPattern) {
        if (!isset($parsedQuery[$key])) {
            throw new Exception("Query parameter '{$key}' is missing.");
        }

        if (!preg_match($expectedPattern, $parsedQuery[$key])) {
            throw new Exception("Query parameter '{$key}' does not match the expected pattern.");
        }
    }

    // If all validations pass, return the original value
    return $this->value;
});
