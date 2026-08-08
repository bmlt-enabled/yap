<?php

use App\Services\SettingsService;
use App\Services\Upgrade\TwilioComplianceService;
use Tests\FakeHttp;

use Tests\Support\TwilioComplianceMocks;

beforeEach(function () {
    FakeHttp::install();
    $this->settings = app(SettingsService::class);
    $this->complianceService = app(TwilioComplianceService::class);
});

function mockTwilioComplianceClient(array $overrides = []): object
{
    $fakeHttpClient = new \Tests\FakeTwilioHttpClient();
    $twilioClient = mock('Twilio\Rest\Client', [
        'username' => 'fake',
        'password' => 'fake',
        'httpClient' => $fakeHttpClient,
    ])->makePartial();

    TwilioComplianceMocks::apply($twilioClient, $overrides);

    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\InstanceContext');
    $incomingPhoneNumberContext->shouldReceive('read')->andReturn($overrides['incomingPhoneNumbers'] ?? []);
    $twilioClient->incomingPhoneNumbers = $incomingPhoneNumberContext;

    return $twilioClient;
}

test('twilio compliance passes when all checks succeed', function () {
    $client = mockTwilioComplianceClient();

    $checks = $this->complianceService->run($client, $this->settings);

    expect(collect($checks)->pluck('status', 'id')->all())->toMatchArray([
        'twilio_account_type' => 'pass',
        'voice_geo_us' => 'pass',
        'trust_hub_profile' => 'pass',
        'sms_a2p_brand' => 'pass',
        'toll_free_verification' => 'skip',
    ]);
});

test('twilio compliance warns on trial account', function () {
    $client = mockTwilioComplianceClient([
        'account' => ['type' => 'Trial'],
    ]);

    $checks = $this->complianceService->run($client, $this->settings);
    $accountCheck = collect($checks)->firstWhere('id', 'twilio_account_type');

    expect($accountCheck->status)->toBe('warn');
    expect($accountCheck->url)->toContain('twilio.com/console');
});

test('twilio compliance fails when us voice geo permissions are denied', function () {
    $client = mockTwilioComplianceClient([
        'usCountry' => ['lowRiskNumbersEnabled' => false],
    ]);

    $checks = $this->complianceService->run($client, $this->settings);
    $geoCheck = collect($checks)->firstWhere('id', 'voice_geo_us');

    expect($geoCheck->status)->toBe('fail');
    expect($geoCheck->message)->toContain('Low-risk US dialing is not enabled');
});

test('twilio compliance fails when no approved a2p brand exists', function () {
    $pendingBrand = mock('\Twilio\Rest\Messaging\V1\BrandRegistrationInstance');
    $pendingBrand->status = 'PENDING';
    $client = mockTwilioComplianceClient([
        'brands' => [$pendingBrand],
    ]);

    $checks = $this->complianceService->run($client, $this->settings);
    $brandCheck = collect($checks)->firstWhere('id', 'sms_a2p_brand');

    expect($brandCheck->status)->toBe('fail');
});

test('twilio compliance skips a2p brand check when sms is disabled', function () {
    $this->settings->set('sms_disable', 'true');
    $client = mockTwilioComplianceClient([
        'brands' => [],
    ]);

    $checks = $this->complianceService->run($client, $this->settings);
    $brandCheck = collect($checks)->firstWhere('id', 'sms_a2p_brand');

    expect($brandCheck->status)->toBe('skip');
});

test('twilio compliance warns on unverified toll-free numbers', function () {
    $incomingPhoneNumber = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumber->phoneNumber = '+18885551212';
    $incomingPhoneNumber->sid = 'PNaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    $client = mockTwilioComplianceClient([
        'incomingPhoneNumbers' => [$incomingPhoneNumber],
        'tollfreeVerifications' => [],
    ]);

    $checks = $this->complianceService->run($client, $this->settings);
    $tollFreeCheck = collect($checks)->firstWhere('id', 'toll_free_verification');

    expect($tollFreeCheck->status)->toBe('warn');
    expect($tollFreeCheck->message)->toContain('+18885551212');
});
