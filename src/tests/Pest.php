<?php

use App\Models\User;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Fakes\FakeTwilioAccount;
use Tests\FakeTwilioHttpClient;
use Tests\FakeTwilioUnauthorizedHttpClient;
use Tests\Support\TwimlExpectations;
use Tests\TestCase;
use Tests\TwilioTestUtility;
use Twilio\Rest\Client;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeAll(function () {
        putenv("ENVIRONMENT=test");
    })
    ->beforeEach(function () {
        mt_srand(1580);
        date_default_timezone_set('UTC');

        // Keep the suite hermetic. Everything the app fetches goes through
        // App\Services\HttpService, which is built entirely on the Http facade,
        // so this turns any request a test forgot to fake into a loud failure
        // naming the URL instead of a silent call to someone else's server.
        //
        // Set YAP_ALLOW_STRAY_HTTP=1 to let real requests through while
        // debugging locally - e.g. to re-record a fixture. It is deliberately
        // not set anywhere in CI.
        if (!getenv("YAP_ALLOW_STRAY_HTTP")) {
            Http::preventStrayRequests();
        }

        $this->artisan('migrate:fresh');
    })
    ->afterEach(function () {
        date_default_timezone_set('UTC');
        expect(date_default_timezone_get())->toBe('UTC');
    })
    ->in('Feature');

/**
 * Opt-in Twilio harness: stateful FakeTwilioAccount wired through the same Client
 * seam as setupTwilioService(), without disturbing per-test Mockery expectations
 * in the legacy helpline tests.
 *
 * @return array{0: TwilioTestUtility, 1: FakeTwilioAccount}
 */
function setupFakeTwilioService(): array
{
    $utility = new TwilioTestUtility();
    $account = new FakeTwilioAccount();
    $fakeHttpClient = new FakeTwilioHttpClient();
    $utility->client = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();

    $utility->client->calls = $account->callList();
    $utility->client->shouldReceive('calls')->andReturnUsing(
        fn (string $sid) => $account->callContext($sid)
    );
    $utility->client->conferences = $account->conferenceList();
    $utility->client->shouldReceive('conferences')->andReturnUsing(
        fn (?string $sid = null) => $sid !== null ? $account->conferenceContext($sid) : $account->conferenceList()
    );
    $utility->client->messages = $account->messageList();
    $utility->client->lookups = (object) ['v1' => $account->lookupsV1()];
    $utility->client->shouldReceive('incomingPhoneNumbers')->andReturnUsing(
        fn (string $sid) => $account->incomingPhoneNumberContext($sid)
    );
    $utility->client->shouldReceive('getAccountSid')->andReturn('AC123');

    $utility->twilio = mock(TwilioService::class)->makePartial();
    $utility->settings = app(SettingsService::class);
    app()->instance(TwilioService::class, $utility->twilio);
    $utility->twilio->shouldReceive('client')->withArgs([])->andReturn($utility->client);
    $utility->twilio->shouldReceive('settings')->andReturn($utility->settings);

    return [$utility, $account];
}

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

/**
 * Bind a TwilioService whose client answers every request with the 401 the real
 * API returns for bad credentials, so tests that assert on that error do not
 * have to reach api.twilio.com to provoke it.
 */
function useUnauthorizedTwilioClient(): void
{
    $twilio = mock(TwilioService::class)->makePartial();
    $twilio->shouldReceive("client")->andReturn(new Client(
        "fake",
        "fake",
        null,
        null,
        new FakeTwilioUnauthorizedHttpClient()
    ));
    app()->instance(TwilioService::class, $twilio);
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

expect()->extend('toRedirectTo', function (string $uri) {
    TwimlExpectations::assertRedirectsTo($this->value, $uri);

    return $this;
});

expect()->extend('toGatherTo', function (string $action) {
    TwimlExpectations::assertGathersTo($this->value, $action);

    return $this;
});

expect()->extend('toDialConference', function (?string $conferenceName = null) {
    TwimlExpectations::assertDialsConference($this->value, $conferenceName);

    return $this;
});

expect()->extend('toHaveDialed', function (string ...$numbers) {
    /** @var FakeTwilioAccount $account */
    $account = $this->value;
    foreach ($numbers as $number) {
        expect($account->dialedNumbers())->toContain($number);
    }

    return $this;
});

expect()->extend('toHaveDialedInOrder', function (array $numbers) {
    /** @var FakeTwilioAccount $account */
    $account = $this->value;
    expect($account->dialedNumbers())->toBe($numbers);

    return $this;
});

expect()->extend('toHaveSentSms', function (string $to, ?string $bodyContains = null) {
    /** @var FakeTwilioAccount $account */
    $account = $this->value;
    $match = collect($account->messages)->first(
        fn (array $message) => $message['to'] === $to
            && ($bodyContains === null || str_contains((string) $message['body'], $bodyContains))
    );
    expect($match)->not->toBeNull("Expected SMS to {$to}" . ($bodyContains ? " containing '{$bodyContains}'" : ''));

    return $this;
});

expect()->extend('toHaveRedirectedCall', function (string $url) {
    /** @var FakeTwilioAccount $account */
    $account = $this->value;
    $match = collect($account->callUpdates)->first(
        fn (array $update) => isset($update['url']) && str_contains($update['url'], $url)
    );
    expect($match)->not->toBeNull("Expected a call redirect to URL containing '{$url}'");

    return $this;
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
