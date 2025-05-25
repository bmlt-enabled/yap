<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Structures\ServiceBodyCallHandling;
use App\Structures\VolunteerData;
use Tests\RootServerMocks;

beforeEach(function () {
    $this->utility = setupTwilioService();

    $this->settings = new SettingsService();
    app()->instance(SettingsService::class, $this->settings);

    $this->utility = setupTwilioService();
    $this->rootServerMocks = new RootServerMocks();
    $this->serviceBodyId = "1053";
    $this->parentServiceBodyId = "1052";
    $this->from = '+12125551212';
    $this->to = '+19735551212';
});

test('initial sms helpline gateway default when there is no volunteer', function ($method) {
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
    $this->utility->client->messages = $messageListMock;

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        new VolunteerData(),
    );

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $response = $this->call($method, '/sms-gateway.php', [
        "SmsSid" => "Dude123",
        "OriginalCallerId" => $this->to,
        "From"=> $this->to,
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

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->from, [
            "body" => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            "from" => $this->to]])->times(1);
    $messageListMock->shouldReceive('create')
        ->withArgs([$volunteer->volunteer_phone_number, [
            "body" => sprintf('Helpline: someone is requesting SMS help from %s please text or call them back.', $this->from),
            "from" => $this->to]])->times(1);
    $this->utility->client->messages = $messageListMock;

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    $response = $this->call($method, '/sms-gateway.php', [
        "SmsSid"=>"Dude123",
        "OriginalCallerId" => $this->from,
        "To" => $this->to,
        "From" => $this->from,
        "Body"=>"talk Geneva, NY"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
        ], false);
})->with(['GET', 'POST']);

test('initial sms helpline gateway with a volunteer with a different keywords', function ($method) {
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

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->withArgs([$this->from, [
            "body" => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            "from" => $this->to]])->times(1);
    $messageListMock->shouldReceive('create')
        ->withArgs([$volunteer->volunteer_phone_number, [
            "body" => sprintf('Helpline: someone is requesting SMS help from %s please text or call them back.', $this->from),
            "from" => $this->to]])->times(1);
    $this->utility->client->messages = $messageListMock;

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer,
    );

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $serviceBodyCallHandlingData->service_body_id = $this->serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $serviceBodyCallHandlingData
    );

    session()->put('override_sms_helpline_keyword', 'dude');

    $response = $this->call($method, '/sms-gateway.php', [
        "SmsSid" => "Dude123",
        "OriginalCallerId" => $this->from,
        "To" => $this->to,
        "From" => $this->from,
        "Body"=>"dude Geneva, NY"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
        ], false);
})->with(['GET', 'POST']);
