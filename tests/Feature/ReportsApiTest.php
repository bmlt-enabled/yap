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
