<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Services\RootServerService;
use App\Structures\ServiceBodyCallHandling;
use Tests\CallScenario;
use Tests\FakeHttp;

const CALL_TOKEN_VOLUNTEER_NUMBER = '(555) 222-0001';

beforeEach(function () {
    FakeHttp::install();

    $this->serviceBodyId = '4400';
    $this->caller = '+17325551212';
    $this->called = '+12125551212';
    $this->callToken = 'CT' . bin2hex('shaken-stir-call-token-test');

    $rootServer = mock(RootServerService::class)->makePartial();
    $rootServer->shouldReceive('getServiceBody')
        ->with($this->serviceBodyId)
        ->andReturn((object) [
            'id' => $this->serviceBodyId,
            'parent_id' => '4399',
            'name' => 'Call Token Test Area',
            'helpline' => '',
        ]);
    app()->instance(RootServerService::class, $rootServer);

    session()->put('override_service_body_id', $this->serviceBodyId);
    session()->put('override_disable_postal_code_gather', true);

    $callHandling = new ServiceBodyCallHandling();
    $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $callHandling->service_body_id = (int) $this->serviceBodyId;
    $callHandling->volunteer_routing_enabled = true;
    $callHandling->gender_routing = 0;
    $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling((int) $this->serviceBodyId, $callHandling);

    $shifts = [];
    foreach (range(1, 7) as $day) {
        $shifts[] = [
            'day' => $day,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
        ];
    }

    ConfigData::createVolunteers((int) $this->serviceBodyId, [[
        'volunteer_name' => 'Token Test Volunteer',
        'volunteer_phone_number' => CALL_TOKEN_VOLUNTEER_NUMBER,
        'volunteer_gender' => VolunteerGender::UNSPECIFIED,
        'volunteer_responder' => 0,
        'volunteer_notes' => '',
        'volunteer_enabled' => true,
        'volunteer_shift_schedule' => base64_encode(json_encode($shifts)),
    ]]);
});

test('stores inbound CallToken in session and forwards it on volunteer outdial', function () {
    $scenario = new CallScenario();

    $scenario
        ->withCallData(['CallToken' => $this->callToken])
        ->startCall($this->caller, $this->called)
        ->pressDigits('1')
        ->followRedirect()
        ->followRedirect();

    expect($scenario->lastTwiml())->toDialConference();
    $scenario->joinConference();

    expect($scenario->twilio->lastOutboundCallOptions())
        ->toHaveKey('callToken', $this->callToken);
});

test('does not forward CallToken when forced caller ID is enabled', function () {
    $callHandling = new ServiceBodyCallHandling();
    $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $callHandling->service_body_id = (int) $this->serviceBodyId;
    $callHandling->volunteer_routing_enabled = true;
    $callHandling->gender_routing = 0;
    $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $callHandling->forced_caller_id_number = '+15551234567';

    ConfigData::createServiceBodyCallHandling((int) $this->serviceBodyId, $callHandling);

    $scenario = new CallScenario();

    $scenario
        ->withCallData(['CallToken' => $this->callToken])
        ->startCall($this->caller, $this->called)
        ->pressDigits('1')
        ->followRedirect()
        ->followRedirect();

    expect($scenario->lastTwiml())->toDialConference();
    $scenario->joinConference();

    expect($scenario->twilio->lastOutboundCallOptions())
        ->not->toHaveKey('callToken')
        ->and($scenario->twilio->lastOutboundCallOptions()['callerId'])->toBe('+15551234567');
});
