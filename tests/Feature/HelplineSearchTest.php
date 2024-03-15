<?php

use App\Constants\EventId;
use App\Models\Coordinates;
use App\Models\RecordType;
use App\Repositories\ConfigRepository;
use App\Constants\DataType;
use App\Repositories\ReportsRepository;
use App\Services\CallService;
use App\Services\ConferenceService;
use App\Services\RootServerService;
use App\Services\SettingsService;
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
    $repository = Mockery::mock(ReportsRepository::class);
    $repository->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::VOLUNTEER_SEARCH_FORCE_DIALED, null, json_encode((object)['number'=>$forcedNumber]), RecordType::PHONE])
        ->once();
    $repository->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::HELPLINE_ROUTE, null, json_encode((object)['helpline_number'=>$forcedNumber,'extension'=>'w']), RecordType::PHONE])
        ->once();
    $repository->shouldReceive("insertSession")
        ->withArgs([$this->callSid])
        ->once();
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => $forcedNumber,
        'CallSid' => $this->callSid
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
    $repository = Mockery::mock(ReportsRepository::class);
    $repository->shouldReceive("insertCallRecord")->withAnyArgs();
    $repository->shouldReceive("insertCallEventRecord")->withAnyArgs();
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Captcha' => "1",
        'ForceNumber' => '+19998887777',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
    $repository = Mockery::mock(ReportsRepository::class);
    $repository->shouldReceive("insertCallRecord")->withAnyArgs();
    $repository->shouldReceive("insertCallEventRecord")->withAnyArgs();
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Captcha' => "1",
        'WaitingMessage' => "1",
        'ForceNumber' => '+19998887777',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+service+body+coverage+for+that+location.</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer routing, by location', function ($method) {
    $settings = new SettingsService();
    $settings->disableRandomConferences();
    app()->instance(SettingsService::class, $settings);
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Buffalo, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical" statusCallback="helpline-dialer.php?service_body_id=1060&amp;Caller=+12125551212&amp;ysk=fake" startConferenceOnEnter="false" endConferenceOnExit="true" statusCallbackMethod="GET" statusCallbackEvent="start join end leave" waitMethod="GET" beep="false">1060_static_room</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);


test('valid search, volunteer routing', function ($method) {
    $conferenceService = Mockery::mock(ConferenceService::class)->makePartial();
    $conferenceService->shouldReceive("getConferenceName")
        ->withArgs(['1053', true])
        ->andReturn("1053_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);
    $repository = Mockery::mock(ReportsRepository::class);
    $repository
        ->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::VOLUNTEER_SEARCH, $this->serviceBodyId, null, RecordType::PHONE])
        ->once();
    $repository
        ->shouldReceive("insertSession")
        ->withArgs([$this->callSid])
        ->once();
    app()->instance(ReportsRepository::class, $repository);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $configRepository = Mockery::mock(ConfigRepository::class);
    $configRepository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $configRepository);

    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        "CallSid"=>$this->callSid,
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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

    $repository = Mockery::mock(ReportsRepository::class);
    $repository
        ->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::VOLUNTEER_SEARCH, null, json_encode((object)["gather"=>$location,"coordinates"=>["location"=>$location,"latitude"=>$latitude,"longitude"=>$longitude]]), RecordType::PHONE])
        ->once();
    $repository
        ->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::HELPLINE_ROUTE, null, json_encode((object)["helpline_number"=>$dialedNumber,"extension"=>"w"]), RecordType::PHONE])
        ->once();

    $repository
        ->shouldReceive("insertSession")
        ->withArgs([$this->callSid])
        ->once();
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Geneva, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'CallSid'=>$this->callSid
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
        ->withArgs(['1053', true])
        ->andReturn("1053_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);

    $repository = Mockery::mock(ReportsRepository::class);
    $repository
        ->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::VOLUNTEER_SEARCH, $this->serviceBodyId, null, RecordType::PHONE])
        ->once();
    $repository
        ->shouldReceive("insertSession")
        ->withArgs([$this->callSid])
        ->once();
    app()->instance(ReportsRepository::class, $repository);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['override_announce_servicebody_volunteer_routing'] = true;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Geneva, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'CallSid'=>$this->callSid
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
    $repository = Mockery::mock(ReportsRepository::class);
            $repository->shouldReceive("insertCallEventRecord")
                ->withArgs([$this->callSid, EventId::VOLUNTEER_SEARCH, $this->serviceBodyId, null, RecordType::PHONE])
                ->once();
    $repository->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::HELPLINE_ROUTE, $this->serviceBodyId, json_encode((object)[
            "helpline_number"=>"888-557-1667",
            "extension"=>"ww1"
        ]), RecordType::PHONE])
        ->once();
    $repository->shouldReceive("insertSession")
        ->withArgs([$this->callSid])
        ->once();
    app()->instance(ReportsRepository::class, $repository);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"helpline_field\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Geneva, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'CallSid' => $this->callSid,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
    $repository = Mockery::mock(ReportsRepository::class);
    $repository
        ->shouldReceive("insertCallEventRecord")
        ->withArgs([$this->callSid, EventId::VOLUNTEER_SEARCH, null, $meta_as_json, RecordType::PHONE])
        ->once();
    $repository
        ->shouldReceive("insertSession")
        ->withArgs([$this->callSid])
        ->once();
    app()->instance(ReportsRepository::class, $repository);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"1\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);

    $address = "14456";
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => $address,
        'SearchType' => "1",
        "CallSid"=>$this->callSid,
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
    $this->withoutExceptionHandling();
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "Brooklyn, NY",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
        ->withArgs(['46', true])
        ->andReturn("46_fake_conference")
        ->once();
    app()->instance(ConferenceService::class, $conferenceService);

    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")
        ->once()
        ->with($this->serviceBodyId, DataType::YAP_CALL_HANDLING_V2)
        ->andReturn([(object)[
            "service_body_id" => $this->serviceBodyId,
            "id" => "200",
            "parent_id" => $this->parentServiceBodyId,
            "data" => "[{\"volunteer_routing\":\"volunteers_redirect\",\"volunteers_redirect_id\":\"46\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
        ]])

        ->shouldReceive("getDbData")
        ->once()
        ->with('46', DataType::YAP_CALL_HANDLING_V2)
        ->andReturn([(object)[
            "service_body_id" => "46",
            "id" => "200",
            "parent_id" => $this->parentServiceBodyId,
            "data" => "[{\"volunteer_routing\":\"helpline_field\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
        ]]);

    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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

//test('valid search, gender based routing', function () {
//    $rootServerService = $this->rootServerMocks->getService();
//    app()->instance(RootServerService::class, $rootServerService);
//    $_SESSION['Address'] = "27592";
//    $repository = Mockery::mock(ConfigRepository::class);
//    $repository->shouldReceive("getDbData")
//        ->once()
//        ->with('44', DataType::YAP_CALL_HANDLING_V2)
//        ->andReturn([(object)[
//            "service_body_id" => "44",
//            "id" => "200",
//            "parent_id" => "43",
//            "data" => "[{\"volunteer_routing\":\"volunteers_redirect\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"1\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
//        ]])
//
//        ->shouldReceive("getDbData")
//        ->once()
//        ->with('46', DataType::YAP_CALL_HANDLING_V2)
//        ->andReturn([(object)[
//            "service_body_id" => "46",
//            "id" => "200",
//            "parent_id" => "43",
//            "data" => "[{\"volunteer_routing\":\"helpline_field\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"1\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
//        ]]);
//
//    app()->instance(ConfigRepository::class, $repository);
//    $response = $this->call('GET', '/helpline-search.php', [
//        'Address' => "Raleigh, NC",
//        'SearchType' => "1",
//        'Called' => "+12125551212",
//    ]);
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
//        ->assertSeeInOrderExact([
/*            '<?xml version="1.0" encoding="UTF-8"?>',*/
//            '<Response>',
//            '<Redirect method="GET">',
//            'gender-routing.php?SearchType=1',
//            '</Redirect>',
//            '</Response>'
//        ], false);
//});
