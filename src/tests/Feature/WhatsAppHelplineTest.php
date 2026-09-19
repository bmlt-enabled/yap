<?php

use App\Constants\CycleAlgorithm;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerRoutingType;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Services\SettingsService;
use App\Services\TimeZoneService;
use App\Structures\RecordType;
use App\Structures\ServiceBodyCallHandling;
use App\Structures\Timezone;
use App\Structures\VolunteerData;
use Tests\FakeHttp;
use Tests\RootServerMocks;

beforeEach(function () {
    FakeHttp::install();

    $this->utility = setupTwilioService();
    $this->settings = new SettingsService();
    app()->instance(SettingsService::class, $this->settings);

    $this->from = 'whatsapp:+19737771313';
    $this->to = 'whatsapp:+12125551212';
    $this->smsSid = 'SM' . bin2hex(random_bytes(16));
});

test('whatsapp meeting search records the inbound as whatsapp', function ($method) {
    $response = $this->call($method, '/sms-gateway.php', [
        'SmsSid' => $this->smsSid,
        'To' => $this->to,
        'From' => $this->from,
        'Body' => '27592',
    ]);

    $response
        ->assertStatus(200)
        ->assertHeader('Content-Type', 'text/xml; charset=utf-8')
        ->assertSee('meeting-search.php?SearchType=1', false);

    $record = \App\Models\Record::where('callsid', $this->smsSid)->first();
    expect($record)->not->toBeNull()
        ->and($record->from_number)->toBe($this->from)
        ->and($record->to_number)->toBe($this->to)
        ->and($record->type)->toBe(RecordType::WHATSAPP);
})->with(['GET', 'POST']);

test('whatsapp talk without a location replies on whatsapp', function ($method) {
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->once()
        ->with($this->from, Mockery::on(function ($data) {
            return $data['from'] === $this->to
                && str_contains($data['body'], 'talk');
        }));
    $this->utility->client->messages = $messageListMock;

    $response = $this->call($method, '/sms-gateway.php', [
        'SmsSid' => $this->smsSid,
        'To' => $this->to,
        'From' => $this->from,
        'Body' => 'talk',
    ]);

    $response->assertStatus(200);

    $record = \App\Models\Record::where('callsid', $this->smsSid)->first();
    expect($record->type)->toBe(RecordType::WHATSAPP);
})->with(['GET', 'POST']);

test('whatsapp volunteer request notifies the volunteer over sms with a wa.me link', function ($method) {
    $this->rootServerMocks = new RootServerMocks();
    $serviceBodyId = '1053';
    $parentServiceBodyId = '1052';

    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            'day' => $i,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
            'type' => VolunteerType::SMS,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = 'Corey';
    $volunteer->volunteer_phone_number = '(732) 555-1111';
    $volunteer->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer->volunteer_languages = ['en-US'];
    $volunteer->volunteer_notes = '';
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    $results[] = (object) ['service_body_bigint' => $serviceBodyId];
    $this->rootServerMocks->getService()
        ->shouldReceive('helplineSearch')
        ->withAnyArgs()->andReturn($results);
    $this->rootServerMocks->getService()
        ->shouldReceive('isBMLTServerOwned')
        ->withNoArgs()->andReturn(true);
    app()->instance(\App\Services\RootServerService::class, $this->rootServerMocks->getService());

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->once()
        ->withArgs([$this->from, [
            'body' => 'Thank you and your request has been received.  A volunteer should be responding to you shortly.',
            'from' => $this->to,
        ]]);
    $messageListMock->shouldReceive('create')
        ->once()
        ->withArgs([$volunteer->volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] === '+12125551212'
                && str_contains($data['body'], 'WhatsApp')
                && str_contains($data['body'], 'https://wa.me/19737771313');
        })]);
    $this->utility->client->messages = $messageListMock;

    ConfigData::createVolunteer($serviceBodyId, $parentServiceBodyId, $volunteer);

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $serviceBodyCallHandlingData->service_body_id = $serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->volunteer_sms_notification_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling($serviceBodyId, $serviceBodyCallHandlingData);

    $response = $this->call($method, '/sms-gateway.php', [
        'SmsSid' => $this->smsSid,
        'To' => $this->to,
        'From' => $this->from,
        'Body' => 'talk Geneva, NY',
    ]);

    $response->assertStatus(200);

    $record = \App\Models\Record::where('callsid', $this->smsSid)->first();
    expect($record)->not->toBeNull()
        ->and($record->type)->toBe(RecordType::WHATSAPP)
        ->and($record->from_number)->toBe($this->from);
})->with(['GET', 'POST']);

test('whatsapp volunteer sms from can be overridden', function () {
    $this->settings->set('whatsapp_sms_from', '+15559876543');
    app()->instance(SettingsService::class, $this->settings);

    $this->rootServerMocks = new RootServerMocks();
    $serviceBodyId = '1053';
    $parentServiceBodyId = '1052';

    $shifts = [];
    for ($i = 1; $i <= 7; $i++) {
        $shifts[] = [
            'day' => $i,
            'tz' => 'America/New_York',
            'start_time' => '12:00 AM',
            'end_time' => '11:59 PM',
            'type' => VolunteerType::SMS,
        ];
    }

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = 'Corey';
    $volunteer->volunteer_phone_number = '(732) 555-1111';
    $volunteer->volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer->volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer->volunteer_languages = ['en-US'];
    $volunteer->volunteer_notes = '';
    $volunteer->volunteer_enabled = true;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode($shifts));

    $results[] = (object) ['service_body_bigint' => $serviceBodyId];
    $this->rootServerMocks->getService()
        ->shouldReceive('helplineSearch')
        ->withAnyArgs()->andReturn($results);
    $this->rootServerMocks->getService()
        ->shouldReceive('isBMLTServerOwned')
        ->withNoArgs()->andReturn(true);
    app()->instance(\App\Services\RootServerService::class, $this->rootServerMocks->getService());

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')->once()->with($this->from, Mockery::any());
    $messageListMock->shouldReceive('create')
        ->once()
        ->withArgs([$volunteer->volunteer_phone_number, Mockery::on(function ($data) {
            return $data['from'] === '+15559876543';
        })]);
    $this->utility->client->messages = $messageListMock;

    ConfigData::createVolunteer($serviceBodyId, $parentServiceBodyId, $volunteer);

    $serviceBodyCallHandlingData = new ServiceBodyCallHandling();
    $serviceBodyCallHandlingData->volunteer_routing = VolunteerRoutingType::VOLUNTEERS_AND_SMS;
    $serviceBodyCallHandlingData->service_body_id = $serviceBodyId;
    $serviceBodyCallHandlingData->volunteer_routing_enabled = true;
    $serviceBodyCallHandlingData->call_strategy = CycleAlgorithm::LINEAR_CYCLE_AND_VOICEMAIL;

    ConfigData::createServiceBodyCallHandling($serviceBodyId, $serviceBodyCallHandlingData);

    $this->post('/sms-gateway.php', [
        'SmsSid' => $this->smsSid,
        'To' => $this->to,
        'From' => $this->from,
        'Body' => 'talk Geneva, NY',
    ])->assertStatus(200);
});

test('whatsapp blackhole matches the e164 number', function () {
    session()->put('override_sms_blackhole', '+19737771313');

    $response = $this->post('/sms-gateway.php', [
        'SmsSid' => $this->smsSid,
        'To' => $this->to,
        'From' => $this->from,
        'Body' => '27592',
    ]);

    $response->assertStatus(200);
    expect(\App\Models\Record::where('callsid', $this->smsSid)->first())->toBeNull();
});

test('whatsapp meeting results keep the whatsapp sender and recipient', function () {
    $this->settings->set('sms_combine', true);
    $this->settings->set('sms_ask', false);
    app()->instance(SettingsService::class, $this->settings);

    $timezone = new Timezone('OK', 0, -18000, 'America/New_York', 'Eastern Standard Time');
    $timezoneService = mock(TimeZoneService::class)->makePartial();
    $timezoneService->shouldReceive('getTimeZoneForCoordinates')
        ->withArgs(['42.867970', '-76.985573'])
        ->once()
        ->andReturn($timezone);
    app()->instance(TimeZoneService::class, $timezoneService);

    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->once()
        ->with($this->from, Mockery::on(function ($data) {
            return $data['from'] === $this->to && $data['body'] !== '';
        }));
    $this->utility->client->messages = $messageListMock;

    $this->post('/meeting-search.php', [
        'Latitude' => '42.867970',
        'Longitude' => '-76.985573',
        'To' => $this->to,
        'From' => $this->from,
        'SmsSid' => $this->smsSid,
        'Timestamp' => '2024-02-19 00:00:00',
    ])->assertStatus(200);
});

test('the public whatsapp page explains configuration when no number is set', function () {
    $this->get('/whatsapp')
        ->assertStatus(200)
        ->assertSee('Wi-Fi helpline', false)
        ->assertSee('$whatsapp_number', false)
        ->assertDontSee('class="cta"', false)
        ->assertDontSee('wa.me', false);
});

test('the public whatsapp page links to wa.me when a number is configured', function () {
    $this->settings->set('whatsapp_number', '+14075551212');
    $this->settings->set('whatsapp_prefill', 'talk Orlando');
    app()->instance(SettingsService::class, $this->settings);

    $this->get('/whatsapp')
        ->assertStatus(200)
        ->assertSee('Open WhatsApp', false)
        ->assertSee('https://wa.me/14075551212?text=talk%20Orlando', false)
        ->assertSee('+14075551212', false)
        ->assertSee('talk Orlando', false);
});

test('whatsapp settings point at documentation that exists', function () {
    $allowlist = (new SettingsService())->allowlist();
    $docsRoot = base_path('docs/docs');

    foreach (['whatsapp_number', 'whatsapp_sms_from', 'whatsapp_prefill'] as $name) {
        expect($allowlist)->toHaveKey($name);
        $docPath = $docsRoot . $allowlist[$name]['description'] . '.md';
        expect(file_exists($docPath))->toBeTrue("Setting '{$name}' links to missing documentation");
    }
});
