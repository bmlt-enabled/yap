<?php

use App\Models\RecordType;
use App\Constants\EventId;
use App\Repositories\ReportsRepository;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('validate sample cdr', function () {
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

test('validate sample map metrics poi csv', function () {
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
