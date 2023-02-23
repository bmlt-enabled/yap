<?php

use App\Repositories\ConfigRepository;
use PHPMailer\PHPMailer\PHPMailer;
use Tests\FakeTwilioHttpClient;
use App\Constants\DataType;

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
    ]);

    $this->callSid = "abc123";
    $this->callerNumber = "+17325551212";
    $this->recordingUrl = "file:///".getcwd()."/tests/fake";
});

test('voicemail complete send sms using primary contact', function () {
    $_SESSION['override_service_body_id'] = "44";
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->once();
    $this->twilioClient->messages = $messageListMock;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == "completed";
        }));
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $GLOBALS['twilioClient'] = $this->twilioClient;

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
    $response = $this->call('GET', '/voicemail-complete.php', [
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
});

// TODO: will fix this test later once more of functions.php is refactored so that all the DB connections are mocked properly
//test('voicemail complete send sms using volunteer responder option', function () {
//    $_SESSION['override_service_body_id'] = "44";
//    $_REQUEST['CallSid'] = $this->callSid;
//    $_REQUEST['caller_number'] = $this->callerNumber;
//    $_REQUEST['RecordingUrl'] = $this->recordingUrl;
//
//    $volunteer_routing_parameters = new \App\Models\VolunteerRoutingParameters();
//    $volunteer_routing_parameters->service_body_id = 44;
//    $volunteer_routing_parameters->tracker = 0;
//    $volunteer_routing_parameters->cycle_algorithm = \App\Constants\CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
//    $volunteer_routing_parameters->volunteer_type = \App\Constants\VolunteerType::PHONE;
//    $volunteer_routing_parameters->volunteer_gender = \App\Constants\VolunteerGender::UNSPECIFIED;
//    $volunteer_routing_parameters->volunteer_responder = \App\Constants\VolunteerResponderOption::UNSPECIFIED;
//    $volunteer_routing_parameters->volunteer_language = "en-US";
//    $_SESSION["volunteer_routing_parameters"] = $volunteer_routing_parameters;
//
//    // mocking TwilioRestClient->messages->create()
//    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
//    $messageListMock->shouldReceive('create')
//        ->with(is_string(""), is_array([]))->once();
//    $this->twilioClient->messages = $messageListMock;
//
//    // mocking TwilioRestClient->calls()->update();
//    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
//    $callContextMock->shouldReceive('update')
//        ->with(Mockery::on(function ($data) {
//            return $data['status'] == "completed";
//        }));
//    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);
//
//    $GLOBALS['twilioClient'] = $this->twilioClient;
//
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
//    app()->instance(ConfigRepository::class, $repository);
//    $response = $this->call('GET', '/voicemail-complete.php', [
//        "caller_id" => "+17325551212",
//        "CallSid" => $this->callSid,
//        "RecordingUrl" => $this->recordingUrl,
//        "caller_number" => $this->callerNumber,
//    ]);
//
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
//        ->assertSeeInOrder([
/*            '<?xml version="1.0" encoding="UTF-8"?>',*/
//            '<Response/>'
//        ], false);
//});

test('voicemail complete send email using primary contact', function () {
    $_SESSION['override_service_body_id'] = "44";
    $GLOBALS['smtp_host'] = "fake.host";
    $_REQUEST['CallSid'] = $this->callSid;
    $_REQUEST['caller_number'] = $this->callerNumber;
    $_REQUEST['RecordingUrl'] = $this->recordingUrl;

    // mocking TwilioRestClient->calls()->update();
    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['status'] == "completed";
        }));
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $GLOBALS['twilioClient'] = $this->twilioClient;

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

    $response = $this->call('GET', '/voicemail-complete.php', [
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
});
