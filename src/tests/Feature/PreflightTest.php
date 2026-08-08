<?php

use App\Services\GeocodingService;
use App\Services\Preflight\PreflightService;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Services\TwilioService;
use Illuminate\Support\Facades\DB;
use Tests\FakeHttp;
use Tests\FakeTwilioHttpClient;

use Tests\Support\TwilioComplianceMocks;

function insertLegacyPreflightUser(int $id, string $username): void
{
    DB::table('users')->insert([
        'id' => $id,
        'name' => 'Test User',
        'username' => $username,
        'password' => 'hash',
        'permissions' => 0,
        'is_admin' => 0,
        'created_on' => now(),
        'service_bodies' => null,
    ]);
}

function setupUpgradeAdvisorMocks(array $overrides = []): SettingsService
{
    FakeHttp::install();

    $settingsService = app(SettingsService::class);
    app()->instance(SettingsService::class, $settingsService);

    $fakeHttpClient = new FakeTwilioHttpClient();
    $twilioClient = mock('Twilio\Rest\Client', [
        'username' => 'fake',
        'password' => 'fake',
        'httpClient' => $fakeHttpClient,
    ])->makePartial();

    $twilioService = mock(TwilioService::class)->makePartial();
    $twilioService->shouldReceive('client')->withArgs([])->andReturn($twilioClient);
    $twilioService->shouldReceive('settings')->andReturn($settingsService);
    app()->instance(TwilioService::class, $twilioService);

    $geocodingService = mock(GeocodingService::class)->makePartial();
    $geocodingService
        ->shouldReceive('ping')
        ->withArgs(['91409'])
        ->andReturn((object) ['status' => 'OK'])
        ->zeroOrMoreTimes();
    app()->instance(GeocodingService::class, $geocodingService);

    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService
        ->shouldReceive('getTimeZoneForCoordinates')
        ->withAnyArgs()
        ->andReturn((object) ['status' => 'OK'])
        ->zeroOrMoreTimes();
    app()->instance(TimeZoneService::class, $timezoneService);

    $twilioClient->shouldReceive('getAccountSid')->andReturn('123');
    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\InstanceContext');
    $incomingPhoneNumberInstance = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumberInstance->voiceUrl = 'http://localhost:3100/yap/index.php';
    $incomingPhoneNumberInstance->phoneNumber = '+12125551212';
    $incomingPhoneNumberInstance->sid = 'PNaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    $incomingPhoneNumberContext->shouldReceive('read')->withNoArgs()
        ->andReturn([$incomingPhoneNumberInstance])->zeroOrMoreTimes();
    $twilioClient->incomingPhoneNumbers = $incomingPhoneNumberContext;
    TwilioComplianceMocks::apply($twilioClient, $overrides['twilioCompliance'] ?? []);

    return $settingsService;
}

test('yap:preflight passes with a healthy database', function () {
    insertLegacyPreflightUser(1, 'alice');
    insertLegacyPreflightUser(2, 'bob');

    $this->artisan('yap:preflight')
        ->assertExitCode(0)
        ->expectsOutputToContain('[PASS] Duplicate usernames')
        ->expectsOutputToContain('[PASS] Twilio auth token')
        ->expectsOutputToContain('[PASS] PHP extensions');
});

test('yap:preflight fails on duplicate usernames', function () {
    DB::statement('ALTER TABLE users DROP INDEX username_unique');
    insertLegacyPreflightUser(10, 'dupe');
    insertLegacyPreflightUser(11, 'dupe');

    $this->artisan('yap:preflight')
        ->assertExitCode(1)
        ->expectsOutputToContain('[FAIL] Duplicate usernames')
        ->expectsOutputToContain('Resolve duplicate usernames before upgrading');
});

test('yap:preflight fails on null username', function () {
    insertLegacyPreflightUser(20, 'valid_user');
    DB::statement('ALTER TABLE users MODIFY username VARCHAR(45) NULL');
    DB::table('users')->insert([
        'id' => 21,
        'name' => 'Null Username',
        'username' => null,
        'password' => 'hash',
        'permissions' => 0,
        'is_admin' => 0,
        'created_on' => now(),
        'service_bodies' => null,
    ]);

    $this->artisan('yap:preflight')
        ->assertExitCode(1)
        ->expectsOutputToContain('[FAIL] Empty usernames');
});

test('yap:preflight fails when twilio auth token is missing', function () {
    $settings = app(SettingsService::class);
    $settings->set('twilio_auth_token', '');
    app()->instance(SettingsService::class, $settings);

    $this->artisan('yap:preflight')
        ->assertExitCode(1)
        ->expectsOutputToContain('[FAIL] Twilio auth token');
});

test('yap:preflight fails when session driver is database', function () {
    config(['session.driver' => 'database']);

    $this->artisan('yap:preflight')
        ->assertExitCode(1)
        ->expectsOutputToContain('[FAIL] Session driver');
});

test('preflight service reports missing users unique index', function () {
    DB::statement('ALTER TABLE users DROP INDEX username_unique');
    insertLegacyPreflightUser(30, 'solo_user');

    $result = app(PreflightService::class)->run();
    $schemaCheck = collect($result['checks'])->firstWhere('id', 'users_schema');

    expect($result['passed'])->toBeFalse();
    expect($schemaCheck['passed'])->toBeFalse();
    expect($schemaCheck['remediation'])->toContain('username_unique');
});

test('upgrade endpoint includes preflight checks', function () {
    insertLegacyPreflightUser(40, 'upgrade_check_user');
    setupUpgradeAdvisorMocks();

    $response = $this->get('/api/v1/upgrade');

    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'warnings',
            'version',
            'build',
            'checks' => [
                '*' => ['id', 'label', 'status'],
            ],
        ]);

    $checks = $response->json('checks');
    expect(collect($checks)->pluck('id'))->toContain('duplicate_usernames', 'twilio_auth_token', 'php_extensions');
});

test('upgrade endpoint fails when preflight checks fail', function () {
    $settings = setupUpgradeAdvisorMocks();
    $settings->set('twilio_auth_token', '');
    app()->instance(SettingsService::class, $settings);

    $response = $this->get('/api/v1/upgrade');

    $response
        ->assertStatus(200)
        ->assertJson([
            'status' => false,
        ]);

    expect($response->json('checks'))->not->toBeEmpty();
});

test('upgrade endpoint includes twilio compliance checks', function () {
    insertLegacyPreflightUser(41, 'compliance_check_user');
    setupUpgradeAdvisorMocks();

    $response = $this->get('/api/v1/upgrade');

    $checks = collect($response->json('checks'));
    expect($checks->pluck('id'))->toContain('voice_geo_us', 'sms_a2p_brand', 'trust_hub_profile');
    expect($checks->firstWhere('id', 'voice_geo_us')['status'])->toBe('pass');
});

test('upgrade endpoint fails when us voice geo permissions are denied', function () {
    insertLegacyPreflightUser(42, 'geo_denied_user');
    setupUpgradeAdvisorMocks([
        'twilioCompliance' => [
            'usCountry' => ['lowRiskNumbersEnabled' => false],
        ],
    ]);

    $response = $this->get('/api/v1/upgrade');

    $response
        ->assertStatus(200)
        ->assertJson([
            'status' => false,
        ]);

    expect($response->json('checks'))->not->toBeEmpty();

    $geoCheck = collect($response->json('checks'))->firstWhere('id', 'voice_geo_us');
    expect($geoCheck)->toMatchArray([
        'id' => 'voice_geo_us',
        'label' => 'US voice geo permissions',
        'status' => 'fail',
        'message' => 'Low-risk US dialing is not enabled. Volunteer outbound calls to US numbers will fail.',
        'url' => 'https://www.twilio.com/console/voice/calls/geo-permissions',
    ]);
});
