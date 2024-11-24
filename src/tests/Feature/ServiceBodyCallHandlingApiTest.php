<?php

use App\Constants\AuthMechanism;
use App\Models\ConfigData;
use App\Structures\Group;
use App\Structures\ServiceBodyCallHandling;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {

    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;


    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
});

test('save call handling', function () {
    session()->put('auth_mechanism', AuthMechanism::V2);
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
    session()->put('auth_mechanism', AuthMechanism::V2);
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
    session()->put('auth_mechanism', AuthMechanism::V2);

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
    session()->put('auth_mechanism', AuthMechanism::V2);
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
