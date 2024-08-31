<?php

use App\Constants\AuthMechanism;
use App\Constants\CallRole;
use App\Constants\EventId;
use App\Models\ConferenceParticipant;
use App\Models\Record;
use App\Models\RecordEvent;
use App\Services\RootServerService;
use App\Services\SettingsService;
use App\Structures\RecordType;
use Tests\MiddlewareTests;
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
    $this->middleware = new MiddlewareTests();

    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
    $this->settings = new SettingsService();
});


test('get cdr no auth', function () {
    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => '',
        "date_range_start" => '',
        "date_range_end" => '',
    ]);
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});

test('validate sample cdr phone', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $id = 11;
    $callSid = "abc123";
    $service_body_id = 44;
    $date_range_start = "2023-01-01T00:00:00";
    $date_range_end = "2023-01-07 23:59:59";
    $start_time = "2023-01-01 20:43:56";
    $end_time = "2023-01-01 20:45:00";
    $duration = 22;
    $from_number = "+15555555555";
    $to_number = "+18331112222";

    ConferenceParticipant::generate("conf123", $callSid, "fake_conference", CallRole::CALLER);
    RecordEvent::generate(
        $callSid,
        EventId::VOICEMAIL,
        "2023-01-01 20:44:53",
        $service_body_id,
        json_encode(["url"=>"fake.mp3"]),
        RecordType::PHONE
    );
    Record::generate($callSid, $start_time, $end_time, $from_number, $to_number, "", $duration, RecordType::PHONE);

    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
    ]);
    $sample_call_event = ([[
        "event_id"=>EventId::VOICEMAIL,
        "event_time"=>"2023-01-01 20:44:53Z",
        "service_body_id"=>$service_body_id,
        "meta"=>[
            "url"=>"fake.mp3"
        ]
    ]]);
    $sample_call_event[0]['parent_callsid'] = $callSid;
    $sample_call_event[0]['event_name'] = EventId::getEventById($sample_call_event[0]['event_id']);
    $sample_call_event[0]['meta'] = json_encode($sample_call_event[0]['meta']);
    $response
        ->assertJson([
            "data" => [[
                "call_events" => $sample_call_event,
                "start_time" => sprintf("%sZ", $start_time),
                "end_time" => sprintf("%sZ", $end_time),
                "id" => $id,
                "duration" => $duration,
                "from_number" => $from_number,
                "to_number" => $to_number,
                "callsid" => $callSid,
                "service_body_id" => $service_body_id,
                "type" => RecordType::PHONE,
                "type_name" => RecordType::getTypeById(RecordType::PHONE),
            ]],
            "last_page" => 1
        ])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('validate sample cdr sms', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $service_body_id = 44;
    $id = 12;
    $date_range_start = "2023-01-01 000:00:00";
    $date_range_end = "2023-01-07 23:59:59";
    $start_time = "2023-01-01 20:43:56";
    $end_time = "2023-01-01 20:45:00";
    $duration = 22;
    $from_number = "+15555555555";
    $to_number = "+18331112222";
    $callSid = "abc123";
    $sample_call_event = ([[
        "event_id"=>EventId::VOICEMAIL,
        "event_time"=>"2023-01-01 20:44:53Z",
        "service_body_id"=>$service_body_id,
        "meta"=>[
            "url"=>"fake.mp3"
        ]
    ]]);

    ConferenceParticipant::generate("conf123", $callSid, "fake_conference", CallRole::CALLER);
    RecordEvent::generate(
        $callSid,
        EventId::VOICEMAIL,
        "2023-01-01 20:44:53",
        $service_body_id,
        json_encode(["url"=>"fake.mp3"]),
        RecordType::SMS
    );
    Record::generate($callSid, $start_time, $end_time, $from_number, $to_number, "", $duration, RecordType::SMS);

    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
    ]);
    $sample_call_event[0]['parent_callsid'] = $callSid;
    $sample_call_event[0]['event_name'] = EventId::getEventById($sample_call_event[0]['event_id']);
    $sample_call_event[0]['meta'] = json_encode($sample_call_event[0]['meta']);
    $response
        ->assertJson([
            "data" => [[
                "call_events" => $sample_call_event,
                "start_time" => sprintf("%sZ", $start_time),
                "end_time" => sprintf("%sZ", $end_time),
                "id" => $id,
                "duration" => $duration,
                "from_number" => $from_number,
                "to_number" => $to_number,
                "callsid" => $callSid,
                "service_body_id" => $service_body_id,
                "type" => RecordType::SMS,
                "type_name" => RecordType::getTypeById(RecordType::SMS),
            ]],
            "last_page" => 1
        ])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('validate sample map metrics', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $service_body_id = "44";
    $date_range_start = $this->settings->getCurrentTime();
    $date_range_end = date('Y-m-d H:i:s', strtotime($this->settings->getCurrentTime() . ' + 30 seconds'));
    $meta_sample = [
        "gather"=>"Raleigh, NC",
        "coordinates"=>[
            "location"=>"Raleigh, NC, USA",
            "latitude"=>35.7795897,
            "longitude"=>-78.638178,
        ]
    ];

    RecordEvent::generate(
        "dude",
        EventId::VOLUNTEER_SEARCH,
        gmdate("Y-m-d H:i:s"),
        $service_body_id,
        json_encode($meta_sample),
        RecordType::PHONE
    );

    $response = $this->call('GET', '/api/v1/reports/mapmetrics', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
        "event_id" => EventId::VOLUNTEER_SEARCH
    ]);
    $expectedContent = sprintf(
        '[{"event_id":%s,"meta":"{\"gather\":\"%s\",\"coordinates\":{\"location\":\"%s\",\"latitude\":%s,\"longitude\":%s}}"}]',
        EventId::VOLUNTEER_SEARCH,
        $meta_sample['gather'],
        $meta_sample['coordinates']['location'],
        $meta_sample['coordinates']['latitude'],
        $meta_sample['coordinates']['longitude'],
    );
    $response
        ->assertContent($expectedContent)
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('validate sample map metrics poi csv', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $service_body_id = "44";
    $date_range_start = $this->settings->getCurrentTime();
    $date_range_end = date('Y-m-d H:i:s', strtotime($this->settings->getCurrentTime() . ' + 30 seconds'));
    $meta_sample = [
        "gather"=>"Raleigh, NC",
        "coordinates"=>[
            "location"=>"Raleigh, NC, USA",
            "latitude"=>35.7795897,
            "longitude"=>-78.638178,
        ]
    ];

    RecordEvent::generate(
        "dude",
        EventId::VOLUNTEER_SEARCH,
        gmdate("Y-m-d H:i:s"),
        $service_body_id,
        json_encode($meta_sample),
        RecordType::PHONE
    );

    $response = $this->call('GET', '/api/v1/reports/mapmetrics', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
        "format" => "csv",
        "event_id" => EventId::VOLUNTEER_SEARCH
    ]);
    $expectedContent = sprintf(
        "lat,lon,name,desc\n%s,%s,\"%s\",\"%s\"\n",
        $meta_sample['coordinates']['latitude'],
        $meta_sample['coordinates']['longitude'],
        $meta_sample['coordinates']['location'],
        EventId::VOLUNTEER_SEARCH
    );
    $response
        ->assertContent($expectedContent)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertHeader("Content-Disposition", "attachment; filename=\"volunteers-map-metrics.csv\"")
        ->assertHeader("Content-Length", strlen($expectedContent))
        ->assertStatus(200);
});

test('validate sample metrics', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $service_body_id = 1053;
    $date_range_start = "2023-01-03 00:00:00";
    $date_range_end = "2023-01-03 23:59:59";

    $summarySample = [
        ["event_id"=>EventId::VOLUNTEER_SEARCH, "counts"=>1],
        ["event_id"=>EventId::VOLUNTEER_IN_CONFERENCE, "counts"=>1],
        ["event_id"=>EventId::MEETING_SEARCH_SMS, "counts"=>1]];

    RecordEvent::generate(
        "abc123",
        EventId::VOLUNTEER_SEARCH,
        "2023-01-03",
        $service_body_id,
        "",
        RecordType::PHONE
    );

    RecordEvent::generate(
        "abc123",
        EventId::VOLUNTEER_IN_CONFERENCE,
        "2023-01-03",
        $service_body_id,
        "",
        RecordType::PHONE
    );

    RecordEvent::generate(
        "abc123",
        EventId::MEETING_SEARCH_SMS,
        "2023-01-03",
        $service_body_id,
        "",
        RecordType::SMS
    );

    RecordEvent::generate(
        "def456",
        EventId::VOLUNTEER_NOANSWER,
        "2023-01-03",
        $service_body_id,
        "{\"to_number\":\"+19103818003\"}",
        RecordType::PHONE
    );
    ConferenceParticipant::generate("abc123", "def456", "fake_conference", CallRole::CALLER);

    $callsSample = [[
        "service_body_id"=>$service_body_id,
        "conferencesid"=>"abc123",
        "answered_count"=>"0",
        "missed_count"=>"1"
    ]];

    $volunteersSample = [[
        "service_body_id"=>$service_body_id,
        "meta"=> "{\"to_number\":\"+19103818003\"}",
        "answered_count"=>"0",
        "missed_count"=>"1"
    ]];

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end
    ]);

    $metricsCollection = [
        "metrics"=>[
            ["timestamp" => "2023-01-03", "counts" => 1, "service_body_id" => $service_body_id,
                "data" => "{\"searchType\":\"1\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"2\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"3\"}"],
            ["timestamp" => "2023-01-03", "counts" => 1, "service_body_id" => $service_body_id,
                "data" => "{\"searchType\":\"19\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"20\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"21\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"23\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"24\"}"],
        ],
        "volunteers"=>$volunteersSample,
        "summary"=>$summarySample,
        "calls"=>$callsSample
    ];

    $response
        ->assertExactJson($metricsCollection)
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('validate sample metrics for with recurse', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;
    $parent_service_body_id = 1052;
    $service_body_id = 1053;
    $date_range_start = "2023-01-03 00:00:00";
    $date_range_end = "2023-01-03 23:59:59";

    $summarySample = [
        ["event_id"=>EventId::VOLUNTEER_SEARCH, "counts"=>1],
        ["event_id"=>EventId::VOLUNTEER_IN_CONFERENCE, "counts"=>1],
        ["event_id"=>EventId::MEETING_SEARCH_SMS, "counts"=>1]];

    RecordEvent::generate(
        "abc123",
        EventId::VOLUNTEER_SEARCH,
        "2023-01-03",
        $service_body_id,
        "",
        RecordType::PHONE
    );

    RecordEvent::generate(
        "abc123",
        EventId::VOLUNTEER_IN_CONFERENCE,
        "2023-01-03",
        $service_body_id,
        "",
        RecordType::PHONE
    );

    RecordEvent::generate(
        "abc123",
        EventId::MEETING_SEARCH_SMS,
        "2023-01-03",
        $service_body_id,
        "",
        RecordType::SMS
    );

    RecordEvent::generate(
        "def456",
        EventId::VOLUNTEER_NOANSWER,
        "2023-01-03",
        $service_body_id,
        "{\"to_number\":\"+19103818003\"}",
        RecordType::PHONE
    );
    ConferenceParticipant::generate("abc123", "def456", "fake_conference", CallRole::CALLER);

    $callsSample = [[
        "service_body_id"=>$service_body_id,
        "conferencesid"=>"abc123",
        "answered_count"=>"0",
        "missed_count"=>"1"
    ]];

    $volunteersSample = [[
        "service_body_id"=>$service_body_id,
        "meta"=> "{\"to_number\":\"+19103818003\"}",
        "answered_count"=>"0",
        "missed_count"=>"1"
    ]];

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => $parent_service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
        "recurse" => true
    ]);

    $metricsCollection = [
        "metrics"=>[
            ["timestamp" => "2023-01-03", "counts" => 1, "service_body_id" => $service_body_id,
                "data" => "{\"searchType\":\"1\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"2\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"3\"}"],
            ["timestamp" => "2023-01-03", "counts" => 1, "service_body_id" => $service_body_id,
                "data" => "{\"searchType\":\"19\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"20\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"21\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"23\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"24\"}"],
        ],
        "volunteers"=>$volunteersSample,
        "summary"=>$summarySample,
        "calls"=>$callsSample
    ];

    $response
        ->assertExactJson($metricsCollection)
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});
