<?php

use App\Constants\CycleAlgorithm;
use App\Constants\EventId;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Services\RootServerService;
use App\Structures\ServiceBodyCallHandling;
use Tests\CallScenario;
use Tests\FakeHttp;

const SCENARIO_UNSPECIFIED_NUMBER = '(555) 111-0000';
const SCENARIO_MALE_NUMBER = '(555) 111-0001';
const SCENARIO_FEMALE_NUMBER = '(555) 111-0002';

function scenarioVolunteer(string $name, string $phoneNumber, int $gender): array
{
    $shifts = [];
    foreach (range(1, 7) as $day) {
        $shifts[] = [
            'day' => $day,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
        ];
    }

    return [
        'volunteer_name' => $name,
        'volunteer_phone_number' => $phoneNumber,
        'volunteer_gender' => $gender,
        'volunteer_responder' => 0,
        'volunteer_notes' => '',
        'volunteer_enabled' => true,
        'volunteer_shift_schedule' => base64_encode(json_encode($shifts)),
    ];
}

beforeEach(function () {
    FakeHttp::install();

    $this->serviceBodyId = '4400';
    $this->caller = '+17325551212';
    $this->called = '+12125551212';

    $rootServer = mock(RootServerService::class)->makePartial();
    $rootServer->shouldReceive('getServiceBody')
        ->with($this->serviceBodyId)
        ->andReturn((object) [
            'id' => $this->serviceBodyId,
            'parent_id' => '4399',
            'name' => 'Scenario Test Area',
            'helpline' => '',
        ]);
    app()->instance(RootServerService::class, $rootServer);

    session()->put('override_service_body_id', $this->serviceBodyId);
    session()->put('override_disable_postal_code_gather', true);

    $callHandling = new ServiceBodyCallHandling();
    $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $callHandling->service_body_id = (int) $this->serviceBodyId;
    $callHandling->volunteer_routing_enabled = true;
    $callHandling->gender_routing = 1;
    $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling((int) $this->serviceBodyId, $callHandling);

    ConfigData::createVolunteers((int) $this->serviceBodyId, [
        scenarioVolunteer('Unspecified', SCENARIO_UNSPECIFIED_NUMBER, VolunteerGender::UNSPECIFIED),
        scenarioVolunteer('Male', SCENARIO_MALE_NUMBER, VolunteerGender::MALE),
        scenarioVolunteer('Female', SCENARIO_FEMALE_NUMBER, VolunteerGender::FEMALE),
    ]);
});

test('caller gender selection routes to the matching volunteer through the live call path', function () {
    $scenario = new CallScenario();

    $scenario
        ->startCall($this->caller, $this->called)
        ->pressDigits('1')
        ->followRedirect()
        ->followRedirect()
        ->pressDigits('2')
        ->followRedirect();

    expect($scenario->lastTwiml())->toDialConference();

    $scenario->joinConference();

    expect($scenario->twilio)->toHaveDialed(SCENARIO_FEMALE_NUMBER);

    $scenario
        ->answerVolunteer(1)
        ->assertHasEvent(EventId::VOLUNTEER_IN_CONFERENCE);
});
