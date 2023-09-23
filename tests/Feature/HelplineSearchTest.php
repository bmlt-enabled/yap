<?php

use App\Repositories\ConfigRepository;
use App\Constants\DataType;
use App\Repositories\ReportsRepository;
use App\Services\RootServerService;
use App\Services\SettingsService;
use Tests\RootServerMocks;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->rootServerMocks = new RootServerMocks();
});

test('force number', function ($method) {
    $repository = Mockery::mock(ReportsRepository::class);
    $repository->shouldReceive("insertCallRecord")->withAnyArgs();
    $repository->shouldReceive("insertCallEventRecord")->withAnyArgs();
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial><Number sendDigits="w">+19998887777</Number>',
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
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" timeout="15" numDigits="1" action="helpline-search.php?CaptchaVerified=1&amp;ForceNumber=%2B19998887777">',
            '<Dial><Number sendDigits="w">+19998887777</Number>',
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
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" hints="" input="dtmf" timeout="15" numDigits="1" action="helpline-search.php?CaptchaVerified=1&amp;ForceNumber=%2B19998887777&amp;WaitingMessage=1">',
            '<Dial><Number sendDigits="w">+19998887777</Number>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('invalid entry', function ($method) {
    $response = $this->call($method, '/helpline-search.php', [
        'Digits' => "",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+an+address+for+that+location.</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer routing', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = 44;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical"',
            'statusCallback="helpline-dialer.php?service_body_id=44&amp;Caller=+12125551212"',
            'startConferenceOnEnter="false"',
            'endConferenceOnExit="true"',
            'statusCallbackMethod="GET"',
            'statusCallbackEvent="start join end leave"',
            'waitMethod="GET"',
            'beep="false">',
            '</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer routing, announce service body name', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = 44;
    $_SESSION['override_announce_servicebody_volunteer_routing'] = true;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please stand by... relocating your call to Crossroads Area</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical"',
            'statusCallback="helpline-dialer.php?service_body_id=44&amp;Caller=+12125551212"',
            'startConferenceOnEnter="false"',
            'endConferenceOnExit="true"',
            'statusCallbackMethod="GET"',
            'statusCallbackEvent="start join end leave"',
            'waitMethod="GET"',
            'beep="false">',
            '</Conference>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, helpline field routing', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = 44;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"helpline_field\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please stand by... relocating your call to... Crossroads Area</Say>',
            '<Dial><Number sendDigits="ww1">888-557-1667</Number></Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, helpline field routing, no helpline set in root server, use fallback number', function ($method) {
    $rootServerMocksWithNoHelplineField = new RootServerMocks(true);
    app()->instance(RootServerService::class, $rootServerMocksWithNoHelplineField->getService());
    $_SESSION['override_service_body_id'] = 44;
    $_SESSION['override_fallback_number'] = '+15551112223';
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"helpline_field\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please stand by... relocating your call to... Crossroads Area</Say>',
            '<Dial><Number sendDigits="w">+15551112223</Number></Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('valid search, volunteer direct', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = 44;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")
        ->once()
        ->with('44', DataType::YAP_CALL_HANDLING_V2)
        ->andReturn([(object)[
            "service_body_id" => "44",
            "id" => "200",
            "parent_id" => "43",
            "data" => "[{\"volunteer_routing\":\"volunteers_redirect\",\"volunteers_redirect_id\":\"46\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
        ]])

        ->shouldReceive("getDbData")
        ->once()
        ->with('46', DataType::YAP_CALL_HANDLING_V2)
        ->andReturn([(object)[
            "service_body_id" => "46",
            "id" => "200",
            "parent_id" => "43",
            "data" => "[{\"volunteer_routing\":\"helpline_field\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
        ]]);

    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please wait while we connect your call</Say>',
            '<Dial>',
            '<Conference waitUrl="https://twimlets.com/holdmusic?Bucket=com.twilio.music.classical" statusCallback="helpline-dialer.php?service_body_id=46&amp;Caller=+12125551212" startConferenceOnEnter="false" endConferenceOnExit="true" statusCallbackMethod="GET" statusCallbackEvent="start join end leave" waitMethod="GET" beep="false">',
            '</Conference></Dial>',
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
//        ->assertSeeInOrder([
/*            '<?xml version="1.0" encoding="UTF-8"?>',*/
//            '<Response>',
//            '<Redirect method="GET">',
//            'gender-routing.php?SearchType=1',
//            '</Redirect>',
//            '</Response>'
//        ], false);
//});
