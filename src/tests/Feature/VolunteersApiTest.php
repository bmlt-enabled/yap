<?php

use App\Constants\AuthMechanism;
use App\Constants\VolunteerGender;
use App\Constants\VolunteerResponderOption;
use App\Constants\VolunteerType;
use App\Models\ConfigData;
use App\Services\RootServerService;
use App\Structures\Group;
use App\Structures\Volunteer;
use App\Structures\VolunteerData;
use App\Structures\VolunteerInfo;
use App\Utilities\VolunteerScheduleHelpers;
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

    $this->midddleware = new MiddlewareTests();
    $this->rootServerMocks = new RootServerMocks();
    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
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

test('get schedule for service body phone volunteer with timezone as blank string', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $shiftDay = 2;
    $shiftTz = "";
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

test('get schedule for service body phone volunteer with timezone as null', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $shiftDay = 2;
    $shiftTz = "null";
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
    $volunteer_name = "Corey";
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $notes = "something something something dark side";

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_notes = $notes;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer
    );

    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $this->serviceBodyId,
        "fmt" => "json"
    ]);

    $expectedResponse = [[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>intval($this->serviceBodyId),
        "notes"=>$notes,
        "language"=>["en-US"],
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

test('return volunteers without notes json', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $volunteer_name = "Corey";
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));

    ConfigData::createVolunteer(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $volunteer
    );

    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $this->serviceBodyId,
        "fmt" => "json"
    ]);

    $expectedResponse = [[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>intval($this->serviceBodyId),
        "notes"=>"",
        "language"=>["en-US"],
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

test('return volunteers recursively json', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;
    $volunteer_name = "Corey";
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $notes = "something something something dark side";

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));
    $volunteer->volunteer_notes = $notes;

    $parentServiceBodyId = 1052;
    $firstServiceBodyId = 1053;
    $secondServiceBodyId = 1054;

    ConfigData::createVolunteer(
        $firstServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    ConfigData::createVolunteer(
        $secondServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $parentServiceBodyId,
        "fmt" => "json",
        "recurse" => true
    ]);

    $expectedResponse = [[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>$firstServiceBodyId,
        "notes"=>$notes,
        "language"=>["en-US"],
        "shift_info"=>[[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd
        ]]
    ],[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>$secondServiceBodyId,
        "notes"=>$notes,
        "language"=>["en-US"],
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

test('return volunteers with groups recursively json', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;
    $volunteer_name = "Corey";
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $notes = "something something something dark side";

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));
    $volunteer->volunteer_notes = $notes;

    $parentServiceBodyId = 1052;
    $firstServiceBodyId = 1053;
    $secondServiceBodyId = 1054;

    ConfigData::createVolunteer(
        $firstServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    ConfigData::createVolunteer(
        $secondServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    $groupData = new Group();
    $groupData->group_name = "test";
    $groupData->group_shared_service_bodies = [$firstServiceBodyId];

    $groupId = ConfigData::createGroup(
        $firstServiceBodyId,
        $groupData
    );

    ConfigData::createGroupVolunteers(
        $firstServiceBodyId,
        $groupId,
        $volunteer,
    );

    ConfigData::addGroupToVolunteers(
        $firstServiceBodyId,
        $groupId,
        (object)["group_id" => $groupId, "group_enabled" => true]
    );

    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $parentServiceBodyId,
        "fmt" => "json",
        "recurse" => true
    ]);

    $expectedResponse = [[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>$firstServiceBodyId,
        "notes"=>$notes,
        "language"=>["en-US"],
        "shift_info"=>[[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd
        ]]
    ],[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>$secondServiceBodyId,
        "notes"=>$notes,
        "language"=>["en-US"],
        "shift_info"=>[[
            "day"=>$shiftDay,
            "tz"=>$shiftTz,
            "start_time"=>$shiftStart,
            "end_time"=>$shiftEnd
        ]]
    ],[
        "name"=>sprintf("%s", $volunteer_name),
        "number"=>$volunteer_phone_number,
        "gender"=>VolunteerGender::UNSPECIFIED,
        "responder"=>VolunteerResponderOption::UNSPECIFIED,
        "type"=>VolunteerType::PHONE,
        "service_body_id"=>$firstServiceBodyId,
        "notes"=>$notes,
        "language"=>["en-US"],
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

test('return volunteers recursively csv', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;

    $volunteer_name = "Corey ";
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $notes = "something something something dark side";

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));
    $volunteer->volunteer_notes = $notes;

    $parentServiceBodyId = 1052;
    $firstServiceBodyId = 1053;
    $secondServiceBodyId = 1054;

    ConfigData::createVolunteer(
        $firstServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    ConfigData::createVolunteer(
        $secondServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $parentServiceBodyId,
        "fmt" => "csv",
        "recurse" => "true"
    ]);

    $expectedResponse = "name,number,gender,responder,type,language,notes,service_body_id,shift_info\n\"Corey \",\"(555) 111-2222\",0,0,PHONE,\"[\"\"en-US\"\"]\",\"$notes\",$firstServiceBodyId,\"[{\"\"day\"\":1,\"\"tz\"\":\"\"America\/New_York\"\",\"\"start_time\"\":\"\"12:00 AM\"\",\"\"end_time\"\":\"\"11:59 PM\"\"}]\"\n\"Corey \",\"(555) 111-2222\",0,0,PHONE,\"[\"\"en-US\"\"]\",\"$notes\",$secondServiceBodyId,\"[{\"\"day\"\":1,\"\"tz\"\":\"\"America\/New_York\"\",\"\"start_time\"\":\"\"12:00 AM\"\",\"\"end_time\"\":\"\"11:59 PM\"\"}]\"\n";
    $response
        ->assertContent($expectedResponse)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertHeader("Content-Length", strlen($expectedResponse))
        ->assertHeader("Content-Disposition", sprintf("attachment; filename=\"%s-volunteer-list.csv\"", $parentServiceBodyId))
        ->assertStatus(200);
});

test('return volunteers csv', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $_SESSION['auth_is_admin'] = true;

    $volunteer_name = "Corey ";
    $volunteer_phone_number = "(555) 111-2222";
    $shiftDay = 1;
    $shiftTz = "America/New_York";
    $shiftStart = "12:00 AM";
    $shiftEnd = "11:59 PM";
    $notes = "something something something dark side";

    $volunteer = new VolunteerData();
    $volunteer->volunteer_name = $volunteer_name;
    $volunteer->volunteer_phone_number = $volunteer_phone_number;
    $volunteer->volunteer_shift_schedule = base64_encode(json_encode([[
        "day"=>$shiftDay,
        "tz"=>$shiftTz,
        "start_time"=>$shiftStart,
        "end_time"=>$shiftEnd,
    ]]));
    $volunteer->volunteer_notes = $notes;

    $parentServiceBodyId = 1052;
    $firstServiceBodyId = 1053;

    ConfigData::createVolunteer(
        $firstServiceBodyId,
        $parentServiceBodyId,
        $volunteer
    );

    $response = $this->call('GET', '/api/v1/volunteers/download', [
        "service_body_id" => $firstServiceBodyId,
        "fmt" => "csv"
    ]);

    $expectedResponse = "name,number,gender,responder,type,language,notes,service_body_id,shift_info\n\"Corey \",\"(555) 111-2222\",0,0,PHONE,\"[\"\"en-US\"\"]\",\"$notes\",$firstServiceBodyId,\"[{\"\"day\"\":1,\"\"tz\"\":\"\"America\/New_York\"\",\"\"start_time\"\":\"\"12:00 AM\"\",\"\"end_time\"\":\"\"11:59 PM\"\"}]\"\n";
    $response
        ->assertContent($expectedResponse)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertHeader("Content-Length", strlen($expectedResponse))
        ->assertHeader("Content-Disposition", sprintf("attachment; filename=\"%s-volunteer-list.csv\"", $firstServiceBodyId))
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

test('save volunteers', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559911";

    $response = $this->call(
        'POST',
        '/api/v1/volunteers',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($volunteerData)
    );

    $volunteer = new Volunteer($volunteerData->volunteer_phone_number);

    $response->assertJson([
        "id"=>135,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteer->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get volunteers for a service body', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559911";

    ConfigData::createVolunteers(
        $this->serviceBodyId,
        [$volunteerData]
    );

    $response = $this->call(
        'POST',
        '/api/v1/volunteers',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($volunteerData)
    );

    $volunteer = new Volunteer($volunteerData->volunteer_phone_number);

    $response->assertJson([
        "id"=>136,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteer->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get volunteers for a service body that does not exist', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $response = $this->call(
        'GET',
        '/api/v1/volunteers',
        ['serviceBodyId' => $this->serviceBodyId]
    );

    $response->assertJson([])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('update call volunteers for a service body', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559911";

    $response = $this->call(
        'POST',
        '/api/v1/volunteers',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($volunteerData)
    );

    $volunteer = new Volunteer($volunteerData->volunteer_phone_number);

    $response->assertJson([
        "id"=>137,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteer->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);


    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559912";

    $response = $this->call(
        'POST',
        '/api/v1/volunteers',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($volunteerData)
    );

    $volunteer = new Volunteer($volunteerData->volunteer_phone_number);

    $response->assertJson([
        "id"=>137,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteer->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});
