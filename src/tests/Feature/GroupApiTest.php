<?php

use App\Constants\AuthMechanism;
use App\Models\ConfigData;
use App\Models\User;
use App\Structures\Group;
use App\Structures\VolunteerData;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
});

test('save group', function () {
    Sanctum::actingAs(User::factory()->create());
    $groupData = new Group();
    $groupData->group_name = "test";
    $groupData->group_shared_service_bodies = ["1060"];

    $response = $this->call(
        'POST',
        '/api/v1/groups',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($groupData)
    );

    $response->assertJson([[
        "id"=>1,
        "parent_id"=>null,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$groupData->toArray()]]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('delete group', function () {
    $groupData = new Group();
    $groupData->group_name = "Fake Group";
    $groupData->group_shared_service_bodies = [$this->serviceBodyId];

    $groupId = ConfigData::createGroup(
        $this->serviceBodyId,
        $groupData,
    );

     Sanctum::actingAs(User::factory()->create());
     $response = $this->call('DELETE', sprintf('/api/v1/groups/%s', $groupId));
     $response->assertStatus(200)
         ->assertHeader("Content-Type", "application/json")
         ->assertJson(['message' => sprintf('Group %s deleted successfully', $groupId)]);
});

test('delete group that does not exist', function () {
    Sanctum::actingAs(User::factory()->create());
    $response = $this->call('DELETE', sprintf('/api/v1/groups/%s', 1000));
    $response->assertStatus(404)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson(['message' => 'Not found']);
});


test('get groups for service body', function () {
    Sanctum::actingAs(User::factory()->create());
    $groupData = new Group();
    $groupData->group_name = "Fake Group";
    $groupData->group_shared_service_bodies = [$this->serviceBodyId];

    ConfigData::createGroup(
        $this->serviceBodyId,
        $groupData,
    );

    $id = ConfigData::select('id')->orderBy('id', 'desc')->first()->id;

    $this->call(
        'GET',
        '/api/v1/groups',
        ['serviceBodyId' => $this->serviceBodyId]
    )->assertJson([[
        "id"=>$id,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$groupData->toArray()]]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get groups for service body no auth', function () {
    $response = $this->call('GET', '/api/v1/groups', [
        "service_body_id" => 0,
    ]);
    $response
        ->assertHeader("Location", "http://localhost/api/v1/login")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});

test('update group', function () {
    Sanctum::actingAs(User::factory()->create());
    $groupData = new Group();
    $groupData->group_name = "test";
    $groupData->group_shared_service_bodies = ["1060"];

    $response = $this->call(
        'POST',
        '/api/v1/groups',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($groupData)
    );

    $response->assertJson([[
        "id"=>1,
        "parent_id"=>null,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$groupData->toArray()]]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);

    $updatedGroupData = new Group();
    $updatedGroupData->group_name = "test2";
    $updatedGroupData->group_shared_service_bodies = ["1060", "1061"];

    $response = $this->call(
        'PUT',
        sprintf('/api/v1/groups/%s', 1),
        content: json_encode($updatedGroupData)
    );

    $response->assertJson([[
        "id"=>1,
        "parent_id"=>null,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$updatedGroupData->toArray()]]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});


test('save group volunteers', function () {
    Sanctum::actingAs(User::factory()->create());

    $groupData = new Group();
    $groupData->group_name = "test";
    $groupData->group_shared_service_bodies = ["1060"];

    $response = $this->call(
        'POST',
        '/api/v1/groups',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($groupData)
    );

    $groupId = json_decode($response->getContent())[0]->id;

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559911";

    $response = $this->call(
        'POST',
        '/api/v1/groups/volunteers',
        ['groupId' => $groupId, 'serviceBodyId' => $this->serviceBodyId],
        content: json_encode([$volunteerData])
    );

    $response->assertJson([
        "id"=>2,
        "parent_id"=>$groupId,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteerData->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('update group volunteers', function () {
    Sanctum::actingAs(User::factory()->create());

    $groupData = new Group();
    $groupData->group_name = "test";
    $groupData->group_shared_service_bodies = ["1060"];

    $response = $this->call(
        'POST',
        '/api/v1/groups',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($groupData)
    );

    $groupId = json_decode($response->getContent())[0]->id;

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559911";

    $response = $this->call(
        'POST',
        '/api/v1/groups/volunteers',
        ['groupId' => $groupId, 'serviceBodyId' => $this->serviceBodyId],
        content: json_encode([$volunteerData])
    );

    $response->assertJson([
        "id"=>2,
        "parent_id"=>$groupId,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteerData->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);

    $volunteerData = new VolunteerData();
    $volunteerData->volunteer_phone_number = "19735559912";


    $response = $this->call(
        'POST',
        '/api/v1/groups/volunteers',
        ['groupId' => $groupId],
        content: json_encode([$volunteerData])
    );

    $response->assertJson([
        "id"=>2,
        "parent_id"=>$groupId,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$volunteerData->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});
