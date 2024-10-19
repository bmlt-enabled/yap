<?php

use App\Constants\AuthMechanism;
use App\Models\ConfigData;
use App\Structures\Group;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
});

test('save group', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $groupData = new Group();
    $groupData->group_name = "test";
    $groupData->group_shared_service_bodies = ["1060"];

    $response = $this->call('POST',
        '/api/v1/groups', ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($groupData));

    $response->assertJson([[
        "id"=>6,
        "parent_id"=>NULL,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>json_encode([$groupData])]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('delete group', function () {
    $groupData = new Group();
    $groupData->group_name = "Fake Group";
    $groupData->group_shared_service_bodies = [$this->serviceBodyId];

    ConfigData::createGroup(
        $this->serviceBodyId,
        $groupData,
    );

     $_SESSION['auth_mechanism'] = AuthMechanism::V2;
     $response = $this->call('DELETE', sprintf('/api/v1/groups/%s', 7));
     $response->assertStatus(200)
         ->assertHeader("Content-Type", "application/json")
         ->assertJson([]);
 });

test('get groups for service body', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $groupData = new Group();
    $groupData->group_name = "Fake Group";
    $groupData->group_shared_service_bodies = [$this->serviceBodyId];

    ConfigData::createGroup(
        $this->serviceBodyId,
        $groupData,
    );

    $id = ConfigData::select('id')->orderBy('id', 'desc')->first()->id;

    $this->call('GET',
        '/api/v1/groups', ['serviceBodyId' => $this->serviceBodyId])->assertJson([[
        "id"=>$id,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>json_encode([$groupData])]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get groups for service body no auth', function () {
    $response = $this->call('GET', '/api/v1/groups', [
        "service_body_id" => 0,
    ]);
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
