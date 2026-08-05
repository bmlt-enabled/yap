<?php

/**
 * Tests that Twilio webhook endpoints are exempt from CSRF verification.
 * Twilio sends POST requests without CSRF tokens, so these routes must be excluded.
 *
 * This test was added after a regression where CSRF was enabled on webhook routes,
 * causing HTTP 419 errors for all Twilio callbacks.
 */

use App\Services\ReadingService;
use Tests\FakeHttp;

beforeEach(function () {
    // Enable CSRF middleware for these tests to ensure exemptions work
    $this->withMiddleware();

    // These routes are only asserted not to return 419, so they passed whether
    // or not their outbound calls succeeded - but they still made them. Stub
    // the three transports they reach: the Http facade (BMLT/Google), the
    // Twilio SDK, and fetch-meditation's own Guzzle client behind
    // ReadingService, which Http::preventStrayRequests() cannot see.
    FakeHttp::install();
    setupTwilioService();

    $readingService = Mockery::mock(ReadingService::class);
    $readingService->shouldReceive('get')->andReturn(['Reading', 'Content']);
    app()->instance(ReadingService::class, $readingService);
});

test('twilio webhook routes do not return 419 CSRF error', function ($route) {
    // POST without CSRF token should not return 419 (Page Expired)
    $response = $this->post($route, [
        'CallSid' => 'CA123',
        'From' => '+15551234567',
        'To' => '+15559876543',
    ]);

    // Assert we don't get a CSRF error - other errors (500, etc) are acceptable
    // as they indicate the route was reached (CSRF passed)
    expect($response->status())->not->toBe(419);
})->with([
    '/lng-selector.php',
    '/lng-selector',
    '/ping.php',
    '/ping',
    '/status.php',
    '/status',
    '/fetch-jft.php',
    '/fetch-jft',
    '/fetch-spad.php',
    '/fetch-spad',
    '/index.php',
    '/input-method.php',
    '/voicemail.php',
    '/sms-gateway.php',
]);
