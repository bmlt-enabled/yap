<?php

use App\Constants\AuthMechanism;
use App\Constants\DataType;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Models\ConfigData;
use App\Models\VolunteerData;
use App\Repositories\ConfigRepository;
use App\Models\VolunteerInfo;
use App\Constants\VolunteerType;
use App\Services\RootServerService;
use App\Utility\VolunteerScheduleHelpers;
use Tests\MiddlewareTests;
use Tests\RepositoryMocks;
use Tests\RootServerMocks;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->midddleware = new MiddlewareTests();
    $this->rootServerMocks = new RootServerMocks();
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
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_name = "Corey";
    $volunteerData->volunteer_phone_number = "(555) 111-2222";
    $volunteerData->volunteer_enabled = true;
    $volunteerData->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteerData,
    );

    $response = $this->call('GET', '/api/v1/volunteers/schedule', [
        "service_body_id" => $this->serviceBodyId,
    ]);
    $volunteers = [];
    $volunteerInfo = new VolunteerInfo();
    $volunteerInfo->color = "#58f6eb";
    $volunteerInfo->title = sprintf("%s (%s)", $volunteerData->volunteer_name, VolunteerType::PHONE);
    $volunteerInfo->gender = VolunteerGender::UNSPECIFIED;
    $volunteerInfo->language = ["en-US"];
    $volunteerInfo->responder = VolunteerResponderOption::UNSPECIFIED;
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
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $shiftDay = 2;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_name = "Corey";
    $volunteerData->volunteer_phone_number = "(555) 111-2222";
    $volunteerData->volunteer_enabled = true;
    $volunteerData->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
        "type"=>VolunteerType::SMS
    ]]));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteerData,
    );

    $response = $this->call('GET', '/api/v1/volunteers/schedule', [
        "service_body_id" => $this->serviceBodyId,
    ]);

    $volunteers = [];
    $volunteerInfo = new VolunteerInfo();
    $volunteerInfo->color = "#872e11";
    $volunteerInfo->title = sprintf("%s (%s)", $volunteerData->volunteer_name, VolunteerType::SMS);
    $volunteerInfo->gender = VolunteerGender::UNSPECIFIED;
    $volunteerInfo->language = ["en-US"];
    $volunteerInfo->responder = VolunteerResponderOption::UNSPECIFIED;
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
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $serviceBodyId = "44";
    $parentServiceBodyId = "43";
    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_phone_number = "(555) 111-2222";
    $volunteer_languages = ["en-US"];
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $repositoryMocks = new RepositoryMocks();
    $repositoryMocks->getVolunteersMock(
        $this->configRepository,
        $volunteer_name,
        $volunteer_gender,
        $volunteer_responder,
        $volunteer_languages,
        $volunteer_phone_number,
        1,
        $serviceBodyId,
        $parentServiceBodyId
    );

    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $serviceBodyId,
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
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $serviceBodyId = "44";
    $parentServiceBodyId = "43";
    $volunteer_name = "Corey";
    $volunteer_gender = VolunteerGender::UNSPECIFIED;
    $volunteer_responder = VolunteerResponderOption::UNSPECIFIED;
    $volunteer_languages = ["en-US"];
    $volunteer_phone_number = "(555) 111-2222";
    $repositoryMocks = new RepositoryMocks();
    $repositoryMocks->getVolunteersMock(
        $this->configRepository,
        $volunteer_name,
        $volunteer_gender,
        $volunteer_responder,
        $volunteer_languages,
        $volunteer_phone_number,
        1,
        $serviceBodyId,
        $parentServiceBodyId
    );

    app()->instance(ConfigRepository::class, $this->configRepository);
    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $serviceBodyId,
        "fmt" => "csv"
    ]);

    $expectedResponse = "name,number,gender,responder,type,language,shift_info\n\"Corey \",\"(555) 111-2222\",0,0,PHONE,\"[\"\"en-US\"\"]\",\"[{\"\"day\"\":1,\"\"tz\"\":\"\"America\/New_York\"\",\"\"start_time\"\":\"\"12:00 AM\"\",\"\"end_time\"\":\"\"11:59 PM\"\"}]\"\n";
    $response
        ->assertContent($expectedResponse)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertHeader("Content-Length", strlen($expectedResponse))
        ->assertHeader("Content-Disposition", sprintf("attachment; filename=\"%s-map-metrics.csv\"", $serviceBodyId))
        ->assertStatus(200);
});

test('return volunteers invalid service body id', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $service_body_id = "999999";

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
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $service_body_id = "44";

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
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $groupData = ["group_name"=>"Fake Group", "group_shared_service_bodies"=>[$this->serviceBodyId]];

    ConfigData::createGroup(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)$groupData,
    );

    $id = ConfigData::select('id')->orderBy('id', 'desc')->first()->id;

    $this->call('GET', '/api/v1/volunteers/groups', [
        "service_body_id" => $this->serviceBodyId,
    ])->assertJson([[
            "id"=>$id,
            "service_body_id"=>intval($this->serviceBodyId),
            "parent_id"=>intval($this->parentServiceBodyId),
            "data"=>json_encode([$groupData])]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get groups for service body no auth', function () {
    $response = $this->call('GET', '/api/v1/volunteers/groups', [
        "service_body_id" => 0,
    ]);
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
