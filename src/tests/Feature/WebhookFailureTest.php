<?php

use App\Constants\EventId;
use App\Models\RecordEvent;
use App\Models\User;
use App\Models\WebhookFailure;
use App\Services\ReadingService;
use App\Services\WebhookFailureService;
use App\Structures\RecordType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/**
 * bmlt-enabled/yap#1686: a Twilio webhook that 500s is recorded and shown on
 * System Health, without storing what the caller sent.
 */

const CALLER_NUMBER = '+15557654321';
const FAILING_CALL_SID = 'CA0123456789abcdef0123456789abcdef';

function forceJftFailure(string $message = 'BMLT unreachable'): void
{
    $reading = Mockery::mock(ReadingService::class);
    $reading->shouldReceive('get')->andThrow(new RuntimeException($message));
    app()->instance(ReadingService::class, $reading);
}

function callFailingWebhook(array $params = []): \Illuminate\Testing\TestResponse
{
    return test()->call('POST', '/fetch-jft.php', array_merge([
        'CallSid' => FAILING_CALL_SID,
        'From' => CALLER_NUMBER,
        'To' => '+18005550000',
    ], $params));
}

test('a forced exception in a webhook appears on System Health within one request', function () {
    forceJftFailure();

    callFailingWebhook()->assertStatus(500);

    Sanctum::actingAs(User::factory()->admin()->create());
    $response = $this->getJson('/api/v1/webhookFailures')->assertOk();

    $response
        ->assertJsonPath('last_24_hours', 1)
        ->assertJsonPath('last_7_days', 1)
        ->assertJsonPath('retention_days', WebhookFailureService::RETENTION_DAYS)
        ->assertJsonPath('failures.0.method', 'POST')
        ->assertJsonPath('failures.0.route', '/fetch-jft.php')
        ->assertJsonPath('failures.0.status', 500)
        ->assertJsonPath('failures.0.callsid', FAILING_CALL_SID)
        ->assertJsonPath('failures.0.exception_class', RuntimeException::class)
        ->assertJsonPath('failures.0.message', 'BMLT unreachable')
        ->assertJsonPath('failures.0.created_at', '2024-02-19T20:00:00Z')
        ->assertJsonPath('last_failure_at', '2024-02-19T20:00:00Z');

    expect($response->json('failures.0.location'))->toStartWith('tests/Feature/WebhookFailureTest.php:');
});

test('the SMS sid is recorded when there is no CallSid', function () {
    forceJftFailure();

    $this->call('POST', '/fetch-jft.php', ['SmsSid' => 'SM123', 'From' => CALLER_NUMBER])
        ->assertStatus(500);

    expect(WebhookFailure::first()->callsid)->toBe('SM123');
});

test('what the caller sent never reaches the stored message', function () {
    forceJftFailure(sprintf(
        'Lookup failed for %s at 123 Main Street (encoded %s / %s), volunteer (555) 222-3333 and 15551112222',
        CALLER_NUMBER,
        urlencode('123 Main Street'),
        rawurlencode('123 Main Street'),
    ));

    callFailingWebhook([
        'SpeechResult' => '123 Main Street',
        'Digits' => '7',
    ])->assertStatus(500);

    $message = WebhookFailure::first()->message;

    expect($message)
        ->not->toContain(CALLER_NUMBER)
        ->not->toContain('5557654321')
        ->not->toContain('Main Street')
        ->not->toContain('222-3333')
        ->not->toContain('15551112222')
        ->toContain('Lookup failed for [redacted]');
});

test('the query string is never stored', function () {
    forceJftFailure();

    $this->call('GET', '/fetch-jft.php?From=' . urlencode(CALLER_NUMBER) . '&CallSid=' . FAILING_CALL_SID)
        ->assertStatus(500);

    $failure = WebhookFailure::first();
    expect($failure->route)->toBe('/fetch-jft.php')
        ->and($failure->callsid)->toBe(FAILING_CALL_SID);
});

test('only Twilio webhooks are recorded', function () {
    Route::get('/not-a-webhook', fn () => throw new RuntimeException('boom'));

    $this->get('/not-a-webhook')->assertStatus(500);

    expect(WebhookFailure::count())->toBe(0);
});

test('a rejected signature is not a failure', function () {
    config(['twilio.disable_signature_validation' => false]);

    $this->call('POST', '/fetch-jft.php', ['CallSid' => FAILING_CALL_SID])->assertStatus(403);

    expect(WebhookFailure::count())->toBe(0);
});

test('failures older than the retention window are pruned', function () {
    WebhookFailure::create([
        'created_at' => Carbon::now('UTC')->subDays(WebhookFailureService::RETENTION_DAYS + 1)->format('Y-m-d H:i:s'),
        'method' => 'POST',
        'route' => '/old',
        'status' => 500,
    ]);
    WebhookFailure::create([
        'created_at' => Carbon::now('UTC')->subDays(3)->format('Y-m-d H:i:s'),
        'method' => 'POST',
        'route' => '/three-days-ago',
        'status' => 500,
    ]);

    forceJftFailure();
    callFailingWebhook()->assertStatus(500);

    expect(WebhookFailure::pluck('route')->sort()->values()->all())
        ->toBe(['/fetch-jft.php', '/three-days-ago']);

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/v1/webhookFailures')
        ->assertJsonPath('last_24_hours', 1)
        ->assertJsonPath('last_7_days', 2)
        ->assertJsonCount(2, 'failures')
        ->assertJsonPath('failures.1.route', '/three-days-ago');
});

test('each failure lists the events yap recorded for that call', function () {
    RecordEvent::generate(FAILING_CALL_SID, EventId::VOLUNTEER_SEARCH, '2024-02-19 19:59:58', 44, null, RecordType::PHONE);
    RecordEvent::generate('CAother', EventId::MEETING_SEARCH, '2024-02-19 19:59:59', 44, null, RecordType::PHONE);

    forceJftFailure();
    callFailingWebhook()->assertStatus(500);

    Sanctum::actingAs(User::factory()->admin()->create());
    $this->getJson('/api/v1/webhookFailures')
        ->assertJsonCount(1, 'failures.0.events')
        ->assertJsonPath('failures.0.events.0.event_id', EventId::VOLUNTEER_SEARCH)
        ->assertJsonPath('failures.0.events.0.event_name', 'Volunteer Search')
        ->assertJsonPath('failures.0.events.0.event_time', '2024-02-19T19:59:58Z')
        ->assertJsonPath('failures.0.events.0.service_body_id', 44);
});

test('a failure to record never changes the response Twilio gets', function () {
    WebhookFailure::creating(fn () => throw new RuntimeException('database is gone'));
    forceJftFailure();

    callFailingWebhook()->assertStatus(500);
});

test('only top-level admins can read webhook failures', function () {
    $this->getJson('/api/v1/webhookFailures')->assertStatus(401);

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/webhookFailures')->assertStatus(403);
});
