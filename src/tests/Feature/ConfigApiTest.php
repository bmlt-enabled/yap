<?php

use App\Constants\AuthMechanism;
use App\Models\ConfigData;
use App\Constants\DataType;
use App\Services\RootServerService;
use Tests\RootServerMocks;

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
    $this->rootServerMocks = new RootServerMocks();
});

test('get config from endpoint', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)["title"=>"Welcome to blah"]
    );

    $this->call('GET', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ])->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>[["title"=>"Welcome to blah"]]
        ]);
});

test('get config for invalid service body', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    ConfigData::createServiceBodyConfiguration(
        99,
        $this->parentServiceBodyId,
        (object)[]
    );

    $this->call('GET', '/api/v1/config', [
        "service_body_id" => 99,
        "data_type" => DataType::YAP_CONFIG_V2
    ])->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);
});

test('get groups', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $groupData = ["group_name"=>"test", "group_shared_service_bodies"=>["1060"]];
    ConfigData::createGroup(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)$groupData
    );

    $this->call('GET', '/api/v1/config', [
        "id" => 3,
        "data_type" => DataType::YAP_GROUPS_V2
    ])->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>3,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>[$groupData]
        ]);
});

test('get by parent id', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;

    $configData = ["data"=>[["title"=>"Welcome to blah"]]];
    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)$configData
    );

    $this->call('GET', '/api/v1/config', [
        "parent_id" => $this->parentServiceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ])->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>4,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>[$configData]
        ]);
});

test('save group', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $groupData = [["group_name"=>"test", "group_shared_service_bodies"=>["1060"]]];
    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "parent_id" => $this->parentServiceBodyId,
        "data_type" => DataType::YAP_GROUPS_V2,
    ], content: json_encode($groupData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>5,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>$groupData
        ]);
});

test('save config', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $serviceBodyConfigData = [["title"=>"welcome to blah"]];
    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "parent_id" => $this->parentServiceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2,
    ], content: json_encode($serviceBodyConfigData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>6,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>$serviceBodyConfigData
        ]);
});

test('save config with parent id', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)[]
    );

    $serviceBodyConfigData = [["title"=>"welcome to blah"]];
    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2,
        "parent_id" => $this->parentServiceBodyId
    ], content: json_encode($serviceBodyConfigData));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>7,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>$serviceBodyConfigData
        ]);
});

test('delete group', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $response = $this->call('DELETE', sprintf('/api/v1/config/%s', $this->id));
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([]);
});

test('get config no auth', function () {
    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => 0,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertStatus(302);
});
