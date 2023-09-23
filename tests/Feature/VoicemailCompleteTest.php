<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;
use App\Models\VolunteerRoutingParameters;
use App\Repositories\ConfigRepository;
use App\Repositories\ReportsRepository;
use App\Services\RootServerService;
use PHPMailer\PHPMailer\PHPMailer;
use App\Constants\DataType;
use Tests\RepositoryMocks;
use Tests\RootServerMocks;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->rootServerMocks = new RootServerMocks();
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
    $this->utility = setupTwilioService();
    $this->callSid = "abc123";
    $this->callerNumber = "+17325551212";
    $this->recordingUrl = "file:///".getcwd()."/tests/fake";

    $reportsRepository = mock(ReportsRepository::class)->makePartial();
    $reportsRepository->shouldReceive("insertCallEventRecord")
        ->withAnyArgs()->once();
    $reportsRepository->shouldReceive("lookupPinForCallSid")
        ->withArgs([$this->callSid])->andReturn([4182804]);
    app()->instance(ReportsRepository::class, $reportsRepository);
});

test('voicemail complete send sms using primary contact', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = "44";
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->once();
    $this->utility->client->messages = $messageListMock;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == "completed";
        }));
    $this->utility->client->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"2125551212\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $this->callSid,
        "RecordingUrl" => $this->recordingUrl,
        "caller_number" => $this->callerNumber,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
})->with(['GET', 'POST']);

test('voicemail complete send sms using volunteer responder option', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $serviceBodyId = "44";
    $parentServiceBodyId = "43";
    $_SESSION['override_service_body_id'] = $serviceBodyId;
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    $volunteer_routing_parameters = new VolunteerRoutingParameters();
    $volunteer_routing_parameters->service_body_id = $serviceBodyId;
    $volunteer_routing_parameters->tracker = 0;
    $volunteer_routing_parameters->cycle_algorithm = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $volunteer_routing_parameters->volunteer_type = VolunteerType::PHONE;
    $volunteer_routing_parameters->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_routing_parameters->volunteer_language = "en-US";
    $_SESSION["volunteer_routing_parameters"] = $volunteer_routing_parameters;

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->once();
    $this->utility->client->messages = $messageListMock;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == "completed";
        }));
    $this->utility->client->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $serviceBodyId,
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => $serviceBodyId,
        "id" => "200",
        "parent_id" => $parentServiceBodyId,
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();

    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::ENABLED;
    $volunteer_phone_number = "(555) 111-2222";
    $volunteer_languages = ["en-US"];

    $repositoryMocks = new RepositoryMocks();
    $repositoryMocks->getVolunteersMock(
        $repository,
        $volunteer_name,
        $volunteer_gender,
        $volunteer_responder,
        $volunteer_languages,
        $volunteer_phone_number,
        7,
        $serviceBodyId,
        $parentServiceBodyId
    );
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $this->callSid,
        "RecordingUrl" => $this->recordingUrl,
        "caller_number" => $this->callerNumber,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
})->with(['GET', 'POST']);

test('voicemail complete send email using primary contact', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = "44";
    $this->utility->settings->set("smtp_host", "fake.host");
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == "completed";
        }));
    $this->utility->client->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CALL_HANDLING_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "[{\"volunteer_routing\":\"volunteers\",\"volunteers_redirect_id\":\"\",\"forced_caller_id\":\"\",\"call_timeout\":\"\",\"gender_routing\":\"0\",\"call_strategy\":\"1\",\"volunteer_sms_notification\":\"send_sms\",\"sms_strategy\":\"2\",\"primary_contact\":\"\",\"primary_contact_email\":\"dude@bro.com\",\"moh\":\"\",\"override_en_US_greeting\":\"\",\"override_en_US_voicemail_greeting\":\"\"}]"
    ]])->once();

    app()->instance(ConfigRepository::class, $repository);

    $mailer = Mockery::mock(PHPMailer::class)->makePartial();
    $mailer->shouldReceive("send")->once();
    app()->instance(PHPMailer::class, $mailer);

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $this->callSid,
        "RecordingUrl" => $this->recordingUrl,
        "caller_number" => $this->callerNumber,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
})->with(['GET', 'POST']);
