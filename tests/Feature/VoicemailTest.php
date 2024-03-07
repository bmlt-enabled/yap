<?php

use App\Repositories\ConfigRepository;
use Tests\FakeTwilioHttpClient;
use App\Constants\DataType;
use Tests\MiddlewareTests;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    new \Tests\TwilioMessagesCreateMock();
    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
    $this->data =  "{\"data\":{}}";
});

test('voicemail standard response', function ($method) {
    $service_body_id = $this->serviceBodyId;
    $_SESSION['override_service_body_id'] = $service_body_id;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call($method, '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        //"ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please leave a message after the tone, hang up when finished</Say>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voicemail custom prompt', function ($method) {
    $service_body_id = $this->serviceBodyId;
    $_SESSION['override_service_body_id'] = $service_body_id;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]]);
    app()->instance(ConfigRepository::class, $repository);
    $_SESSION['override_en_US_voicemail_greeting'] = "https://example.org/test.mp3";
    $response = $this->call($method, '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        //"ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Play>https://example.org/test.mp3</Play>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
