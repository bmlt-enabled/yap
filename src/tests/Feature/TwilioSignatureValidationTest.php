<?php

use App\Services\SettingsService;
use Illuminate\Http\Middleware\TrustProxies as IlluminateTrustProxies;
use Tests\Support\TwilioSignatureTestHelper;
use Twilio\Security\RequestValidator;

const TWILIO_SIG_TOKEN = TwilioSignatureTestHelper::AUTH_TOKEN;

beforeEach(function () {
    // Exercise the real middleware: turn the non-production dev bypass off (it is
    // enabled globally via phpunit.xml) so signature validation actually runs for
    // the tests in this file.
    config(['twilio.disable_signature_validation' => false]);
    putenv('TRUSTED_PROXIES');
    unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
    config(['trustedproxy.proxies' => null]);

    session()->put('override_twilio_auth_token', TWILIO_SIG_TOKEN);
    stubTwilioForInboundWebhook();
    IlluminateTrustProxies::flushState();
});

afterEach(function () {
    putenv('TRUSTED_PROXIES');
    unset($_ENV['TRUSTED_PROXIES'], $_SERVER['TRUSTED_PROXIES']);
    config(['trustedproxy.proxies' => null]);
    IlluminateTrustProxies::flushState();
});

function stubTwilioForInboundWebhook(): void
{
    $utility = setupTwilioService();
    $utility->twilio->client()->shouldReceive('getAccountSid')->andReturn('123');

    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->phoneNumberSid = 'PNtest';

    $callContext = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContext->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $utility->twilio->client()->shouldReceive('calls')->andReturn($callContext);

    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberContext');
    $incomingPhoneNumberInstance = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumberInstance->statusCallback = 'https://example.org/status.php';
    $incomingPhoneNumberInstance->phoneNumber = '+15559876543';
    $incomingPhoneNumberContext->shouldReceive('fetch')->withNoArgs()->andReturn($incomingPhoneNumberInstance);
    $utility->twilio->client()->shouldReceive('incomingPhoneNumbers')->andReturn($incomingPhoneNumberContext);
}

/** @return array<string, mixed> */
function twilioWebhookParameters(): array
{
    return [
        'CallSid' => 'CA123',
        'From' => '+15551234567',
        'To' => '+15559876543',
    ];
}

/**
 * @param  array<string, mixed>  $parameters
 * @param  array<string, string>  $server
 */
function callTwilioRoute(string $method, string $uri, array $parameters = [], array $server = [])
{
    return test()->call($method, $uri, $parameters, [], [], $server);
}

/**
 * @param  array<string, mixed>  $parameters
 * @param  array<string, string>  $server
 */
function callSignedTwilioRoute(
    string $method,
    string $uri,
    array $parameters = [],
    array $server = [],
    ?string $trustedProxies = null,
) {
    $server = TwilioSignatureTestHelper::signedServer(
        $method,
        $uri,
        $parameters,
        $server,
        $trustedProxies,
    );

    if ($trustedProxies !== null && $trustedProxies !== '') {
        putenv('TRUSTED_PROXIES=' . $trustedProxies);
        $_ENV['TRUSTED_PROXIES'] = $trustedProxies;
        $_SERVER['TRUSTED_PROXIES'] = $trustedProxies;
        config(['trustedproxy.proxies' => $trustedProxies]);
    }

    return callTwilioRoute($method, $uri, $parameters, $server);
}

test('rejects a Twilio route with a missing signature', function ($method) {
    $response = callTwilioRoute($method, '/');

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('rejects a Twilio route with an invalid signature', function ($method) {
    $response = callTwilioRoute($method, '/', [], ['HTTP_X_TWILIO_SIGNATURE' => 'not-a-valid-signature']);

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('fails closed when no auth token is configured', function ($method) {
    app(SettingsService::class)->set('twilio_auth_token', '');
    session()->forget('override_twilio_auth_token');

    $response = callTwilioRoute($method, '/');

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('allows a genuinely signed Twilio request without proxy headers', function ($method) {
    $parameters = $method === 'POST' ? twilioWebhookParameters() : twilioWebhookParameters();

    $response = callSignedTwilioRoute($method, '/', $parameters);

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('allows a signed request behind X-Forwarded-Host and X-Forwarded-Proto', function ($method) {
    $parameters = twilioWebhookParameters();

    $server = [
        'HTTP_X_FORWARDED_HOST' => 'yap.example.org',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ];

    $response = callSignedTwilioRoute($method, '/', $parameters, $server, '*');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('allows a signed request with chained X-Forwarded-Host values', function ($method) {
    $parameters = twilioWebhookParameters();

    $server = [
        'HTTP_X_FORWARDED_HOST' => 'a.example.org, edge.internal',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ];

    $response = callSignedTwilioRoute($method, '/', $parameters, $server, '*');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('allows a signed request when X-Forwarded-Host is present but empty', function ($method) {
    $parameters = twilioWebhookParameters();

    // Without TRUSTED_PROXIES the empty header is ignored and validation uses Host.
    $signature = TwilioSignatureTestHelper::signature($method, '/', $parameters, [], null);

    $response = callTwilioRoute($method, '/', $parameters, [
        'HTTP_X_FORWARDED_HOST' => '',
        'HTTP_X_TWILIO_SIGNATURE' => $signature,
    ]);

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('allows a signed request with a non-standard X-Forwarded-Port', function ($method) {
    $parameters = twilioWebhookParameters();

    $server = [
        'HTTP_X_FORWARDED_HOST' => 'yap.example.org',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_PORT' => '8443',
    ];

    $response = callSignedTwilioRoute($method, '/', $parameters, $server, '*');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('rejects a signed request when the proxy strips a path prefix without X-Forwarded-Prefix', function ($method) {
    $parameters = twilioWebhookParameters();

    putenv('TRUSTED_PROXIES=*');
    $_ENV['TRUSTED_PROXIES'] = '*';
    config(['trustedproxy.proxies' => '*']);

    // Twilio signs the public URL including /yap; the app only sees /index.php.
    $server = [
        'HTTP_X_FORWARDED_HOST' => 'yap.example.org',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ];

    $signature = (new RequestValidator(TWILIO_SIG_TOKEN))->computeSignature(
        'https://yap.example.org/yap/index.php',
        $method === 'POST' ? $parameters : [],
    );

    $response = callTwilioRoute($method, '/index.php', $parameters, array_merge($server, [
        'HTTP_X_TWILIO_SIGNATURE' => $signature,
    ]));

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('allows a signed request when the proxy strips a path prefix and sends X-Forwarded-Prefix', function ($method) {
    $parameters = twilioWebhookParameters();

    $server = [
        'HTTP_X_FORWARDED_HOST' => 'yap.example.org',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_PREFIX' => '/yap',
    ];

    $response = callSignedTwilioRoute($method, '/index.php', $parameters, $server, '*');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('allows a signed request when X-Original-Host differs from X-Forwarded-Host', function ($method) {
    $parameters = twilioWebhookParameters();

    $server = [
        'HTTP_X_FORWARDED_HOST' => 'yap.example.org',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_ORIGINAL_HOST' => 'legacy.example.org',
    ];

    $response = callSignedTwilioRoute($method, '/', $parameters, $server, '*');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('rejects forwarded-host signatures when TRUSTED_PROXIES is not configured', function ($method) {
    $parameters = twilioWebhookParameters();

    $server = [
        'HTTP_X_FORWARDED_HOST' => 'yap.example.org',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ];

    // Twilio signed the public URL; without TRUSTED_PROXIES Yap validates against localhost.
    $publicUrl = $method === 'POST'
        ? 'https://yap.example.org/'
        : 'https://yap.example.org/?' . http_build_query($parameters);
    $bodyParams = $method === 'POST' ? $parameters : [];
    $signature = (new RequestValidator(TWILIO_SIG_TOKEN))->computeSignature($publicUrl, $bodyParams);

    $response = callTwilioRoute($method, '/', $parameters, array_merge($server, [
        'HTTP_X_TWILIO_SIGNATURE' => $signature,
    ]));

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('dev bypass skips validation for unsigned requests in non-production', function ($method) {
    config(['twilio.disable_signature_validation' => true]);

    $response = callTwilioRoute($method, '/');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('per-service-body auth token is used on the first webhook when override_service_body_id is present', function () {
    $serviceToken = 'service-body-token';
    session()->put('override_twilio_auth_token', $serviceToken);

    $parameters = twilioWebhookParameters();
    $signature = (new RequestValidator($serviceToken))->computeSignature('http://localhost', $parameters);

    $response = callTwilioRoute('POST', '/', $parameters, [
        'HTTP_X_TWILIO_SIGNATURE' => $signature,
    ]);

    $response->assertStatus(200);
});

test('per-service-body auth token is not re-applied mid-call once call_state exists', function () {
    session()->put('call_state', 'STARTED');
    session()->put('override_twilio_auth_token', 'stale-token');

    $parameters = twilioWebhookParameters();
    $signature = (new RequestValidator(TWILIO_SIG_TOKEN))->computeSignature('http://localhost', $parameters);

    $response = callTwilioRoute('POST', '/input-method.php', $parameters, [
        'HTTP_X_TWILIO_SIGNATURE' => $signature,
    ]);

    // Signed with the global token, but session still holds a stale override.
    $response->assertStatus(403);
});
