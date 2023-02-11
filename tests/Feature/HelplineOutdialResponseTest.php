<?php

use Tests\FakeTwilioHttpClient;

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

    $this->conferenceName = "abc";

    // mocking TwilioRestClient->conferences->read()
    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode('[{"sid":"'.$this->conferenceName.'"}]'));
    $this->twilioClient->conferences = $conferenceListMock;
});

test('join volunteer to conference', function () {
    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn(["caller", "volunteer"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);
    $GLOBALS['twilioClient'] = $this->twilioClient;
    $response = $this->call('GET', '/helpline-outdial-response.php', [
        "Called"=>"12125551212",
        "CallSid"=>"fakeCallSid",
        "conference_name"=>$this->conferenceName,
        "service_body_id"=>"1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'A volunteer has already joined the call... goodbye</Say>',
            '<Hangup/>',
            '</Response>'
        ], false);
});

test('waiting for the volunteer to press 1 to answer the call', function () {
    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn(["caller"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);
    $GLOBALS['twilioClient'] = $this->twilioClient;

    $response = $this->call('GET', '/helpline-outdial-response.php', [
        "Called"=>"12125551212",
        "CallSid"=>"fakeCallSid",
        "service_body_id"=>"1",
        "conference_name"=>$this->conferenceName
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<Response>',
            '<Gather actionOnEmptyResult="1" numDigits="1" timeout="15" action="helpline-answer-response.php?conference_name=abc&amp;service_body_id=1" method="GET">',
            '<Say voice="alice" language="en-US">',
            'you have a call from the helpline, press 1 to accept.  press any other key to hangup.</Say>',
            '</Gather>',
            '</Response>'
        ], false);
});

test('volunteer called and auto answer capability enabled', function () {
    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn(["caller"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);
    $GLOBALS['twilioClient'] = $this->twilioClient;
    $GLOBALS['volunteer_auto_answer'] = true;

    $response = $this->call('GET', '/helpline-outdial-response.php', [
        "Called"=>"12125551212",
        "CallSid"=>"fakeCallSid",
        "service_body_id"=>"1",
        "conference_name"=>$this->conferenceName
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<Response>',
            '<Redirect method="GET">helpline-answer-response.php?Digits=1&amp;conference_name=abc&amp;service_body_id=1',
            '</Redirect>',
            '</Response>'
        ], false);
});


test('the caller hung up before the call was answered', function () {
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);
    $GLOBALS['twilioClient'] = $this->twilioClient;

    $response = $this->call('GET', '/helpline-outdial-response.php', [
        "Digits"=>"1",
        "Called"=>"12125551212",
        "CallSid"=>"abc",
        "conference_name"=>$this->conferenceName
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'unfortunately the caller hung up before we could connect you.  Good bye.',
            '</Say>',
            '<Hangup/>',
            '</Response>'
        ], false);
});