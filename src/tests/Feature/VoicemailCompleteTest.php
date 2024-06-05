<?php

use App\Constants\CycleAlgorithm;
use App\Constants\SmtpPorts;
use App\Constants\TwilioCallStatus;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Models\ServiceBodyCallHandling;
use App\Models\VolunteerData;
use App\Models\VolunteerRoutingParameters;
use App\Repositories\ConfigRepository;
use App\Repositories\ReportsRepository;
use App\Services\RootServerService;
use Illuminate\Testing\Assert;
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
    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
    $this->utility = setupTwilioService();
    $this->callSid = "abc123";
    $this->callerNumber = "+17325551212";
    $this->recordingUrl = "https://example.org/tests/fake";
    $expectedPin = 4182804;

    $reportsRepository = mock(ReportsRepository::class)->makePartial();
    $reportsRepository->shouldReceive("insertCallEventRecord")
        ->withAnyArgs()->once();
    $reportsRepository->shouldReceive("lookupPinForCallSid")
        ->withArgs([$this->callSid])->andReturn([$expectedPin]);
    app()->instance(ReportsRepository::class, $reportsRepository);
});

// TODO: add a test that gets the voicemail complete link for dialback

test('voicemail complete send sms using primary contact', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $serviceBodyCallHandlingData->primary_contact = "2125551212";

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$serviceBodyCallHandlingData->primary_contact, [
            "from" => $this->callerNumber,
            "body" => sprintf(
                'You have a message from the Finger Lakes Area Service helpline from caller %s. Voicemail Link %s.mp3. ',
                $this->callerNumber,
                $this->recordingUrl
            ),
            ]])->times(1);
    $this->utility->client->messages = $messageListMock;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == TwilioCallStatus::COMPLETED;
        }));
    $this->utility->client->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => $this->callerNumber,
        "CallSid" => $this->callSid,
        "RecordingUrl" => $this->recordingUrl,
        "caller_number" => $this->callerNumber,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
})->with(['GET', 'POST']);

test('voicemail complete send sms using volunteer responder option', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;
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
        ];
    }

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = "Corey";
    $volunteer->volunteer_phone_number = "(555) 111-2222";
    $volunteer->volunteer_responder = VolunteerResponderOption::ENABLED;
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    $volunteer_routing_parameters = new VolunteerRoutingParameters();
    $volunteer_routing_parameters->service_body_id = $this->serviceBodyId;
    $volunteer_routing_parameters->tracker = 0;
    $volunteer_routing_parameters->cycle_algorithm = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $volunteer_routing_parameters->volunteer_type = VolunteerType::PHONE;
    $volunteer_routing_parameters->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_routing_parameters->volunteer_language = "en-US";
    $_SESSION["volunteer_routing_parameters"] = $volunteer_routing_parameters;

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$volunteer->volunteer_phone_number, [
            "from" => $this->callerNumber,
            "body" => sprintf(
                'You have a message from the Finger Lakes Area Service helpline from caller %s. Voicemail Link %s.mp3. ',
                $this->callerNumber,
                $this->recordingUrl
            ),
        ]])->times(1);
    $this->utility->client->messages = $messageListMock;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == TwilioCallStatus::COMPLETED;
        }));
    $this->utility->client->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer
    );

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData
    );

    $this->withoutExceptionHandling();

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $this->callSid,
        "RecordingUrl" => $this->recordingUrl,
        "caller_number" => $this->callerNumber,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
})->with(['GET', 'POST']);

test('voicemail complete send email using primary contact', function ($method, $smtp_alt_port, $smtp_secure) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $this->utility->settings->set("smtp_host", "fake.host");
    $this->utility->settings->set("smtp_username", "fake@user");
    $this->utility->settings->set("smtp_password", "fake@password");
    $this->utility->settings->set("smtp_alt_port", $smtp_alt_port);
    $this->utility->settings->set("smtp_secure", $smtp_secure);

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $serviceBodyCallHandlingData->primary_contact_email = "dude@bro.net";

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == TwilioCallStatus::COMPLETED;
        }))->once();
    $this->utility->client->shouldReceive('calls')
        ->with($this->callSid)
        ->andReturn($callContextMock)->once();

    $mailer = Mockery::mock(PHPMailer::class)->makePartial();
    $mailer
        ->shouldReceive("send")
        ->withNoArgs()
        ->once();
    app()->instance(PHPMailer::class, $mailer);

    ConfigData::createCallHandling(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $serviceBodyCallHandlingData,
    );

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $this->callSid,
        "RecordingUrl" => $this->recordingUrl,
        "caller_number" => $this->callerNumber,
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);

    if ($smtp_secure) {
        if ($smtp_secure == SmtpPorts::TLS) {
            Assert::assertTrue($mailer->Port == SmtpPorts::TLS);
        } else if ($smtp_secure == SmtpPorts::SSL) {
            Assert::assertTrue($mailer->Port == SmtpPorts::SSL);
        }
    } else if (isset($smtp_alt_port)) {
        Assert::assertTrue($mailer->Port == $smtp_alt_port);
    } else {
        Assert::assertTrue($mailer->Port == SmtpPorts::PLAIN);
    }
})->with(['GET', 'POST'], [null, 2525], [null, 'tls', 'ssl', 'bad']);
