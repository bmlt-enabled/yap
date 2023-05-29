<?php

use App\Models\Timezone;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Services\TwilioService;
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
    ])->makePartial();

    $repository = Mockery::mock(TwilioService::class);
    $repository->shouldReceive("client")
        ->andReturn($this->twilioClient);
    app()->instance(TwilioService::class, $repository);

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
    $participantListMock->shouldReceive("read")->andReturn(["person1"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);

    $_REQUEST['Digits'] = "1";
    $_REQUEST['Called'] = "12125551212";
    $_REQUEST['conference_name'] = $this->conferenceName;
    $response = $this->call('GET', '/helpline-answer-response.php', [
        "Digits"=>"1",
        "Called"=>"12125551212",
        "conference_name"=>"abc"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<Response>',
            '<Dial>',
            sprintf('<Conference statusCallbackMethod="GET" statusCallbackEvent="join" startConferenceOnEnter="true" endConferenceOnExit="true" beep="false">%s</Conference>', $this->conferenceName),
        '</Dial>',
        '</Response>'
        ], false);
});

test('enough volunteers in conference, someone is talking to the caller already', function () {
    // mocking TwilioRestClient->conferences()->participants->read()
    $conferenceContextMock = mock("\Twilio\Rest\Api\V2010\Account\ConferenceContext");
    $participantListMock = mock("Twilio\Rest\Api\V2010\Account\Conference\ParticipantList");
    $participantListMock->shouldReceive("read")->andReturn(["person1", "person2"]);
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient->shouldReceive("conferences")->with($this->conferenceName)->andReturn($conferenceContextMock);

    $_REQUEST['Digits'] = "1";
    $_REQUEST['Called'] = "12125551212";
    $_REQUEST['conference_name'] = $this->conferenceName;
    $response = $this->call('GET', '/helpline-answer-response.php', [
        "Digits"=>"1",
        "Called"=>"12125551212",
        "conference_name"=>"abc"
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

test('volunteer opts not to answer the call', function () {
    $_REQUEST['Digits'] = "2";
    $_REQUEST['Called'] = "12125551212";
    $_REQUEST['CallSid'] = "abc";
    $_REQUEST['conference_name'] = $this->conferenceName;
    $_SESSION['no_answer_max'] = 5;
    $response = $this->call('GET', '/helpline-answer-response.php', [
        "Digits"=>"1",
        "Called"=>"12125551212",
        "CallSid"=>"abc",
        "conference_name"=>"abc"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<Response>',
            '<Hangup/>',
            '</Response>'
        ], false);
});
