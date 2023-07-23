<?php

use App\Constants\AlertId;
use App\Constants\DataType;
use App\Repositories\ConfigRepository;
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

test('initial callin with service body override', function () {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers_and_sms\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"https://fake.mp3\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    $repository->shouldReceive("getAllDbData")->with(
        DataType::YAP_CONFIG_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[]"
    ]])->times(2);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call("GET", '/', [
        "override_service_body_id"=>44
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Play>https://fake.mp3</Play>',
            '</Gather>',
            '</Response>',
            ], false);
});

test('initial call-in extension dial', function () {
    $_SESSION['override_extension_dial'] = true;
    $response = $this->get('/');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" finishOnKey="#" timeout="10" action="service-body-ext-response.php" method="GET">',
            '<Say>Enter the service body ID, followed by the pound sign.</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});
