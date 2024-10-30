<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Structures\ServiceBodyCallHandling;

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
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
        //"ysk" => "test"
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">please leave a message after the tone, hang up when finished</Say>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313&amp;ysk=fake" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voicemail custom prompt', function ($method) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['override_en_US_voicemail_greeting'] = "https://example.org/test.mp3";

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/voicemail.php', [
        "caller_id" => "+17325551212",
        "Caller" => "+12125551313",
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Play>https://example.org/test.mp3</Play>',
            '<Record playBeep="1" maxLength="120" timeout="15" recordingStatusCallback="voicemail-complete.php?service_body_id=44&amp;caller_id=%2B17325551212&amp;caller_number=%2B12125551313&amp;ysk=fake" recordingStatusCallbackMethod="GET"/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
