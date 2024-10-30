<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Services\ConferenceService;
use App\Services\RootServerService;
use App\Structures\Coordinates;
use App\Structures\ServiceBodyCallHandling;
use Illuminate\Testing\Assert;
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

    $this->midddleware = new MiddlewareTests();
    $this->rootServerMocks = new RootServerMocks();
    $this->callSid = "abc123";
    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
});

test('force number', function ($method) {
    $forcedNumber = '+19998887777';
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => $forcedNumber,
        'CallSid' => $this->callSid
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial>',
            '<Number sendDigits="w">+19998887777</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('force number wth captcha', function ($method) {
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Captcha' => "1",
        'ForceNumber' => '+19998887777',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" timeout="15" numDigits="1" action="helpline-search.php?CaptchaVerified=1&amp;ForceNumber=%2B19998887777&amp;ysk=fake">',
            '<Say voice="alice" language="en-US">Test Helpline...press any key to continue</Say>',
            '</Gather>',
            '<Hangup/>',
            '<Dial>',
            '<Number sendDigits="w">+19998887777</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('force number wth captcha w/waiting message querystring setting', function ($method) {
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Captcha' => "1",
        'WaitingMessage' => "1",
        'ForceNumber' => '+19998887777',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" timeout="15" numDigits="1" action="helpline-search.php?CaptchaVerified=1&amp;ForceNumber=%2B19998887777&amp;ysk=fake&amp;WaitingMessage=1">',
            '<Say voice="alice" language="en-US">Test Helpline...press any key to continue</Say>',
            '</Gather>',
            '<Hangup/>',
            '<Dial>',
            '<Number sendDigits="w">+19998887777</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('invalid address', function ($method) {
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+an+address+for+that+location.</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('Unable to find service body coverage for a location.', function ($method) {
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Brooklyn, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+service+body+coverage+for+that+location.</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer routing, by location', function ($method) {
    $serviceBodyId = 1060; // Buffalo Area
    $parentServiceBodyId = 1059; // Western New York Region

    $conferenceService = Mockery::mock(ConferenceService::class)->makePartial();
    $conferenceService->shouldReceive("getConferenceName")
        ->withArgs(['1060'])
        ->andReturn("1060_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling($serviceBodyId, $serviceBodyCallHandlingData);

    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Buffalo, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical" statusCallback="helpline-dialer.php?service_body_id=1060&amp;Caller=+12125551212&amp;ysk=fake" startConferenceOnEnter="false" endConferenceOnExit="true" statusCallbackMethod="GET" statusCallbackEvent="start join end leave" waitMethod="GET" beep="false">1060_fake_conference</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);


test('valid search, volunteer routing', function ($method) {
    $conferenceService = Mockery::mock(ConferenceService::class)->makePartial();
    $conferenceService->shouldReceive("getConferenceName")
        ->withArgs(['1053'])
        ->andReturn("1053_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        "CallSid"=>$this->callSid,
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical" statusCallback="helpline-dialer.php?service_body_id=1053&amp;Caller=+12125551212&amp;ysk=fake" startConferenceOnEnter="false" endConferenceOnExit="true" statusCallbackMethod="GET" statusCallbackEvent="start join end leave" waitMethod="GET" beep="false">',
            '1053_fake_conference',
            '</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search with coordinates override, volunteer routing, announce service body name', function ($method) {
    $latitude = "40.912252";
    $longitude = "-72.665590";
    $location = "Geneva, NY";
    $dialedNumber = "631-689-6262";
    $_SESSION['override_custom_geocoding'] = [
        ['location' => $location, 'latitude' => $latitude, 'longitude' => $longitude]
    ];

    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Geneva, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'CallSid'=>$this->callSid
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please stand by... relocating your call to... Eastern Long Island Area Service</Say>',
            '<Dial>',
            '<Number sendDigits="w">'.$dialedNumber.'</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer routing, announce service body name', function ($method) {
    $conferenceService = Mockery::mock(ConferenceService::class)->makePartial();
    $conferenceService->shouldReceive("getConferenceName")
        ->withArgs(['1053'])
        ->andReturn("1053_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['override_announce_servicebody_volunteer_routing'] = true;

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Geneva, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'CallSid'=>$this->callSid
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please stand by... relocating your call to Finger Lakes Area Service</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical" statusCallback="helpline-dialer.php?service_body_id=1053&amp;Caller=+12125551212&amp;ysk=fake" startConferenceOnEnter="false" endConferenceOnExit="true" statusCallbackMethod="GET" statusCallbackEvent="start join end leave" waitMethod="GET" beep="false">',
            '1053_fake_conference',
            '</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, helpline field routing', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Geneva, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'CallSid' => $this->callSid,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please stand by... relocating your call to... Finger Lakes Area Service</Say>',
            '<Dial><Number sendDigits="ww1">888-557-1667</Number></Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search with address, volunteer gender routing enabled and choice not selected so far', function ($method) {
    $coordinates = new Coordinates();
    $coordinates->latitude = 42.8361156;
    $coordinates->longitude =-76.9873477;
    $coordinates->location = "Geneva, NY 14456, USA";
    $meta_as_json = json_encode((object)['gather' => '14456', 'coordinates' => $coordinates]);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    // TODO: clean this class up so that certain things are encapsulated versus now
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->gender_routing = true;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $address = "14456";
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => $address,
        'SearchType' => "1",
        "CallSid"=>$this->callSid,
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">gender-routing.php?SearchType=1</Redirect>',
            '</Response>'
        ], false);

    Assert::assertTrue($_SESSION['Address'] == $address);
})->with(['GET', 'POST']);

test('valid search, helpline field routing, no helpline set in root server, use fallback number', function ($method) {
    $fallback_number = '+15551112223';
    $_SESSION['override_fallback_number'] = $fallback_number;
    $rootServer = new RootServerMocks(true);
    app()->instance(RootServerService::class, $rootServer->getService());
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Brooklyn, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial><Number sendDigits="w">'.$fallback_number.'</Number></Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer direct', function ($method) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $conferenceService = Mockery::mock(ConferenceService::class)->makePartial();
    $conferenceService->shouldReceive("getConferenceName")
        ->withArgs(['46'])
        ->andReturn("46_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);

    // TODO: clean this class up so that certain things are encapsulated versus now
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_REDIRECT;
    $serviceBodyCallHandlingData->volunteers_redirect_id = 46;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $redirectedServiceBody = new ServiceBodyCallHandling();
    $redirectedServiceBody->volunteer_routing = VolunteerRoutingType::HELPLINE_FIELD;
    $redirectedServiceBody->service_body_id = 46;
    $redirectedServiceBody->volunteer_routing_enabled = true;

    ConfigData::createServiceBodyCallHandling(
        46,
        $redirectedServiceBody
    );

    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical" statusCallback="helpline-dialer.php?service_body_id=46&amp;Caller=+12125551212&amp;ysk=fake" startConferenceOnEnter="false" endConferenceOnExit="true" statusCallbackMethod="GET" statusCallbackEvent="start join end leave" waitMethod="GET" beep="false">',
            '46_fake_conference',
            '</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
