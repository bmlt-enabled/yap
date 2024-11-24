<?php

use App\Constants\CycleAlgorithm;
use App\Constants\SmsDialbackOptions;
use App\Constants\SmtpPorts;
use App\Constants\TwilioCallStatus;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Models\Session;
use App\Services\RootServerService;
use App\Structures\ServiceBodyCallHandling;
use App\Structures\VolunteerData;
use App\Structures\VolunteerRoutingParameters;
use Illuminate\Testing\Assert;
use PHPMailer\PHPMailer\PHPMailer;
use Tests\RootServerMocks;

beforeEach(function () {
    $this->rootServerMocks = new RootServerMocks();
    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
    $this->utility = setupTwilioService();
    $this->callSid = "abc123";
    $this->callerNumber = "+17325551212";
    $this->recordingUrl = "https://example.org/tests/fake";
});

test('voicemail complete send sms using primary contact', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    session()->put('override_service_body_id', $this->serviceBodyId);

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
                'You have a message from the Finger Lakes Area Service helpline from the caller %s. Voicemail: %s.mp3. ',
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

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
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
    session()->put('override_service_body_id', $this->serviceBodyId);
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
    session()->put("volunteer_routing_parameters", $volunteer_routing_parameters);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$volunteer->volunteer_phone_number, [
            "from" => $this->callerNumber,
            "body" => sprintf(
                'You have a message from the Finger Lakes Area Service helpline from the caller %s. Voicemail: %s.mp3. ',
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

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
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
})->with(['GET', 'POST']);

test('voicemail complete send sms using volunteer responder option and dialback enabled', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    session()->put('override_service_body_id', $this->serviceBodyId);

    $pin = Session::getPin($this->callSid);
    Session::generate($this->callSid, $pin);
    $this->utility->settings->set('sms_dialback_options', SmsDialbackOptions::VOICEMAIL_NOTIFICATION);

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
    session()->put("volunteer_routing_parameters", $volunteer_routing_parameters);

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$volunteer->volunteer_phone_number, [
            "from" => $this->callerNumber,
            "body" => sprintf(
                'You have a message from the Finger Lakes Area Service helpline from the caller %s. Voicemail: %s.mp3. Tap to dialback: +17325551212,,,9,,,%s#.  PIN: %s',
                $this->callerNumber,
                $this->recordingUrl,
                $pin,
                $pin
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

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
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
})->with(['GET', 'POST']);

test('voicemail complete send sms using volunteer responder option and dialback enabled and language selection', function ($method) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    $this->withoutExceptionHandling();

    $pin = Session::getPin($this->callSid);
    Session::generate($this->callSid, $pin);
    $this->utility->settings->set('sms_dialback_options', SmsDialbackOptions::VOICEMAIL_NOTIFICATION);
    // enable piglatin language selection
    $this->utility->settings->set('language_selections', 'en-US,pig-latin');
    $this->utility->settings->setSessionLanguage('pig-latin');

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
                'ouyay avehay ayay essagemay omfray ethay Finger Lakes Area Service elplinehay omfray ethay allercay %s. oicemailvay: %s.mp3. aptay ootay ialbackdray: +17325551212,,,2,,,9,,,%s#.  PIN: %s',
                $this->callerNumber,
                $this->recordingUrl,
                $pin,
                $pin
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

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
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
})->with(['GET', 'POST']);

test('voicemail complete send email using primary contact with dialback disabled', function ($method, $smtp_alt_port, $smtp_secure) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    session()->put('override_service_body_id', $this->serviceBodyId);
    $smtp_host = "fake.host";
    $smtp_username = "fake@user";
    $smtp_password = "fake@password";

    $this->utility->settings->set("smtp_host", $smtp_host);
    $this->utility->settings->set("smtp_username", $smtp_username);
    $this->utility->settings->set("smtp_password", $smtp_password);
    $this->utility->settings->set("smtp_alt_port", $smtp_alt_port);
    $this->utility->settings->set("smtp_secure", $smtp_secure);

    $smtp_from_name = "bro bro";
    $smtp_from_address = "son@my.com";

    $this->utility->settings->set("smtp_from_name", $smtp_from_name);
    $this->utility->settings->set("smtp_from_address", $smtp_from_address);

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $serviceBodyCallHandlingData->primary_contact_email = "dude@bro.net,chief@home.org";

    $callSid = "asdfasdjk2l3r";

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == TwilioCallStatus::COMPLETED;
        }))->once();
    $this->utility->client->shouldReceive('calls')
        ->with($callSid)
        ->andReturn($callContextMock)->once();

    $mailer = Mockery::mock(PHPMailer::class)->makePartial();
    $mailer
        ->shouldReceive("send")
        ->withNoArgs()
        ->once();
    app()->instance(PHPMailer::class, $mailer);

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData,
    );

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $callSid,
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

    $pin = Session::getPin($callSid);

    Assert::assertTrue($mailer->Host == $smtp_host);
    Assert::assertTrue($mailer->Username == $smtp_username);
    Assert::assertTrue($mailer->Password == $smtp_password);
    Assert::assertTrue($mailer->SMTPAuth);
    Assert::assertTrue($mailer->From == $smtp_from_address);
    Assert::assertTrue($mailer->FromName == $smtp_from_name);
    Assert::assertTrue($mailer->getToAddresses()[0][0] == explode(",", $serviceBodyCallHandlingData->primary_contact_email)[0]);
    Assert::assertTrue($mailer->getToAddresses()[1][0] == explode(",", $serviceBodyCallHandlingData->primary_contact_email)[1]);
    $body = sprintf("You have a message from the Finger Lakes Area Service helpline from the caller %s. Voicemail: https://example.org/tests/fake.mp3. ", $this->callerNumber);
    Assert::assertTrue($mailer->Body == $body);
    Assert::assertTrue($mailer->Subject == "Helpline Voicemail from Finger Lakes Area Service");
    Assert::assertTrue($mailer->getAttachments()[0][1] == "https://example.org/tests/fake.mp3");
    Assert::assertTrue($mailer->getAttachments()[0][2] == "fake.mp3");
    Assert::assertTrue($mailer->getAttachments()[0][3] == "base64");
    Assert::assertTrue($mailer->getAttachments()[0][4] == "audio/mpeg");
    Assert::assertTrue($mailer->getAttachments()[0][5]);
    Assert::assertTrue($mailer->getAttachments()[0][6] == "attachment");

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

test('voicemail complete send email using primary contact with dialback enabled', function ($method, $smtp_alt_port, $smtp_secure) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $smtp_host = "fake.host";
    $smtp_username = "fake@user";
    $smtp_password = "fake@password";

    $this->utility->settings->set("smtp_host", $smtp_host);
    $this->utility->settings->set("smtp_username", $smtp_username);
    $this->utility->settings->set("smtp_password", $smtp_password);
    $this->utility->settings->set("smtp_alt_port", $smtp_alt_port);
    $this->utility->settings->set("smtp_secure", $smtp_secure);

    $smtp_from_name = "bro bro";
    $smtp_from_address = "son@my.com";

    $this->utility->settings->set("smtp_from_name", $smtp_from_name);
    $this->utility->settings->set("smtp_from_address", $smtp_from_address);

    $this->utility->settings->set('sms_dialback_options', SmsDialbackOptions::VOICEMAIL_NOTIFICATION);

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $serviceBodyCallHandlingData->primary_contact_email = "dude@bro.net,chief@home.org";

    $callSid = "asdfasdjk2l3r";

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == TwilioCallStatus::COMPLETED;
        }))->once();
    $this->utility->client->shouldReceive('calls')
        ->with($callSid)
        ->andReturn($callContextMock)->once();

    $mailer = Mockery::mock(PHPMailer::class)->makePartial();
    $mailer
        ->shouldReceive("send")
        ->withNoArgs()
        ->once();
    app()->instance(PHPMailer::class, $mailer);

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData,
    );

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $callSid,
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

    $pin = Session::getPin($callSid);

    Assert::assertTrue($mailer->Host == $smtp_host);
    Assert::assertTrue($mailer->Username == $smtp_username);
    Assert::assertTrue($mailer->Password == $smtp_password);
    Assert::assertTrue($mailer->SMTPAuth);
    Assert::assertTrue($mailer->From == $smtp_from_address);
    Assert::assertTrue($mailer->FromName == $smtp_from_name);
    Assert::assertTrue($mailer->getToAddresses()[0][0] == explode(",", $serviceBodyCallHandlingData->primary_contact_email)[0]);
    Assert::assertTrue($mailer->getToAddresses()[1][0] == explode(",", $serviceBodyCallHandlingData->primary_contact_email)[1]);
    $body = sprintf("You have a message from the Finger Lakes Area Service helpline from the caller %s. Voicemail: https://example.org/tests/fake.mp3. ", $this->callerNumber);
    $body .= sprintf("Tap to dialback: %s,,,9,,,%s#.  PIN: %s", $this->callerNumber, $pin, $pin);
    Assert::assertTrue($mailer->Body == $body);
    Assert::assertTrue($mailer->Subject == "Helpline Voicemail from Finger Lakes Area Service");
    Assert::assertTrue($mailer->getAttachments()[0][1] == "https://example.org/tests/fake.mp3");
    Assert::assertTrue($mailer->getAttachments()[0][2] == "fake.mp3");
    Assert::assertTrue($mailer->getAttachments()[0][3] == "base64");
    Assert::assertTrue($mailer->getAttachments()[0][4] == "audio/mpeg");
    Assert::assertTrue($mailer->getAttachments()[0][5]);
    Assert::assertTrue($mailer->getAttachments()[0][6] == "attachment");

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

test('voicemail complete send email using primary contact with dialback enabled and language selection', function ($method, $smtp_alt_port, $smtp_secure) {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $_SESSION['override_service_body_id'] = $this->serviceBodyId;
    $smtp_host = "fake.host";
    $smtp_username = "fake@user";
    $smtp_password = "fake@password";

    $this->utility->settings->set("smtp_host", $smtp_host);
    $this->utility->settings->set("smtp_username", $smtp_username);
    $this->utility->settings->set("smtp_password", $smtp_password);
    $this->utility->settings->set("smtp_alt_port", $smtp_alt_port);
    $this->utility->settings->set("smtp_secure", $smtp_secure);

    $smtp_from_name = "bro bro";
    $smtp_from_address = "son@my.com";

    $this->utility->settings->set("smtp_from_name", $smtp_from_name);
    $this->utility->settings->set("smtp_from_address", $smtp_from_address);

    $this->utility->settings->set('sms_dialback_options', SmsDialbackOptions::VOICEMAIL_NOTIFICATION);

    $this->utility->settings->set('language_selections', 'en-US,pig-latin');
    $this->utility->settings->setSessionLanguage('pig-latin');

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $serviceBodyCallHandlingData->primary_contact_email = "dude@bro.net,chief@home.org";

    $callSid = "asdfasdjk2l3r";

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == TwilioCallStatus::COMPLETED;
        }))->once();
    $this->utility->client->shouldReceive('calls')
        ->with($callSid)
        ->andReturn($callContextMock)->once();

    $mailer = Mockery::mock(PHPMailer::class)->makePartial();
    $mailer
        ->shouldReceive("send")
        ->withNoArgs()
        ->once();
    app()->instance(PHPMailer::class, $mailer);

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData,
    );

    $response = $this->call($method, '/voicemail-complete.php', [
        "caller_id" => "+17325551212",
        "CallSid" => $callSid,
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

    $pin = Session::getPin($callSid);

    Assert::assertTrue($mailer->Host == $smtp_host);
    Assert::assertTrue($mailer->Username == $smtp_username);
    Assert::assertTrue($mailer->Password == $smtp_password);
    Assert::assertTrue($mailer->SMTPAuth);
    Assert::assertTrue($mailer->From == $smtp_from_address);
    Assert::assertTrue($mailer->FromName == $smtp_from_name);
    Assert::assertTrue($mailer->getToAddresses()[0][0] == explode(",", $serviceBodyCallHandlingData->primary_contact_email)[0]);
    Assert::assertTrue($mailer->getToAddresses()[1][0] == explode(",", $serviceBodyCallHandlingData->primary_contact_email)[1]);
    $body = sprintf("ouyay avehay ayay essagemay omfray ethay Finger Lakes Area Service elplinehay omfray ethay allercay %s. oicemailvay: https://example.org/tests/fake.mp3. ", $this->callerNumber);
    $body .= sprintf("aptay ootay ialbackdray: %s,,,2,,,9,,,%s#.  PIN: %s", $this->callerNumber, $pin, $pin);
    Assert::assertTrue($mailer->Body == $body);
    // TODO: Need to translate the subject.
    Assert::assertTrue($mailer->Subject == "Helpline Voicemail from Finger Lakes Area Service");
    Assert::assertTrue($mailer->getAttachments()[0][1] == "https://example.org/tests/fake.mp3");
    Assert::assertTrue($mailer->getAttachments()[0][2] == "fake.mp3");
    Assert::assertTrue($mailer->getAttachments()[0][3] == "base64");
    Assert::assertTrue($mailer->getAttachments()[0][4] == "audio/mpeg");
    Assert::assertTrue($mailer->getAttachments()[0][5]);
    Assert::assertTrue($mailer->getAttachments()[0][6] == "attachment");

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

// TODO: need the same test for the email with translations.
// TODO: there are no email responders for email.
