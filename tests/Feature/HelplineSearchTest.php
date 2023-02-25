<?php

use App\Repositories\ConfigRepository;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('force number', function () {
    $response = $this->call('GET', '/helpline-search.php', [
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
});


test('invalid entry', function () {
    $response = $this->call('GET', '/helpline-search.php', [
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
            '<Redirect method="GET">input-method.php?Digits=1&amp;Retry=1&amp;RetryMessage=Couldn%27t+find+an+address+for+that+location.</Redirect>',
            '</Response>'
        ], false);
});

test('valid search, volunteer routing', function () {
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
    $response = $this->call('GET', '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'stub_google_maps_endpoint' => true
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
});

test('valid search, helpline field routing', function () {
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
    $response = $this->call('GET', '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'stub_google_maps_endpoint' => true
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
});

test('valid search, volunteer direct', function () {
    $_SESSION['override_service_body_id'] = 44;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers_redirect\",\"volunteers_redirect_id\":\"46\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call('GET', '/helpline-search.php', [
        'Address' => "Raleigh, NC",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'stub_google_maps_endpoint' => true
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
});
