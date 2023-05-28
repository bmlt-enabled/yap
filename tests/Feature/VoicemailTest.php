<?php

use App\Repositories\ConfigRepository;
use Tests\FakeTwilioHttpClient;
use App\Constants\DataType;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    new \Tests\TwilioMessagesCreateMock();
});

test('voicemail standard response', function () {
    $service_body_id = "44";
    $_SESSION['override_service_body_id'] = $service_body_id;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('GET', '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        "ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please leave a message after the tone, hang up when finished</Say>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
});

test('voicemail custom prompt', function () {
    $service_body_id = "44";
    $_SESSION['override_service_body_id'] = $service_body_id;
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);
    $_SESSION['override_en_US_voicemail_greeting'] = "https://example.org/test.mp3";
    $response = $this->call('GET', '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        "ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Play>https://example.org/test.mp3</Play>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
});
