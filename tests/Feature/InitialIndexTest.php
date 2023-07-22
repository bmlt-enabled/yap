<?php

use App\Constants\AlertId;
use App\Repositories\ReportsRepository;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->fakeCallSid = "abcdefghij";
    $this->middleware = new \Tests\MiddlewareTests();
    $this->reportsRepository = $this->middleware->insertSession($this->fakeCallSid);

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
});

test('initial call-in default', function () {
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
    ], false);
});

test('initial call-in with jft option enabled', function () {
    $_SESSION['override_jft_option'] = "true";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press three to listen to the just for today</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('initial call-in with spad option enabled', function () {
    $_SESSION['override_spad_option'] = "true";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '<Say voice="alice" language="en-US">',
            'press four to listen to the spiritual principle a day</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('initial call-in with jft and spad option enabled', function () {
    $_SESSION['override_jft_option'] = "true";
    $_SESSION['override_spad_option'] = "true";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
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
});

test('initial call-in default with language selections', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response><Redirect>lng-selector.php</Redirect></Response>',
        ], false);
});

test('selected language call flow', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $response = $this->call("GET", '/', [
        "Digits"=>"2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="es-US">presione uno para encontrar alguien con quien hablar</Say>',
            '<Say voice="alice" language="es-US">presione dos buscar reuniones</Say>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('play custom promptset', function () {
    $_SESSION['override_en_US_greeting'] = "https://example.org/fake.mp3";
    $response = $this->call("GET", '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('play custom promptset in a different language with selection menu', function () {
    $_SESSION['override_language_selections'] = "en-US,es-US";
    $_SESSION['override_es_US_greeting'] = "https://example.org/fake_es.mp3";
    $response = $this->call("GET", '/', [
        'Digits' => "2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake_es.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('play custom promptset in a different language with single forced language', function () {
    $_SESSION['override_gather_language'] = "es-US";
    $_SESSION['override_word_language'] = "es-US";
    $_SESSION['override_es_US_greeting'] = "https://example.org/fake_es.mp3";
    $response = $this->call("GET", '/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="es-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://example.org/fake_es.mp3</Play>',
            '</Gather>',
            '</Response>',
        ], false);
});

test('initial callin without a status callback', function () {
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

    $this->reportsRepository->shouldReceive("insertAlert")
        ->withArgs([AlertId::STATUS_CALLBACK_MISSING, $fakePhoneNumber])->once();

    app()->instance(TwilioService::class, $this->twilioService);
    app()->instance(ReportsRepository::class, $this->reportsRepository);

    $response = $this->call("GET", '/', [
        'CallSid'=>$this->fakeCallSid
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
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
});

test('initial callin without a status callback without actual status.php in it', function () {
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

    $this->reportsRepository->shouldReceive("insertAlert")
        ->withArgs([AlertId::STATUS_CALLBACK_MISSING, $fakePhoneNumber])->once();

    app()->instance(TwilioService::class, $this->twilioService);
    app()->instance(ReportsRepository::class, $this->reportsRepository);

    $response = $this->call("GET", '/', [
        'CallSid'=>$this->fakeCallSid
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
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
});

// TODO: load up some kind of service body configuration
//test('initial callin with service body override', function () {
//    $response = $this->call("GET", '/', [
//        "override_service_body_id"=>2
//    ]);
//
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
//        ->assertSeeInOrder([
/*            '<?xml version="1.0" encoding="UTF-8"?>',*/
//            '<Response>',
//            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
//            '<Pause length="2"/>',
//            '<Play>https://crossroadsarea.org/wp-content/uploads/9/2018/08/crossroads_v4_greeting.mp3</Play>',
//            '</Gather>',
//            '</Response>',
//            ], false);
//});
