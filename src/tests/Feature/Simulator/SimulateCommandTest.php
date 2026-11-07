<?php

use App\Services\FakeTwilio\FakeMailer;
use App\Services\FakeTwilio\FakeTwilioMode;
use App\Services\FakeTwilio\FakeTwilioStore;
use App\Services\TwilioService;
use App\Simulator\Scenarios\ScenarioRegistry;
use App\Simulator\Transport;
use App\Simulator\TwilioSimulator;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\FakeHttp;
use Tests\Support\KernelTransport;

/*
 * yap:simulate run in-process: every simulated webhook goes through the full
 * middleware stack with real signature validation, and every outbound Twilio
 * call goes through the real Twilio SDK into the fake transport.
 */

beforeEach(function () {
    // The scenarios search BMLT for meetings on today's weekday, by the real
    // clock, and FakeHttp only records the scenario coordinates for weekdays
    // 2-4. Serve the Tuesday fixture for the other days so the scenarios find
    // meetings whatever day the suite runs; its meetings are never "today" on
    // those days, so the time-of-day filter cannot empty the list.
    $tuesday = Http::response(FakeHttp::fixture('bmlt/meeting-search-42.867970-day3.json'));
    $overrides = [
        // meeting-search-options: no meetings within a 5 mile meeting_search_radius.
        '*switcher=GetSearchResults*data_field_key=id_bigint*geo_width=5&*' => Http::response(FakeHttp::fixture('bmlt/meeting-search-0-0.json')),
        // sms-helpline: the scenario coordinates are covered by the Finger Lakes bodies, as on the live root server.
        '*service_body_bigint*lat_val=42.867970*' => Http::response(FakeHttp::fixture('bmlt/helpline-search-14456.json')),
    ];
    foreach ([1, 5, 6, 7] as $weekday) {
        $overrides["*switcher=GetSearchResults*data_field_key=id_bigint*lat_val=42.867970*weekdays={$weekday}*"] = $tuesday;
    }
    FakeHttp::install($overrides);
    config([
        'twilio.fake_mode' => true,
        'twilio.disable_signature_validation' => false,
        'app.url' => 'http://localhost',
    ]);

    // AppServiceProvider decided at boot that fake mode was off too: capture
    // email, recording downloads and the readings now. config.test.php keys its
    // SMTP settings off the environment variable a fake-mode server runs with.
    FakeTwilioMode::install(app());
    putenv('TWILIO_FAKE_MODE=true');

    // routes/web.php decided at boot that fake mode was off; mount the routes now.
    Route::middleware(['web', 'twilio.signature'])->group(base_path('routes/fake-twilio.php'));
    app('router')->getRoutes()->refreshNameLookups();

    app()->instance(Transport::class, new KernelTransport());
});

afterEach(function () {
    putenv('TWILIO_FAKE_MODE');
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

/**
 * Run yap:simulate and return its output, failing with that output (which
 * explains what the simulator expected) unless every scenario passed.
 */
function simulate(array $arguments = []): string
{
    $status = Artisan::call('yap:simulate', $arguments);
    $output = Artisan::output();
    expect($status)->toBe(0, $output);

    return $output;
}

test('a scenario that configures nothing passes', function (string $scenario) {
    expect(simulate(['scenarios' => [$scenario]]))->toContain("PASS {$scenario}");
})->with([
    'meeting-search-options',
    'voice-location-search',
    'language-selector',
    'post-call-sms',
    'readings',
    'custom-extensions',
    'blocklist',
    'no-coverage-fallback',
]);

test('a scenario that seeds the helpline passes', function (string $scenario) {
    expect(simulate(['scenarios' => [$scenario], '--service-body' => '1006', '--seed' => true]))
        ->toContain("PASS {$scenario}");
})->with([
    'gender-routing',
    'call-blasting',
    'caller-hangup-before-accept',
    'voicemail-notifications',
    'dialback',
    'sms-helpline',
]);

test('voicemail email is captured with the recording, downloaded with Basic Auth', function () {
    simulate(['scenarios' => ['voicemail-notifications'], '--service-body' => '1006', '--seed' => true]);

    $store = new FakeTwilioStore();
    $email = $store->requests(0, FakeMailer::RESOURCE)[0]['data'];
    expect($email['to'])->toBe(['voicemail@example.org'])
        ->and($email['subject'])->toStartWith('Helpline Voicemail from ')
        ->and($email['body'])->toContain('/media?recording=')
        ->and($email['attachments'][0]['bytes'])->toBe(strlen(\App\Http\Controllers\MediaController::FAKE_RECORDING_BODY));

    $download = collect($store->requests(0, 'recordings.media'))->first(fn ($request) => $request['data'] !== []);
    expect($download['url'])->toEndWith('.mp3')
        ->and($download['data'])->toBe([
            'auth' => 'basic',
            'username' => app(SettingsService::class)->get('twilio_account_sid'),
            'credentials_match' => true,
        ]);
});

test('--list shows every scenario', function () {
    $output = simulate(['--list' => true]);

    expect(ScenarioRegistry::all())->toHaveCount(17);
    foreach (ScenarioRegistry::all() as $name => $scenario) {
        expect($output)->toMatch('/^\s+' . preg_quote($name, '/') . '\s+' . preg_quote($scenario->description(), '/') . '$/m');
    }
});

test('running every scenario skips the ones that need a service body or --seed', function () {
    $output = simulate();

    foreach (['helpline-volunteer', 'gender-routing', 'call-blasting', 'caller-hangup-before-accept', 'voicemail-notifications', 'dialback'] as $helpline) {
        expect($output)->toMatch("/^SKIP {$helpline}: pass --service-body/m");
    }
    expect($output)->toMatch('/^SKIP sms-helpline: pass --seed/m');
    foreach (['meeting-search', 'sms-meeting-search', 'meeting-search-options', 'readings', 'blocklist'] as $passes) {
        expect($output)->toMatch("/^PASS {$passes}$/m");
    }
});

test('a scenario that needs --seed fails when named without it', function () {
    $this->artisan('yap:simulate', ['scenarios' => ['call-blasting'], '--service-body' => '1006'])
        ->expectsOutputToContain('FAIL call-blasting')
        ->expectsOutputToContain('pass --seed')
        ->assertFailed();
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
