<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Models\ServiceBodyCallHandling;
use App\Models\VolunteerData;
use App\Repositories\ConfigRepository;
use App\Repositories\ReportsRepository;
use App\Services\RootServerService;
use App\Constants\DataType;
use App\Services\SettingsService;
use App\Services\TwilioService;
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

    $this->utility = setupTwilioService();

    $this->settings = new SettingsService();
    app()->instance(SettingsService::class, $this->settings);

    $this->middleware = new MiddlewareTests();
    $this->utility = setupTwilioService();
    $this->rootServerMocks = new RootServerMocks();
    $this->id = "200";
    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
    $this->data =  "{\"data\":{}}";
    $this->configRepository = $this->middleware->getAllDbData(
        $this->id,
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $this->data
    );
    $this->from = '+12125551212';
    $this->to = '+19735551212';
});

test('initial sms helpline gateway default when there is no volunteer', function ($method) {
    $reportsRepository = Mockery::mock(ReportsRepository::class);
    $reportsRepository->shouldReceive("insertCallRecord")->withAnyArgs();
    $reportsRepository->shouldReceive("insertCallEventRecord")->withAnyArgs();

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            "day" => $i,
            "tz" => $shiftTz,
            "start_time" => $shiftStart,
            "end_time" => $shiftEnd,
            "type" => VolunteerType::SMS
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = '(732) 555-1111';
    $volunteer->volunteer_gender = $volunteer_gender;
    $volunteer->volunteer_responder = $volunteer_responder;
    $volunteer->volunteer_languages = $volunteer_languages;
    $volunteer->volunteer_notes = "";
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    app()->instance(ReportsRepository::class, $reportsRepository);
    $results[] = (object)["service_body_bigint"=>$this->serviceBodyId];
    $this->rootServerMocks->getService()
        ->shouldReceive("helplineSearch")
        ->withAnyArgs()->andReturn($results);
    $this->rootServerMocks->getService()
        ->shouldReceive("isBMLTServerOwned")
        ->withNoArgs()->andReturn(true);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->to, [
            "body" => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            "from" => $this->from]])->times(1);
    $messageListMock->shouldReceive('create')
        ->withArgs([$volunteer->volunteer_phone_number, [
            "body" => sprintf('Helpline: someone is requesting SMS help from %s please text or call them back.', $this->to),
            "from" => $this->from]])->times(1);
    $this->utility->client->messages = $messageListMock;

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $this->withoutExceptionHandling();
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );

    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $response = $this->call($method, '/sms-gateway.php', [
        "OriginalCallerId" => $this->to,
        "To" => $this->from,
        "Body" => "talk blah"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
    ], false);
})->with(['GET', 'POST']);

test('initial sms helpline gateway with a volunteer', function ($method) {
    $reportsRepository = Mockery::mock(ReportsRepository::class);
    $reportsRepository->shouldReceive("insertCallRecord")->withAnyArgs();
    app()->instance(ReportsRepository::class, $reportsRepository);
    $results[] = (object)["service_body_bigint"=>$this->serviceBodyId];
    $this->rootServerMocks->getService()
        ->shouldReceive("helplineSearch")
        ->withAnyArgs()->andReturn($results);
    $this->rootServerMocks->getService()
        ->shouldReceive("isBMLTServerOwned")
        ->withNoArgs()->andReturn(true);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->utility->client->messages = $messageListMock;

    $this->configRepository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers_and_sms\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"+15551112222\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $volunteer = [[
        "volunteer_name"=>$volunteer_name,
        "volunteer_phone_number"=>"(555) 111-2222",
        "volunteer_gender"=>$volunteer_gender,
        "volunteer_responder"=>$volunteer_responder,
        "volunteer_notes"=>"",
        "volunteer_enabled"=>true,
        "volunteer_shift_schedule"=>base64_encode(json_encode([[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd,
        ]]))
    ]];
    $this->configRepository->shouldReceive("getDbData")
        ->with($this->serviceBodyId, DataType::YAP_VOLUNTEERS_V2)
        ->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => json_encode($volunteer)
    ]])->once();

    app()->instance(ConfigRepository::class, $this->configRepository);

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs(['+19735551212', [
            "body" => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            "from" => '+12125551212']])->once();
    $messageListMock->shouldReceive('create')
        ->withArgs(['+15551112222', [
            "body" => 'Helpline: someone is requesting SMS help from +19735551212 please text or call them back.',
            "from" => '+12125551212']])->once();
    $this->utility->client->messages = $messageListMock;

    $response = $this->call($method, '/sms-gateway.php', [
        "OriginalCallerId" => '+19735551212',
        "To" => '+12125551212',
        "Body"=>"talk Geneva, NY"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
        ], false);
})->with(['GET', 'POST']);

test('initial sms helpline gateway with a volunteer with a different keyword', function ($method) {
    $reportsRepository = Mockery::mock(ReportsRepository::class);
    $reportsRepository->shouldReceive("insertCallRecord")->withAnyArgs();
    app()->instance(ReportsRepository::class, $reportsRepository);
    $results[] = (object)["service_body_bigint"=>$this->serviceBodyId];
    $this->rootServerMocks->getService()
        ->shouldReceive("helplineSearch")
        ->withAnyArgs()->andReturn($results);
    $this->rootServerMocks->getService()
        ->shouldReceive("isBMLTServerOwned")
        ->withNoArgs()->andReturn(true);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->utility->client->messages = $messageListMock;

    $this->configRepository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers_and_sms\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"+15551112222\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $volunteer = [[
        "volunteer_name"=>$volunteer_name,
        "volunteer_phone_number"=>"(555) 111-2222",
        "volunteer_gender"=>$volunteer_gender,
        "volunteer_responder"=>$volunteer_responder,
        "volunteer_notes"=>"",
        "volunteer_enabled"=>true,
        "volunteer_shift_schedule"=>base64_encode(json_encode([[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd,
        ]]))
    ]];
    $this->configRepository->shouldReceive("getDbData")
        ->with($this->serviceBodyId, DataType::YAP_VOLUNTEERS_V2)
        ->andReturn([(object)[
            "service_body_id" => $this->serviceBodyId,
            "id" => "200",
            "parent_id" => $this->parentServiceBodyId,
            "data" => json_encode($volunteer)
        ]])->once();

    app()->instance(ConfigRepository::class, $this->configRepository);

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs(['+19735551212', [
            "body" => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            "from" => '+12125551212']])->once();
    $messageListMock->shouldReceive('create')
        ->withArgs(['+15551112222', [
            "body" => 'Helpline: someone is requesting SMS help from +19735551212 please text or call them back.',
            "from" => '+12125551212']])->once();
    $this->utility->client->messages = $messageListMock;

    $_SESSION['override_sms_helpline_keyword'] = 'dude';
    $response = $this->call($method, '/sms-gateway.php', [
        "OriginalCallerId" => '+19735551212',
        "To" => '+12125551212',
        "Body"=>"dude 27592"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
        ], false);
})->with(['GET', 'POST']);
