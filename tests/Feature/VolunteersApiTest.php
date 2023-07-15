<?php

use App\Constants\DataType;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Models\VolunteerData;
use App\Repositories\ConfigRepository;
use App\Models\VolunteerInfo;
use App\Constants\VolunteerType;
use App\Utility\VolunteerScheduleHelpers;
use Tests\MiddlewareTests;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->midddleware = new MiddlewareTests();
    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
    $this->data =  "{\"data\":{}}";
    $this->configRepository = $this->midddleware->getAllDbData(
        $this->id,
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $this->data
    );
});

test('get schedule for service body phone volunteer', function () {
    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $volunteer = [[
        "volunteer_name"=>$volunteer_name,
        "volunteer_phone_number"=>"(555) 111-2222",
        "volunteer_gender"=>$volunteer_gender,
        "volunteer_responder"=>$volunteer_responder,
        "volunteer_notes"=>"",
        "volunteer_enabled"=>true,
        "volunteer_shift_schedule"=>base64_encode(json_encode([[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd,
        ]]))
    ]];
    $this->configRepository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => "200",
        "parent_id" => $this->parentServiceBodyId,
        "data" => json_encode($volunteer)
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/schedule', [
        "service_body_id" => $this->serviceBodyId,
    ]);
    $volunteers = [];
    $volunteerInfo = new VolunteerInfo();
    $volunteerInfo->color = "#f0580f";
    $volunteerInfo->title = sprintf("%s (%s) ", $volunteer_name, VolunteerType::PHONE);
    $volunteerInfo->gender = $volunteer_gender;
    $volunteerInfo->language = $volunteer_languages;
    $volunteerInfo->responder = $volunteer_responder;
    $volunteerInfo->sequence = 0;
    $volunteerInfo->time_zone = $shiftTz;
    $volunteerInfo->weekday = "Monday";
    $volunteerInfo->weekday_id = $shiftDay;
    $volunteerInfo->start = VolunteerScheduleHelpers::getNextShiftInstance($shiftDay, $shiftStart, $shiftTz);
    $volunteerInfo->end = VolunteerScheduleHelpers::getNextShiftInstance($shiftDay, $shiftEnd, $shiftTz);
    $volunteerInfo->type = VolunteerType::PHONE;
    $volunteers[] = $volunteerInfo;
    VolunteerScheduleHelpers::filterOutPhoneNumber($volunteers);

    $response
        ->assertSimilarJson(json_decode(json_encode($volunteers), true))
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get schedule for service body sms volunteer', function () {
    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $volunteer = [[
        "volunteer_name"=>$volunteer_name,
        "volunteer_phone_number"=>"(555) 111-2222",
        "volunteer_gender"=>$volunteer_gender,
        "volunteer_responder"=>$volunteer_responder,
        "volunteer_notes"=>"",
        "volunteer_enabled"=>true,
        "volunteer_shift_schedule"=>base64_encode(json_encode([[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd,
            "type"=>VolunteerType::SMS
        ]]))
    ]];
    $service_body_id = "44";
    $parent_service_body_id = "43";
    $this->configRepository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => $parent_service_body_id,
        "data" => json_encode($volunteer)
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/schedule', [
        "service_body_id" => $service_body_id,
    ]);

    $volunteers = [];
    $volunteerInfo = new VolunteerInfo();
    $volunteerInfo->color = "#e3eb4f";
    $volunteerInfo->title = sprintf("%s (%s) ", $volunteer_name, VolunteerType::SMS);
    $volunteerInfo->gender = $volunteer_gender;
    $volunteerInfo->language = ["en-US"];
    $volunteerInfo->responder = $volunteer_responder;
    $volunteerInfo->sequence = 0;
    $volunteerInfo->time_zone = $shiftTz;
    $volunteerInfo->weekday = "Monday";
    $volunteerInfo->weekday_id = $shiftDay;
    $volunteerInfo->start = VolunteerScheduleHelpers::getNextShiftInstance($shiftDay, $shiftStart, $shiftTz);
    $volunteerInfo->end = VolunteerScheduleHelpers::getNextShiftInstance($shiftDay, $shiftEnd, $shiftTz);
    $volunteerInfo->type = VolunteerType::SMS;
    $volunteers[] = $volunteerInfo;
    VolunteerScheduleHelpers::filterOutPhoneNumber($volunteers);

    $response
        ->assertSimilarJson(json_decode(json_encode($volunteers), true))
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('return volunteers json', function () {
    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_name = $volunteer_name;
    $volunteerData->volunteer_phone_number = "(555) 111-2222";
    $volunteerData->volunteer_gender = $volunteer_gender;
    $volunteerData->volunteer_responder = $volunteer_responder;
    $volunteerData->volunteer_languages = $volunteer_languages;
    $volunteerData->volunteer_notes = "";
    $volunteerData->volunteer_enabled = true;
    $volunteerData->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));

    $service_body_id = "44";
    $parent_service_body_id = "43";
    $this->configRepository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => $parent_service_body_id,
        "data" => json_encode([$volunteerData])
    ]]);

    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $service_body_id,
        "fmt" => "json"
    ]);

    $expectedResponse = [[
        "name"=>sprintf("%s ", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>$volunteer_gender,
        "responder"=>$volunteer_responder,
        "type"=>VolunteerType::PHONE,
        "language"=>$volunteer_languages,
        "shift_info"=>[[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd
        ]]
    ]];
    $response
        ->assertSimilarJson($expectedResponse)
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('return volunteers csv', function () {
    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_name = $volunteer_name;
    $volunteerData->volunteer_phone_number = "(555) 111-2222";
    $volunteerData->volunteer_gender = $volunteer_gender;
    $volunteerData->volunteer_responder = $volunteer_responder;
    $volunteerData->volunteer_languages = $volunteer_languages;
    $volunteerData->volunteer_notes = "";
    $volunteerData->volunteer_enabled = true;
    $volunteerData->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));
    $service_body_id = "44";
    $parent_service_body_id = "43";
    $this->configRepository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => $parent_service_body_id,
        "data" => json_encode([$volunteerData])
    ]]);

    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $service_body_id,
        "fmt" => "csv"
    ]);

    $expectedResponse = "name,number,gender,responder,type,language,shift_info\n\"Corey \",\"(555) 111-2222\",0,0,PHONE,\"[\"\"en-US\"\"]\",\"[{\"\"day\"\":2,\"\"tz\"\":\"\"America\/New_York\"\",\"\"start_time\"\":\"\"12:00 AM\"\",\"\"end_time\"\":\"\"11:59 PM\"\"}]\"\n";
    $response
        ->assertContent($expectedResponse)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertHeader("Content-Length", strlen($expectedResponse))
        ->assertHeader("Content-Disposition", sprintf("attachment; filename=\"%s-map-metrics.csv\"", $service_body_id))
        ->assertStatus(200);
});

test('return volunteers invalid service body id', function () {
    $service_body_id = "999999";
    $this->configRepository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([]);

    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $service_body_id,
        "fmt" => "json"
    ]);

    $response
        ->assertSimilarJson([])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('return volunteers invalid format', function () {
    $service_body_id = "44";
    $this->configRepository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([]);

    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $service_body_id,
        "fmt" => "garbage"
    ]);

    $response
        ->assertSimilarJson([])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get groups for service body', function () {
    $service_body_id = "44";
    $parent_service_body_id = "43";
    $id = "200";
    $group = [[
        "group_name"=>"Fake Group",
        "group_shared_service_bodies"=>[$service_body_id]
    ]];
    $this->configRepository->shouldReceive("getAllDbData")->with(
        DataType::YAP_GROUPS_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => $id,
        "parent_id" => $parent_service_body_id,
        "data" => json_encode($group)
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/groups', [
        "service_body_id" => $service_body_id,
    ]);

    $response
        ->assertSimilarJson([[
            "data"=>json_encode($group),
            "id"=>$id,
            "parent_id"=>$parent_service_body_id,
            "service_body_id"=>$service_body_id
        ]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});
