<?php

use App\Constants\AuthMechanism;
use App\Models\RecordType;
use App\Constants\EventId;
use App\Repositories\ConfigRepository;
use App\Repositories\ReportsRepository;
use App\Services\RootServerService;
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
    $this->data =  "{\"data\":{}}";

    $configRepository = mock(ConfigRepository::class)->makePartial();
    $this->configRepository = $this->middleware->getAllDbData(
        $this->id,
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $this->data
    );
    app()->instance(ConfigRepository::class, $this->configRepository);
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
    $repository = Mockery::mock(ReportsRepository::class);
    $service_body_id = "44";
    $id = "12312";
    $date_range_start = "2023-01-01 000:00:00";
    $date_range_end = "2023-01-07 23:59:59";
    $start_time = "2023-01-01 20:43:56Z";
    $end_time = "2023-01-01 20:45:00Z";
    $duration = 22;
    $from_number = "+15555555555";
    $to_number = "+18331112222";
    $callsid = "abc123";
    $sample_call_event = ([[
        "event_id"=>EventId::VOICEMAIL,
        "event_time"=>"2023-01-01 20:44:53Z",
        "service_body_id"=>$service_body_id,
        "meta"=>[
            "url"=>"fake.mp3"
        ]
    ]]);
    $repository->shouldReceive("getCallRecords")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn([(object)[
        "id" => $id,
        "start_time" => $start_time,
        "end_time" => $end_time,
        "duration" => $duration,
        "from_number" => $from_number,
        "to_number" => $to_number,
        "callsid" => $callsid,
        "service_body_id" => $service_body_id,
        "type" => RecordType::PHONE,
        "call_events" => json_encode($sample_call_event)
    ]]);
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
    ]);
    $sample_call_event[0]['parent_callsid'] = $callsid;
    $sample_call_event[0]['event_name'] = EventId::getEventById($sample_call_event[0]['event_id']);
    $sample_call_event[0]['meta'] = json_encode($sample_call_event[0]['meta']);
    $response
        ->assertJson([
            "data" => [[
                "call_events" => $sample_call_event,
                "start_time" => $start_time,
                "end_time" => $end_time,
                "id" => $id,
                "duration" => $duration,
                "from_number" => $from_number,
                "to_number" => $to_number,
                "callsid" => $callsid,
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
    $repository = Mockery::mock(ReportsRepository::class);
    $service_body_id = "44";
    $id = "12312";
    $date_range_start = "2023-01-01 000:00:00";
    $date_range_end = "2023-01-07 23:59:59";
    $start_time = "2023-01-01 20:43:56Z";
    $end_time = "2023-01-01 20:45:00Z";
    $duration = 22;
    $from_number = "+15555555555";
    $to_number = "+18331112222";
    $callsid = "abc123";
    $sample_call_event = ([[
        "event_id"=>EventId::VOICEMAIL,
        "event_time"=>"2023-01-01 20:44:53Z",
        "service_body_id"=>$service_body_id,
        "meta"=>[
            "url"=>"fake.mp3"
        ]
    ]]);
    $repository->shouldReceive("getCallRecords")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn([(object)[
        "id" => $id,
        "start_time" => $start_time,
        "end_time" => $end_time,
        "duration" => $duration,
        "from_number" => $from_number,
        "to_number" => $to_number,
        "callsid" => $callsid,
        "service_body_id" => $service_body_id,
        "type" => RecordType::SMS,
        "call_events" => json_encode($sample_call_event)
    ]]);
    app()->instance(ReportsRepository::class, $repository);
    $response = $this->call('GET', '/api/v1/reports/cdr', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end,
    ]);
    $sample_call_event[0]['parent_callsid'] = $callsid;
    $sample_call_event[0]['event_name'] = EventId::getEventById($sample_call_event[0]['event_id']);
    $sample_call_event[0]['meta'] = json_encode($sample_call_event[0]['meta']);
    $response
        ->assertJson([
            "data" => [[
                "call_events" => $sample_call_event,
                "start_time" => $start_time,
                "end_time" => $end_time,
                "id" => $id,
                "duration" => $duration,
                "from_number" => $from_number,
                "to_number" => $to_number,
                "callsid" => $callsid,
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
    $repository = Mockery::mock(ReportsRepository::class);
    $service_body_id = "44";
    $date_range_start = "2023-01-01 000:00:00";
    $date_range_end = "2023-01-07 23:59:59";
    $meta_sample = [
        "gather"=>"Raleigh, NC",
        "coordinates"=>[
            "location"=>"Raleigh, NC, USA",
            "latitude"=>35.7795897,
            "longitude"=>-78.638178,
        ]
    ];
    $repository->shouldReceive("getMapMetrics")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn([(object)[
        "event_id"=>EventId::VOLUNTEER_SEARCH,
        "meta"=>json_encode($meta_sample)
    ]]);
    app()->instance(ReportsRepository::class, $repository);
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
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $repository = Mockery::mock(ReportsRepository::class);
    $service_body_id = "44";
    $date_range_start = "2023-01-01 000:00:00";
    $date_range_end = "2023-01-07 23:59:59";
    $meta_sample = [
        "gather"=>"Raleigh, NC",
        "coordinates"=>[
            "location"=>"Raleigh, NC, USA",
            "latitude"=>35.7795897,
            "longitude"=>-78.638178,
        ]
    ];
    $repository->shouldReceive("getMapMetricByType")->with(
        [$service_body_id],
        EventId::VOLUNTEER_SEARCH,
        $date_range_start,
        $date_range_end
    )->andReturn([(object)[
        "event_id"=>EventId::VOLUNTEER_SEARCH,
        "meta"=>json_encode($meta_sample)
    ]]);
    app()->instance(ReportsRepository::class, $repository);
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
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $repository = Mockery::mock(ReportsRepository::class);
    $service_body_id = "44";
    $date_range_start = "2023-01-03 00:00:00";
    $date_range_end = "2023-01-03 23:59:59";

    $getMetric = [(object)[
        "timestamp"=>"2023-01-03",
        "counts"=>"1",
        "data"=>"{\"searchType\":\"1\"}",
        "service_body_id"=>$service_body_id
    ]];

    $repository->shouldReceive("getMetric")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn($getMetric);

    $summarySample = [
        ["event_id"=>EventId::VOLUNTEER_SEARCH, "counts"=>"14"],
        ["event_id"=>EventId::VOLUNTEER_IN_CONFERENCE, "counts"=>"6"],
        ["event_id"=>EventId::MEETING_SEARCH_SMS, "counts"=>"94"]];
    $repository->shouldReceive("getMetricCounts")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn($summarySample);


    $callsSample = [[
        "service_body_id"=>$service_body_id,
        "conferencesid"=>"abc123",
        "answered_count"=>"0",
        "missed_count"=>"3"
    ]];
    $repository->shouldReceive("getAnsweredAndMissedCallMetrics")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn($callsSample);

    $volunteersSample = [[
        "service_body_id"=>$service_body_id,
        "meta"=> "{\"to_number\":\"+19103818003\"}",
        "answered_count"=>"0",
        "missed_count"=>"3"
    ]];
    $repository->shouldReceive("getAnsweredAndMissedVolunteerMetrics")->with(
        [$service_body_id],
        $date_range_start,
        $date_range_end
    )->andReturn($volunteersSample);

    app()->instance(ReportsRepository::class, $repository);

    $response = $this->call('GET', '/api/v1/reports/metrics', [
        "service_body_id" => $service_body_id,
        "date_range_start" => $date_range_start,
        "date_range_end" => $date_range_end
    ]);

    $metricsCollection = [
        "metrics"=>[
            ["timestamp" => "2023-01-03", "counts" => "1", "service_body_id" => $service_body_id,
                "data" => "{\"searchType\":\"1\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"2\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"3\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"19\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"20\"}"],
            ["timestamp" => "2023-01-03", "counts" => 0, "data" => "{\"searchType\":\"21\"}"],
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
