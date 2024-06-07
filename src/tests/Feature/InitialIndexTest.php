<?php

use App\Constants\AlertId;
use App\Constants\SearchType;
use App\Constants\VolunteerRoutingType;
use App\Models\Alert;
use App\Models\ConfigData;
use App\Models\ServiceBodyCallHandling;
use App\Repositories\ReportsRepository;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;
use Tests\MiddlewareTests;
use Tests\RootServerMocks;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->fakeCallSid = "abcdefghij";
    $this->middleware = new MiddlewareTests();
    $this->rootServerMocks = new RootServerMocks();
    $this->reportsRepository = $this->middleware->insertSession($this->fakeCallSid);

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();

    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
});

test('initial call-in default', function ($method) {
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
    ], false);
})->with(['GET', 'POST']);

test('initial call-in default with lengthier initial pause', function ($method) {
    $_SESSION["override_initial_pause"] = 5;
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="5"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('initial call-in default with altered menu options', function ($method) {
    $_SESSION['override_digit_map_search_type'] = [
        '3' => SearchType::MEETINGS,
        '4' => SearchType::VOLUNTEERS,
    ];
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press three to search for meetings</Say>',
            '<Say voice="alice" language="en-US">press four to find someone to talk to</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);



test('initial call-in default after going to the admin page', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $this->call('GET', '/admin');

    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('initial call-in with jft option enabled', function ($method) {
    $_SESSION['override_jft_option'] = "true";
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press three to listen to the just for today</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('initial call-in with spad option enabled', function ($method) {
    $_SESSION['override_spad_option'] = "true";
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press four to listen to the spiritual principle a day</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('initial call-in with jft and spad option enabled', function ($method) {
    $_SESSION['override_jft_option'] = "true";
    $_SESSION['override_spad_option'] = "true";
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press three to listen to the just for today</Say>',
            '<Say voice="alice" language="en-US">',
            'press four to listen to the spiritual principle a day</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('initial call-in default with language selections', function ($method) {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect>lng-selector.php</Redirect></Response>',
        ], false);
})->with(['GET', 'POST']);

test('selected language call flow', function ($method) {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->call($method, '/', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="es-US">presione uno para encontrar alguien con quien hablar</Say>',
            '<Say voice="alice" language="es-US">presione dos buscar reuniones</Say>',
            '</Gather>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('play custom promptset', function ($method) {
    $_SESSION['override_en_US_greeting'] = "https://example.org/fake.mp3";
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('play custom promptset in a different language with selection menu', function ($method) {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $_SESSION['override_es_US_greeting'] = "https://example.org/fake_es.mp3";
    $response = $this->call($method, '/', [
        'Digits' => "2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake_es.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('play custom promptset in a different language with single forced language', function ($method) {
    $_SESSION['override_gather_language'] = "es-US";
    $_SESSION['override_word_language'] = "es-US";
    $_SESSION['override_es_US_greeting'] = "https://example.org/fake_es.mp3";
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake_es.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('initial callin without a status callback', function ($method) {
    $settingsService = new SettingsService();
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);
    app()->instance(SettingsService::class, instance: $settingsService);
    $fakePhoneNumberSid = "fakePhoneNumberSid";
    $fakePhoneNumber = "5556661212";

    $this->twilioService->client()->shouldReceive('getAccountSid')->andReturn("123");
    // mocking TwilioRestClient->calls()->fetch()->phoneNumberSid
    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->startTime = new DateTime('2023-01-26T18:00:00');
    $callInstance->endTime = new DateTime('2023-01-26T18:15:00');
    $callInstance->phoneNumberSid = $fakePhoneNumberSid;

    $callContext = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContext->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $this->twilioService->client()->shouldReceive('calls')
        ->withArgs([$this->fakeCallSid])->andReturn($callContext)->once();

    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberContext');
    $incomingPhoneNumberInstance= mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumberInstance->statusCallback = null;
    $incomingPhoneNumberInstance->phoneNumber = $fakePhoneNumber;
    $incomingPhoneNumberContext->shouldReceive('fetch')->withNoArgs()
        ->andReturn($incomingPhoneNumberInstance)->once();

    // mocking TwilioRestClient->incomingPhoneNumbers()->fetch();
    $this->twilioService->client()->shouldReceive('incomingPhoneNumbers')
        ->withArgs([$fakePhoneNumberSid])->andReturn($incomingPhoneNumberContext)->once();

    Alert::createMisconfiguredPhoneNumberAlert($fakePhoneNumber);

    app()->instance(TwilioService::class, $this->twilioService);
    app()->instance(ReportsRepository::class, $this->reportsRepository);

    $response = $this->call($method, '/', [
        'CallSid'=>$this->fakeCallSid
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('initial callin without a status callback without actual status.php in it', function ($method) {
    $settingsService = new SettingsService();
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);
    app()->instance(SettingsService::class, instance: $settingsService);
    $fakePhoneNumberSid = "fakePhoneNumberSid";
    $fakePhoneNumber = "5556661212";

    $this->twilioService->client()->shouldReceive('getAccountSid')->andReturn("123");
    // mocking TwilioRestClient->calls()->fetch()->phoneNumberSid
    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->startTime = new DateTime('2023-01-26T18:00:00');
    $callInstance->endTime = new DateTime('2023-01-26T18:15:00');
    $callInstance->phoneNumberSid = $fakePhoneNumberSid;

    $callContext = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContext->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $this->twilioService->client()->shouldReceive('calls')
        ->withArgs([$this->fakeCallSid])->andReturn($callContext)->once();

    $incomingPhoneNumberContext = mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberContext');
    $incomingPhoneNumberInstance= mock('\Twilio\Rest\Api\V2010\Account\IncomingPhoneNumberInstance');
    $incomingPhoneNumberInstance->statusCallback = "blah.php";
    $incomingPhoneNumberInstance->phoneNumber = $fakePhoneNumber;
    $incomingPhoneNumberContext->shouldReceive('fetch')->withNoArgs()
        ->andReturn($incomingPhoneNumberInstance)->once();

    // mocking TwilioRestClient->incomingPhoneNumbers()->fetch();
    $this->twilioService->client()->shouldReceive('incomingPhoneNumbers')
        ->withArgs([$fakePhoneNumberSid])->andReturn($incomingPhoneNumberContext)->once();

    Alert::createMisconfiguredPhoneNumberAlert($fakePhoneNumber);

    app()->instance(TwilioService::class, $this->twilioService);
    app()->instance(ReportsRepository::class, $this->reportsRepository);

    $response = $this->call($method, '/', [
        'CallSid'=>$this->fakeCallSid
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>',
        ], false);
})->with(['GET', 'POST']);

test('initial callin with service body override', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $handling = new ServiceBodyCallHandling();
    $handling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $handling->override_en_US_greeting = "https://fake.mp3";

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $handling
    );

    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        new stdClass()
    );

    $response = $this->call($method, '/', [
        "override_service_body_id"=>$this->serviceBodyId
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://fake.mp3</Play>',
            '</Gather>',
            '</Response>',
            ], false);
})->with(['GET', 'POST']);

test('initial call-in extension dial', function ($method) {
    $_SESSION['override_extension_dial'] = true;
    $response = $this->call($method, '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" finishOnKey="#" timeout="10" action="service-body-ext-response.php" method="GET">',
            '<Say>Enter the service body ID, followed by the pound sign.</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
