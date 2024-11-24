<?php

use App\Constants\TwilioCallStatus;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();

    $repository = Mockery::mock(TwilioService::class)->makePartial();
    $repository->shouldReceive("client")->andReturn($this->twilioClient);
    app()->instance(TwilioService::class, $repository);

    $this->conferenceName = "abc";
    $this->conferenceSid = "ijk";
    $this->voicemail_url = "https://example.org/voicemail.php";

    // mocking TwilioRestClient->conferences->read()
    $conferenceListMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceList");
    $conferenceListMock->shouldReceive("read")->with(['friendlyName' => $this->conferenceName])
        ->andReturn(json_decode('[{"sid":"'.$this->conferenceSid.'"}]'));
    $this->twilioClient->conferences = $conferenceListMock;
});

test('join volunteer to conference', function ($method) {
    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn(["person1"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceSid)->andReturn($conferenceContextMock);

    $response = $this->call($method, '/helpline-answer-response.php', [
        "Digits"=>"1",
        "Called"=>"12125551212",
        "conference_name"=>"abc"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Dial>',
            sprintf('<Conference statusCallbackMethod="GET" statusCallbackEvent="join" startConferenceOnEnter="true" endConferenceOnExit="true" beep="false">%s</Conference>', $this->conferenceName),
        '</Dial>',
        '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('enough volunteers in conference, someone is talking to the caller already', function ($method) {
    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn(["person1", "person2"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceSid)->andReturn($conferenceContextMock);

    $response = $this->call($method, '/helpline-answer-response.php', [
        "Digits"=>"1",
        "Called"=>"12125551212",
        "conference_name"=>"abc"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'A volunteer has already joined the call... goodbye</Say>',
            '<Hangup/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('volunteer opts not to answer the call', function ($method) {
    $callSid = "def";
    $digits = "2";
    $called = "12125551212";

    session()->put('no_answer_max', 5);
    $response = $this->call($method, '/helpline-answer-response.php', [
        "Digits"=>$digits,
        "Called"=>$called,
        "CallSid"=>$callSid,
        "conference_name"=>$this->conferenceName
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Hangup/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('no volunteers opt to answer the call, sent to voicemail', function ($method) {
    $callSid = "def";
    $digits = "2";
    $called = "12125551212";

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('update')
        ->with(Mockery::on(function ($data) {
            return $data['method'] == "GET" && $data['url'] == $this->voicemail_url;
        }))->once();
    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->status = TwilioCallStatus::INPROGRESS;
    $callContextMock->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($callSid)->andReturn($callContextMock);
    $this->twilioClient->calls = $callContextMock;

    session()->put('no_answer_max', 1);
    session()->put('master_callersid', $callSid);
    session()->put('voicemail_url', $this->voicemail_url);
    $response = $this->call($method, '/helpline-answer-response.php', [
        "Digits"=>$digits,
        "Called"=>$called,
        "CallSid"=>$callSid,
        "conference_name"=>$this->conferenceName
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Hangup/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('no volunteers opt to answer the call, caller hung up before being sent to voicemail', function ($method) {
    $callSid = "def";
    $digits = "2";
    $called = "12125551212";

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->status = TwilioCallStatus::COMPLETED;
    $callContextMock->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($callSid)->andReturn($callContextMock);
    $this->twilioClient->calls = $callContextMock;

    session()->put('no_answer_max', 1);
    session()->put('master_callersid', $callSid);
    session()->put('voicemail_url', $this->voicemail_url);
    $response = $this->call($method, '/helpline-answer-response.php', [
        "Digits"=>$digits,
        "Called"=>$called,
        "CallSid"=>$callSid,
        "conference_name"=>$this->conferenceName
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Hangup/>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
