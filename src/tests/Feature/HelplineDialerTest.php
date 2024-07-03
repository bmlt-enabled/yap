<?php

use App\Constants\CallRole;
use App\Constants\CycleAlgorithm;
use App\Constants\EventId;
use App\Constants\TwilioCallStatus;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Models\ConferenceParticipant;
use App\Models\ConfigData;
use App\Models\RecordType;
use App\Models\ServiceBodyCallHandling;
use App\Models\VolunteerData;
use App\Repositories\ReportsRepository;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
    $this->conferenceName = "abc";
    $this->voicemail_url = 'https://example.org/voicemail.php';
    $this->callSid = 'abc';
    $this->serviceBodyId = "4400";
    $this->parentServiceBodyId = "43";
    $this->caller = "+17778889999";

    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);
});

test('noop', function ($method) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET" && $data['url'] == $this->voicemail_url;
        }))->once();
    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->status = TwilioCallStatus::INPROGRESS;
    $callContextMock->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);
    $this->twilioClient->calls = $callContextMock;

    $response = $this->call($method, '/helpline-dialer.php', [
        'noop' => "1",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
        'FriendlyName' => $this->conferenceName,
        'CallStatus' => TwilioCallStatus::NOANSWER,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);

test('debug messages', function ($method) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;


    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );

    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = "(555) 111-2222";
    $volunteer->volunteer_gender = $volunteer_gender;
    $volunteer->volunteer_responder = $volunteer_responder;
    $volunteer->volunteer_languages = $volunteer_languages;
    $volunteer->volunteer_notes = "";
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $response = $this->call($method, '/helpline-dialer.php', [
        'Debug' => "1",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
        'FriendlyName' => $this->conferenceName,
        'CallStatus' => 'no-answer'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);

test('do nothing', function ($method) {
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
        ))
        ->once();
    $this->twilioClient->conferences = $conferenceListMock;

    $response = $this->call($method, '/helpline-dialer.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
        'FriendlyName' => $this->conferenceName,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);

test('mark the caller as having entered the conference for reporting purposes', function ($method) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = $this->volunteer_phone_number;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;


    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );
    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_gender = $volunteer_gender;
    $volunteer->volunteer_responder = $volunteer_responder;
    $volunteer->volunteer_languages = $volunteer_languages;
    $volunteer->volunteer_notes = "";
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
        ))
        ->times(2);
    $this->twilioClient->conferences = $conferenceListMock;

    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")
        ->andReturn(json_decode(sprintf('[{"callSid":"%s"}]', $this->callSid)))
        ->once();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient
        ->shouldReceive("conferences")
        ->with($this->conferenceName)
        ->andReturn($conferenceContextMock)
        ->once();

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = "+15557770000";

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')
        ->with()
        ->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs([$volunteer_phone_number, $this->caller, [
            "method" => "GET",
            "url" => sprintf(
                "https://localhost/helpline-outdial-response.php?conference_name=%s&service_body_id=%s&ysk=fake",
                $this->conferenceName,
                $this->serviceBodyId
            ),
            "statusCallback" => sprintf(
                "https://localhost/helpline-dialer.php?service_body_id=%s&tracker=1&FriendlyName=%s&OriginalCallerId=%s&ysk=fake",
                $this->serviceBodyId,
                $this->conferenceName,
                $this->caller
            ),
            "statusCallbackEvent" => TwilioCallStatus::COMPLETED,
            "statusCallbackMethod" => "GET",
            "timeout" => 20,
            "callerId" => $this->caller,
            "originalCallerId" => $this->caller,
        ]]);
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] == $this->caller && !empty($data['body'][0]);
        }));

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$this->callSid,
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Caller' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'StatusCallbackEvent' => 'participant-join',
        'SequenceNumber' => 1
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);

test('mark the caller as having entered the conference for reporting purposes, with sms dialback', function ($method) {
    $settings = new SettingsService();
    $settings->set("sms_dialback_options", 1);
    app()->instance(SettingsService::class, $settings);

    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = $this->volunteer_phone_number;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );
    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_gender = $volunteer_gender;
    $volunteer->volunteer_responder = $volunteer_responder;
    $volunteer->volunteer_languages = $volunteer_languages;
    $volunteer->volunteer_notes = "";
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
        ))
        ->times(2);
    $this->twilioClient->conferences = $conferenceListMock;

    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")
        ->andReturn(json_decode(sprintf('[{"callSid":"%s"}]', $this->callSid)))
        ->once();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient
        ->shouldReceive("conferences")
        ->with($this->conferenceName)
        ->andReturn($conferenceContextMock)
        ->once();

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = "+15557770000";

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')
        ->with()
        ->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs([$volunteer_phone_number, $this->caller, [
            "method" => "GET",
            "url" => sprintf(
                "https://localhost/helpline-outdial-response.php?conference_name=%s&service_body_id=%s&ysk=fake",
                $this->conferenceName,
                $this->serviceBodyId
            ),
            "statusCallback" => sprintf(
                "https://localhost/helpline-dialer.php?service_body_id=%s&tracker=1&FriendlyName=%s&OriginalCallerId=%s&ysk=fake",
                $this->serviceBodyId,
                $this->conferenceName,
                $this->caller
            ),
            "statusCallbackEvent" => TwilioCallStatus::COMPLETED,
            "statusCallbackMethod" => "GET",
            "timeout" => 20,
            "callerId" => $this->caller,
            "originalCallerId" => $this->caller,
        ]]);
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] == $this->caller && !empty($data['body'][0]);
        }));

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$this->callSid,
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Caller' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'StatusCallbackEvent' => 'participant-join',
        'SequenceNumber' => 1
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);

test('an invalid or busy outgoing call happens with linear and voicemail and one volunteer', function ($method, $twilioCallStatus) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = $this->volunteer_phone_number;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );
    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_gender = $volunteer_gender;
    $volunteer->volunteer_responder = $volunteer_responder;
    $volunteer->volunteer_languages = $volunteer_languages;
    $volunteer->volunteer_notes = "";
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
        ))
        ->times(2);
    $this->twilioClient->conferences = $conferenceListMock;

    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")
        ->andReturn(json_decode(sprintf('[{"callSid":"%s"}]', $this->callSid)))
        ->once();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient
        ->shouldReceive("conferences")
        ->with($this->conferenceName)
        ->andReturn($conferenceContextMock)
        ->once();

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = "+15557770000";

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')
        ->with()
        ->andReturn($callInstance);

    // mocking TwilioRestClient->calls()->update();
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET"
                && $data['url'] == "https://localhost/voicemail.php?service_body_id=4400&caller_id=0000000000&ysk=fake&caller_number=+15557770000";
        }));
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs([$volunteer_phone_number, $this->caller, [
            "method" => "GET",
            "url" => sprintf(
                "https://localhost/helpline-outdial-response.php?conference_name=%s&service_body_id=%s&ysk=fake",
                $this->conferenceName,
                $this->serviceBodyId
            ),
            "statusCallback" => sprintf(
                "https://localhost/helpline-dialer.php?service_body_id=%s&tracker=1&FriendlyName=%s&OriginalCallerId=%s&ysk=fake",
                $this->serviceBodyId,
                $this->conferenceName,
                $this->caller
            ),
            "statusCallbackEvent" => TwilioCallStatus::COMPLETED,
            "statusCallbackMethod" => "GET",
            "timeout" => 20,
            "callerId" => $this->caller,
            "originalCallerId" => $this->caller,
        ]]);
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] == $this->caller && !empty($data['body'][0]);
        }));

    $eventId = 0;
    $payload = "";
    if ($twilioCallStatus === TwilioCallStatus::FAILED) {
        $eventId = EventId::VOLUNTEER_NUMBER_BAD;
        $payload = ["to_number"=>$this->volunteer_phone_number, "error"=>"invalid phone number"];
    } elseif ($twilioCallStatus === TwilioCallStatus::BUSY) {
        $eventId = EventId::VOLUNTEER_NUMBER_BUSY;
        $payload = ["to_number"=>$this->volunteer_phone_number];
    }

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$this->callSid,
        'Called'=>$this->volunteer_phone_number,
        'OriginalCallerId' => "19193559674",
        'FriendlyName' => $this->conferenceName,
        'Direction' => 'outbound-api',
        'CallStatus' => $twilioCallStatus,
        'CallbackSource' => 'call-progress-events',
        'ErrorMessage' => 'invalid phone number',
        'tracker' => 1
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST'], [TwilioCallStatus::FAILED, TwilioCallStatus::BUSY]);

test('an invalid or busy outgoing call happens with linear and voicemail and two volunteers', function ($method, $twilioCallStatus) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = $this->volunteer_phone_number;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $volunteer_name_2 = "Rocky";
    $this->volunteer_phone_number_2 = "(212) 555-5555";

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );
    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer1 = new VolunteerData();
    $volunteer1->volunteer_name = $volunteer_name;
    $volunteer1->volunteer_phone_number = $this->volunteer_phone_number;
    $volunteer1->volunteer_gender = $volunteer_gender;
    $volunteer1->volunteer_responder = $volunteer_responder;
    $volunteer1->volunteer_languages = $volunteer_languages;
    $volunteer1->volunteer_notes = "";
    $volunteer1->volunteer_enabled = true;
    $volunteer1->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    $volunteer2 = new VolunteerData();
    $volunteer2->volunteer_name = $volunteer_name_2;
    $volunteer2->volunteer_phone_number = $this->volunteer_phone_number_2;
    $volunteer2->volunteer_gender = $volunteer_gender;
    $volunteer2->volunteer_responder = $volunteer_responder;
    $volunteer2->volunteer_languages = $volunteer_languages;
    $volunteer2->volunteer_notes = "";
    $volunteer2->volunteer_enabled = true;
    $volunteer2->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteers(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        [$volunteer1, $volunteer2],
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
        ))
        ->times(2);
    $this->twilioClient->conferences = $conferenceListMock;

    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")
        ->andReturn(json_decode(sprintf('[{"callSid":"%s"}]', $this->callSid)))
        ->once();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient
        ->shouldReceive("conferences")
        ->with($this->conferenceName)
        ->andReturn($conferenceContextMock)
        ->once();

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = "+15557770000";

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')
        ->with()
        ->andReturn($callInstance);

    // mocking TwilioRestClient->calls()->update();
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET"
                && $data['url'] == "https://localhost/voicemail.php?service_body_id=4400&caller_id=0000000000&ysk=fake&caller_number=+15557770000";
        }));
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList');
    $callCreateInstance->shouldReceive('create')
        ->withArgs([$this->volunteer_phone_number_2, $this->caller, [
            "method" => "GET",
            "url" => sprintf(
                "https://localhost/helpline-outdial-response.php?conference_name=%s&service_body_id=%s&ysk=fake",
                $this->conferenceName,
                $this->serviceBodyId
            ),
            "statusCallback" => sprintf(
                "https://localhost/helpline-dialer.php?service_body_id=%s&tracker=2&FriendlyName=%s&OriginalCallerId=%s&ysk=fake",
                $this->serviceBodyId,
                $this->conferenceName,
                $this->caller
            ),
            "statusCallbackEvent" => TwilioCallStatus::COMPLETED,
            "statusCallbackMethod" => "GET",
            "timeout" => 20,
            "callerId" => $this->caller,
            "originalCallerId" => $this->caller,
        ]])->once();
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] == $this->caller && !empty($data['body'][0]);
        }));

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$this->callSid,
        'Called'=>$this->volunteer_phone_number,
        'Caller' => $this->caller,
        'OriginalCallerId' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'Direction' => 'outbound-api',
        'CallStatus' => $twilioCallStatus,
        'CallbackSource' => 'call-progress-events',
        'ErrorMessage' => 'invalid phone number',
        'tracker' => 1
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST'], [TwilioCallStatus::FAILED, TwilioCallStatus::BUSY]);

test('an invalid or busy outgoing call happens with blasting and voicemail and one volunteer', function ($method, $twilioCallStatus) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = $this->volunteer_phone_number;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::BLASTING;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );
    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $this->volunteer_phone_number;
    $volunteer->volunteer_gender = $volunteer_gender;
    $volunteer->volunteer_responder = $volunteer_responder;
    $volunteer->volunteer_languages = $volunteer_languages;
    $volunteer->volunteer_notes = "";
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $this->twilioClient->conferences = $conferenceListMock;

    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $conferenceContextMock->participants = $participantListMock;

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = "+15557770000";
    $callInstance->status = TwilioCallStatus::INPROGRESS;

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')
        ->with()
        ->andReturn($callInstance);

    // mocking TwilioRestClient->calls()->update();
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET"
                && $data['url'] == "https://example.org/voicemail.php";
        }));
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs([$volunteer_phone_number, $this->caller, [
            "method" => "GET",
            "url" => sprintf(
                "https://localhost/helpline-outdial-response.php?conference_name=%s&service_body_id=%s&ysk=fake",
                $this->conferenceName,
                $this->serviceBodyId
            ),
            "statusCallback" => sprintf(
                "https://localhost/helpline-dialer.php?service_body_id=%s&tracker=1&FriendlyName=%s&OriginalCallerId=%s&ysk=fake",
                $this->serviceBodyId,
                $this->conferenceName,
                $this->caller
            ),
            "statusCallbackEvent" => TwilioCallStatus::COMPLETED,
            "statusCallbackMethod" => "GET",
            "timeout" => 20,
            "callerId" => $this->caller,
            "originalCallerId" => $this->caller,
        ]]);
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] == $this->caller && !empty($data['body'][0]);
        }));

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$this->callSid,
        'Called'=>$this->volunteer_phone_number,
        'OriginalCallerId' => "19193559674",
        'FriendlyName' => $this->conferenceName,
        'Direction' => 'outbound-api',
        'CallStatus' => $twilioCallStatus,
        'CallbackSource' => 'call-progress-events',
        'ErrorMessage' => 'invalid phone number',
        'tracker' => 1,
        'noop' => "1",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST'], [TwilioCallStatus::FAILED, TwilioCallStatus::BUSY]);

test('an invalid or busy outgoing call happens with blasting and voicemail and two volunteers', function ($method, $twilioCallStatus) {
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = $this->volunteer_phone_number;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $volunteer_name_2 = "Rocky";
    $this->volunteer_phone_number_2 = "(212) 555-5555";

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::BLASTING;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );
    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
        ];
    }

    $volunteer1 = new VolunteerData();
    $volunteer1->volunteer_name = $volunteer_name;
    $volunteer1->volunteer_phone_number = $this->volunteer_phone_number;
    $volunteer1->volunteer_gender = $volunteer_gender;
    $volunteer1->volunteer_responder = $volunteer_responder;
    $volunteer1->volunteer_languages = $volunteer_languages;
    $volunteer1->volunteer_notes = "";
    $volunteer1->volunteer_enabled = true;
    $volunteer1->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    $volunteer2 = new VolunteerData();
    $volunteer2->volunteer_name = $volunteer_name_2;
    $volunteer2->volunteer_phone_number = $this->volunteer_phone_number_2;
    $volunteer2->volunteer_gender = $volunteer_gender;
    $volunteer2->volunteer_responder = $volunteer_responder;
    $volunteer2->volunteer_languages = $volunteer_languages;
    $volunteer2->volunteer_notes = "";
    $volunteer2->volunteer_enabled = true;
    $volunteer2->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteers(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        [$volunteer1, $volunteer2],
    );

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $this->twilioClient->conferences = $conferenceListMock;

    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $conferenceContextMock->participants = $participantListMock;

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = "+15557770000";
    $callInstance->status = TwilioCallStatus::INPROGRESS;

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')
        ->with()
        ->andReturn($callInstance);

    // mocking TwilioRestClient->calls()->update();
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET"
                && $data['url'] == "https://example.org/voicemail.php";
        }));
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs([$volunteer_phone_number, $this->caller, [
            "method" => "GET",
            "url" => sprintf(
                "https://localhost/helpline-outdial-response.php?conference_name=%s&service_body_id=%s&ysk=fake",
                $this->conferenceName,
                $this->serviceBodyId
            ),
            "statusCallback" => sprintf(
                "https://localhost/helpline-dialer.php?service_body_id=%s&tracker=1&FriendlyName=%s&OriginalCallerId=%s&ysk=fake",
                $this->serviceBodyId,
                $this->conferenceName,
                $this->caller
            ),
            "statusCallbackEvent" => TwilioCallStatus::COMPLETED,
            "statusCallbackMethod" => "GET",
            "timeout" => 20,
            "callerId" => $this->caller,
            "originalCallerId" => $this->caller,
        ]]);
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] == $this->caller && !empty($data['body'][0]);
        }));

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$this->callSid,
        'Called'=>$this->volunteer_phone_number,
        'OriginalCallerId' => "19193559674",
        'FriendlyName' => $this->conferenceName,
        'Direction' => 'outbound-api',
        'CallStatus' => $twilioCallStatus,
        'CallbackSource' => 'call-progress-events',
        'ErrorMessage' => 'invalid phone number',
        'tracker' => 1,
        'noop' => "1",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST'], [TwilioCallStatus::FAILED, TwilioCallStatus::BUSY]);

test('caller leaves the call', function ($method) {
    $callsid = "CA460d1728a3e07606f36aaa8879a7fbd3";
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $callsid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn([])
        ->times(10);
    $this->twilioClient->conferences = $conferenceListMock;

    ConferenceParticipant::create([
       "conferencesid"=>"abc123",
       "callsid"=>$callsid,
       "friendlyname"=>$this->conferenceName,
       "role"=>CallRole::VOLUNTEER
    ]);
    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$callsid,
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Caller' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'StatusCallbackEvent' => 'participant-leave',
        'SequenceNumber' => 2
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);

test('volunteer leave the call', function ($method) {
    $callsid = "CA460d1728a3e07606f36aaa8879a7fbd2";
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $callsid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $this->volunteer_phone_number = "(732) 566-5232";

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn([])
        ->times(10);
    $this->twilioClient->conferences = $conferenceListMock;

    ConferenceParticipant::create([
        "conferencesid"=>"abc123",
        "callsid"=>$callsid,
        "friendlyname"=>$this->conferenceName,
        "role"=>CallRole::VOLUNTEER
    ]);

    $response = $this->call($method, '/helpline-dialer.php', [
        'CallSid'=>$callsid,
        'SearchType' => "1",
        'Called' => "+12125551212",
        'Caller' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'StatusCallbackEvent' => 'participant-leave',
        'SequenceNumber' => 2
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
})->with(['GET', 'POST']);
