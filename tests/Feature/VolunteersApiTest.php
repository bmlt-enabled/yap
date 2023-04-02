<?php

use App\Constants\DataType;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Repositories\ConfigRepository;
use App\Models\VolunteerInfo;
use App\Constants\VolunteerType;
use App\Services\VolunteerService;
use App\Utility\VolunteerScheduleHelpers;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
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
    $service_body_id = "44";
    $parent_service_body_id = "43";
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => $parent_service_body_id,
        "data" => json_encode($volunteer)
    ]]);
    app()->instance(ConfigRepository::class, $repository);
    $response = $this->call('GET', '/api/v1/volunteers/schedule', [
        "service_body_id" => $service_body_id,
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
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        $service_body_id,
        DataType::YAP_VOLUNTEERS_V2
    )->andReturn([(object)[
        "service_body_id" => $service_body_id,
        "id" => "200",
        "parent_id" => $parent_service_body_id,
        "data" => json_encode($volunteer)
    ]]);
    app()->instance(ConfigRepository::class, $repository);
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
