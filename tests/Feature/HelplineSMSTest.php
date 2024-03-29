<?php

use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
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

    $this->midddleware = new MiddlewareTests();
    $this->utility = setupTwilioService();
    $this->rootServerMocks = new RootServerMocks();
    $this->id = "200";
    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
    $this->data =  "{\"data\":{}}";
    $this->configRepository = $this->midddleware->getAllDbData(
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
//    $messageListMock->shouldReceive('create')
//        ->withArgs([$this->to, [
//            'body' => 'could not find a volunteer for the location, please retry your request.',
//            'from' => $this->from]])->times(1);
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->to, [
            "body" => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            "from" => $this->from]])->times(1);
    $this->utility->client->messages = $messageListMock;

    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")
        ->with($this->serviceBodyId, DataType::YAP_VOLUNTEERS_V2)
        ->andReturn([(object)[
            "service_body_id" => $this->serviceBodyId,
            "id" => "200",
            "parent_id" => $this->parentServiceBodyId,
            "data" => json_encode([])
        ]])->once();

    $repository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers_and_sms\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $response = $this->call($method, '/sms-gateway.php', [
        "OriginalCallerId" => $this->to,
        "To" => $this->from,
        "Body" => "talk blah"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
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
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
        ], false);
})->with(['GET', 'POST']);
