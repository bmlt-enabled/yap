<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Models\ConfigData;
use App\Services\SettingsService;
use App\Services\TwilioService;
use App\Structures\ServiceBodyCallHandling;
use App\Structures\VolunteerData;
use Tests\FakeTwilioHttpClient;

beforeEach(function () {
    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        'username' => 'fake',
        'password' => 'fake',
        'httpClient' => $fakeHttpClient,
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
    $this->conferenceName = 'call-token-conference';
    $this->callSid = 'call-token-callsid';
    $this->serviceBodyId = '4400';
    $this->parentServiceBodyId = '43';
    $this->caller = '+17778889999';
    $this->callToken = 'CT' . bin2hex('shaken-stir-call-token-test');

    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive('client')->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive('settings')->andReturn($settingsService);

    session()->put('override_service_body_id', $this->serviceBodyId);
    session()->put('call_token', $this->callToken);
});

function seedCallTokenVolunteer(string $serviceBodyId, string $parentServiceBodyId, string $phoneNumber): void
{
    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $serviceBodyCallHandlingData->service_body_id = (int) $serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling((int) $serviceBodyId, $serviceBodyCallHandlingData);

    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            'day' => $i,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = 'Token Test Volunteer';
    $volunteer->volunteer_phone_number = $phoneNumber;
    $volunteer->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer->volunteer_languages = ['en-US'];
    $volunteer->volunteer_notes = '';
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer($serviceBodyId, $parentServiceBodyId, $volunteer);
}

test('forwards stored CallToken on volunteer outdial', function ($method) {
    $volunteerPhoneNumber = '(555) 222-0001';
    seedCallTokenVolunteer($this->serviceBodyId, $this->parentServiceBodyId, $volunteerPhoneNumber);

    $conferenceListMock = mock('\Twilio\Rest\Api\V2010\Account\ConferenceList');
    $conferenceListMock->shouldReceive('read')
        ->with(['friendlyName' => $this->conferenceName, 'status' => 'in-progress'])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"' . $this->conferenceName . '"}]'
        ))
        ->times(2);
    $this->twilioClient->conferences = $conferenceListMock;

    $conferenceContextMock = mock('\Twilio\Rest\Api\V2010\Account\ConferenceContext');
    $participantListMock = mock('Twilio\Rest\Api\V2010\Account\Conference\ParticipantList');
    $participantListMock->shouldReceive('read')
        ->andReturn(json_decode(sprintf('[{"callSid":"%s"}]', $this->callSid)))
        ->once();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient
        ->shouldReceive('conferences')
        ->with($this->conferenceName)
        ->andReturn($conferenceContextMock)
        ->once();

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = '+15557770000';

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs(function ($to, $from, $options) use ($volunteerPhoneNumber) {
            expect($to)->toBe($volunteerPhoneNumber)
                ->and($options)->toHaveKey('callToken', $this->callToken);

            return true;
        });
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;

    $this->call($method, '/helpline-dialer.php', [
        'CallSid' => $this->callSid,
        'SearchType' => '1',
        'Called' => '+12125551212',
        'Caller' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'StatusCallbackEvent' => 'participant-join',
        'SequenceNumber' => 1,
    ])->assertStatus(200);
})->with(['GET', 'POST']);

test('does not forward CallToken when forced caller ID is enabled', function ($method) {
    $volunteerPhoneNumber = '(555) 222-0002';

    ConfigData::query()
        ->where('service_body_id', $this->serviceBodyId)
        ->where('data_type', \App\Constants\DataType::YAP_CALL_HANDLING_V2)
        ->delete();

    $callHandling = new ServiceBodyCallHandling();
    $callHandling->volunteer_routing = VolunteerRoutingType::VOLUNTEERS;
    $callHandling->service_body_id = (int) $this->serviceBodyId;
    $callHandling->volunteer_routing_enabled = true;
    $callHandling->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;
    $callHandling->forced_caller_id = '+15551234567';

    ConfigData::createServiceBodyCallHandling((int) $this->serviceBodyId, $callHandling);

    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            'day' => $i,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = 'Forced Caller ID Volunteer';
    $volunteer->volunteer_phone_number = $volunteerPhoneNumber;
    $volunteer->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer->volunteer_languages = ['en-US'];
    $volunteer->volunteer_notes = '';
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    ConfigData::createVolunteer($this->serviceBodyId, $this->parentServiceBodyId, $volunteer);

    $conferenceListMock = mock('\Twilio\Rest\Api\V2010\Account\ConferenceList');
    $conferenceListMock->shouldReceive('read')
        ->with(['friendlyName' => $this->conferenceName, 'status' => 'in-progress'])
        ->andReturn(json_decode(
            '[{"status":"in-progress","sid":"' . $this->conferenceName . '"}]'
        ))
        ->times(2);
    $this->twilioClient->conferences = $conferenceListMock;

    $conferenceContextMock = mock('\Twilio\Rest\Api\V2010\Account\ConferenceContext');
    $participantListMock = mock('Twilio\Rest\Api\V2010\Account\Conference\ParticipantList');
    $participantListMock->shouldReceive('read')
        ->andReturn(json_decode(sprintf('[{"callSid":"%s"}]', $this->callSid)))
        ->once();
    $conferenceContextMock->participants = $participantListMock;
    $this->twilioClient
        ->shouldReceive('conferences')
        ->with($this->conferenceName)
        ->andReturn($conferenceContextMock)
        ->once();

    $callInstance = mock('Twilio\Rest\Api\V2010\Account\CallInstance')->makePartial();
    $callInstance->from = '+15557770000';

    $callContextMock = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContextMock->shouldReceive('fetch')->andReturn($callInstance);
    $this->twilioClient->shouldReceive('calls')->with($this->callSid)->andReturn($callContextMock);

    $callCreateInstance = mock('Twilio\Rest\Api\V2010\Account\CallList')->makePartial();
    $callCreateInstance->shouldReceive('create')
        ->withArgs(function ($to, $from, $options) use ($volunteerPhoneNumber) {
            expect($to)->toBe($volunteerPhoneNumber)
                ->and($options)->not->toHaveKey('callToken')
                ->and($options['callerId'])->toBe('+15551234567');

            return true;
        });
    $this->twilioClient->calls = $callCreateInstance;

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->twilioClient->messages = $messageListMock;

    $this->call($method, '/helpline-dialer.php', [
        'CallSid' => $this->callSid,
        'SearchType' => '1',
        'Called' => '+12125551212',
        'Caller' => $this->caller,
        'FriendlyName' => $this->conferenceName,
        'StatusCallbackEvent' => 'participant-join',
        'SequenceNumber' => 1,
    ])->assertStatus(200);
})->with(['GET', 'POST']);
