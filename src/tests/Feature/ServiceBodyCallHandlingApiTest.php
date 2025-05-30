<?php

use App\Constants\AuthMechanism;
use App\Models\ConfigData;
use App\Models\User;
use App\Structures\Group;
use App\Structures\ServiceBodyCallHandling;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
});

test('save call handling', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    $callHandling = new ServiceBodyCallHandling();
    $callHandling->forced_caller_id_number = "123";

    $response = $this->call(
        'POST',
        '/api/v1/callHandling',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($callHandling)
    );

    $response->assertJson([
        "id"=>1,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$callHandling->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get call handling for a service body', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    $callHandling = new ServiceBodyCallHandling();
    $callHandling->forced_caller_id_number = "123";

    ConfigData::createServiceBodyCallHandling(
        $this->serviceBodyId,
        $callHandling
    );

    $response = $this->call(
        'GET',
        '/api/v1/callHandling',
        ['serviceBodyId' => $this->serviceBodyId]
    );

    $response->assertJson([
        "id"=>1,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$callHandling->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('get call handling for a service body that does not exist', function () {
    Sanctum::actingAs(User::factory()->create());
    ;

    $response = $this->call(
        'GET',
        '/api/v1/callHandling',
        ['serviceBodyId' => $this->serviceBodyId]
    );

    $response->assertJson([])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});

test('update call handling for a service body', function () {
    Sanctum::actingAs(User::factory()->create());
    ;
    $callHandling = new ServiceBodyCallHandling();
    $callHandling->forced_caller_id_number = "123";

    $response = $this->call(
        'POST',
        '/api/v1/callHandling',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($callHandling)
    );

    $response->assertJson([
        "id"=>1,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$callHandling->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);


    $callHandling = new ServiceBodyCallHandling();
    $callHandling->forced_caller_id_number = "123abc";

    $response = $this->call(
        'POST',
        '/api/v1/callHandling',
        ['serviceBodyId' => $this->serviceBodyId],
        content: json_encode($callHandling)
    );

    $response->assertJson([
        "id"=>1,
        "parent_id"=>0,
        "service_body_id"=>intval($this->serviceBodyId),
        "data"=>[$callHandling->toArray()]])
        ->assertHeader("Content-Type", "application/json")
        ->assertStatus(200);
});
