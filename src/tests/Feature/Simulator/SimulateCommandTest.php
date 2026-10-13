<?php

use App\Services\FakeTwilio\FakeTwilioStore;
use App\Services\TwilioService;
use App\Simulator\Transport;
use App\Simulator\TwilioSimulator;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Route;
use Tests\FakeHttp;
use Tests\Support\KernelTransport;

/*
 * yap:simulate run in-process: every simulated webhook goes through the full
 * middleware stack with real signature validation, and every outbound Twilio
 * call goes through the real Twilio SDK into the fake transport.
 */

beforeEach(function () {
    FakeHttp::install();
    config([
        'twilio.fake_mode' => true,
        'twilio.disable_signature_validation' => false,
        'app.url' => 'http://localhost',
    ]);

    // routes/web.php decided at boot that fake mode was off; mount the routes now.
    Route::middleware(['web', 'twilio.signature'])->group(base_path('routes/fake-twilio.php'));
    app('router')->getRoutes()->refreshNameLookups();

    app()->instance(Transport::class, new KernelTransport());
});

function simulatorForTest(): TwilioSimulator
{
    $settings = app(SettingsService::class);

    return new TwilioSimulator(
        'http://localhost',
        (string) $settings->get('twilio_account_sid'),
        (string) $settings->get('twilio_auth_token'),
        new KernelTransport(),
    );
}

test('fake mode builds the Twilio client on the recording transport', function () {
    $twilio = new TwilioService();
    $twilio->client()->messages->create('+17325551212', ['from' => '+12125551212', 'body' => 'hello']);

    $requests = (new FakeTwilioStore())->requests();
    expect($requests)->toHaveCount(1)
        ->and($requests[0]['resource'])->toBe('messages.create')
        ->and($requests[0]['data'])->toMatchArray(['To' => '+17325551212', 'Body' => 'hello'])
        ->and($requests[0]['url'])->toStartWith('https://api.twilio.com/2010-04-01/Accounts/');
});

test('fake mode is ignored in production', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(FakeTwilioStore::enabled())->toBeFalse();
});

test('unknown Twilio resources get a Twilio-shaped 404', function () {
    $twilio = new TwilioService();

    expect(fn () => $twilio->client()->queues->read())
        ->toThrow(\Twilio\Exceptions\RestException::class);
});

test('meeting search call scenario passes', function () {
    $this->artisan('yap:simulate', ['scenarios' => ['meeting-search']])
        ->expectsOutputToContain('PASS meeting-search')
        ->assertSuccessful();
});

test('sms meeting search scenario passes', function () {
    $this->artisan('yap:simulate', ['scenarios' => ['sms-meeting-search']])
        ->expectsOutputToContain('PASS sms-meeting-search')
        ->assertSuccessful();
});

test('helpline volunteer answers and joins the caller', function () {
    $this->artisan('yap:simulate', [
        'scenarios' => ['helpline-volunteer'],
        '--service-body' => '1006',
        '--volunteer' => '+15555551234',
        '--seed' => true,
    ])
        ->expectsOutputToContain('volunteer accepted and joined')
        ->expectsOutputToContain('PASS helpline-volunteer')
        ->assertSuccessful();

    $created = (new FakeTwilioStore())->requests(0, 'calls.create');
    expect($created)->toHaveCount(1)
        ->and($created[0]['data']['To'])->toBe('+15555551234');
});

test('helpline volunteer no-answer falls through to voicemail', function () {
    $this->artisan('yap:simulate', [
        'scenarios' => ['helpline-volunteer'],
        '--service-body' => '1006',
        '--outcome' => 'no-answer',
        '--seed' => true,
    ])
        ->expectsOutputToContain('redirected to voicemail')
        ->expectsOutputToContain('PASS helpline-volunteer')
        ->assertSuccessful();
});

test('running every scenario skips helpline-volunteer without a service body', function () {
    $this->artisan('yap:simulate')
        ->expectsOutputToContain('SKIP helpline-volunteer')
        ->expectsOutputToContain('PASS meeting-search')
        ->expectsOutputToContain('PASS sms-meeting-search')
        ->assertSuccessful();
});

test('a wrong auth token is rejected by signature validation', function () {
    $this->artisan('yap:simulate', ['scenarios' => ['meeting-search'], '--auth-token' => 'not-the-token'])
        ->expectsOutputToContain('returned HTTP 403')
        ->assertFailed();
});

test('a failed expectation fails the run', function () {
    $this->artisan('yap:simulate', ['scenarios' => ['helpline-volunteer']])
        ->expectsOutputToContain('FAIL helpline-volunteer')
        ->assertFailed();
});

test('outbound requests and conference state are inspectable through the control API', function () {
    $simulator = simulatorForTest();
    $simulator->reset();

    $call = $simulator->startCall('+17325551212', '+12125551212');
    expect($call->isWaitingOn('Gather'))->toBeTrue()
        ->and($call->transcript())->toContain('press two to search for meetings');

    $fetches = $simulator->outbound('calls.fetch');
    expect($fetches)->toHaveCount(1)
        ->and($fetches[0]['response']['phone_number_sid'])->toStartWith('PN');

    $conference = $simulator->joinConference('sim-conf', $call->sid)['conference'];
    expect($conference['participants'])->toBe([$call->sid])
        ->and($conference['sid'])->toBe(FakeTwilioStore::conferenceSid('sim-conf'));

    $twilio = new TwilioService();
    $conferences = $twilio->client()->conferences->read(['friendlyName' => 'sim-conf', 'status' => 'in-progress']);
    expect($conferences)->toHaveCount(1);
    expect($twilio->client()->conferences($conferences[0]->sid)->participants->read()[0]->callSid)->toBe($call->sid);

    $twilio->hup($call->sid);
    expect($twilio->client()->calls($call->sid)->fetch()->status)->toBe('completed')
        ->and($twilio->client()->conferences->read(['friendlyName' => 'sim-conf']))->toBe([]);
});
