<?php

use App\Repositories\ConfigRepository;
use App\Constants\DataType;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Twilio\Version;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
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

    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);
});

test('noop', function () {
    $_SESSION['override_service_body_id'] = '44';
    $_SESSION['no_answer_max'] = 1;
    $_SESSION['master_callersid'] = $this->callSid;
    $_SESSION['voicemail_url'] = $this->voicemail_url;

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET" && $data['url'] == $this->voicemail_url;
        }))->once();
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);
    $this->twilioClient->calls = $callContextMock;

    $response = $this->call('GET', '/helpline-dialer.php', [
        'noop' => "1",
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
        'FriendlyName' => $this->conferenceName,
        'CallStatus' => 'no-answer',
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
});

test('do nothing', function () {
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

    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")
        ->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
        ))
        ->once();
    $this->twilioClient->conferences = $conferenceListMock;

    $_SESSION['override_service_body_id'] = '44';
    $response = $this->call('GET', '/helpline-dialer.php', [
        'SearchType' => "1",
        'Called' => "+12125551212",
        'ForceNumber' => '+19998887777',
        'FriendlyName' => $this->conferenceName,
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
});

// TODO: disabled until we refactor functions.php
//test('force number', function () {
//    $repository = Mockery::mock(ConfigRepository::class);
//    $repository->shouldReceive("getDbData")->with(
//        '44',
//        DataType::YAP_CALL_HANDLING_V2
//    )->andReturn([(object)[
//        "service_body_id" => "44",
//        "id" => "200",
//        "parent_id" => "43",
//        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
//    ]])->once();
//    $shifts = [];
//    for ($i = 1; $i <= 7; $i++) {
//        $shifts[] = [
//            "day" => $i,
//            "tz" => $shiftTz,
//            "start_time" => $shiftStart,
//            "end_time" => $shiftEnd,
//        ];
//    }
//
//    $volunteer = [[
//        "volunteer_name"=>$volunteer_name,
//        "volunteer_phone_number"=>"(555) 111-2222",
//        "volunteer_gender"=>$volunteer_gender,
//        "volunteer_responder"=>$volunteer_responder,
//        "volunteer_languages"=>$volunteer_languages,
//        "volunteer_notes"=>"",
//        "volunteer_enabled"=>true,
//        "volunteer_shift_schedule"=>base64_encode(json_encode($shifts))
//    ]];
//    $repository->shouldReceive("getDbData")->with(
//        $this->serviceBodyId,
//        DataType::YAP_VOLUNTEERS_V2
//    )->andReturn([(object)[
//        "service_body_id" => $this->serviceBodyId,
//        "id" => "200",
//        "parent_id" => $this->parentServiceBodyId,
//        "data" => json_encode($volunteer)
//    ]]);
//
//    app()->instance(ConfigRepository::class, $repository);
//
//    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
//    $conferenceListMock->shouldReceive("read")
//        ->with(['friendlyName' => $this->conferenceName])
//        ->andReturn(json_decode(
//            '[{"status":"in-progress","sid":"'.$this->conferenceName.'"}]'
//        ))
//        ->once();
//    $this->twilioClient->conferences = $conferenceListMock;
//
//    // mocking TwilioRestClient->conferences()->participants->read()
//    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
//    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
//    $participantListMock->shouldReceive("read")
//        ->andReturn(json_decode('[{"callSid":"abc"}]'));
//    $conferenceContextMock->participants = $participantListMock;
//    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);
//
//    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
//    $callInstance->from = "+15557770000";
//
//    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
//    $callContextMock->shouldReceive('fetch')
//        ->with()
//        ->andReturn($callInstance);
//    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);
//
//    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
//    $this->twilioClient->messages = $messageListMock;
//    $messageListMock->shouldReceive('create')
//        ->with("placeholder", Mockery::on(function ($data) {
//            return $data['from'] == $this->to && !empty($data['body'][0]);
//        }));
//
//    $GLOBALS['twilioClient'] = $this->twilioClient;
//
//    $_SESSION['override_service_body_id'] = '44';
//    $response = $this->call('GET', '/helpline-dialer.php', [
//        'SearchType' => "1",
//        'Called' => "+12125551212",
//        'ForceNumber' => '+19998887777',
//        'FriendlyName' => $this->conferenceName,
//        'SequenceNumber' => "1",
//        'CallStatus' => 'completed',
//    ]);
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "application/json");
//});
