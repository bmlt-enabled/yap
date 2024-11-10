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

test('get config from endpoint using BMLT auth', function () {
    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)["title"=>"Welcome to blah"]
    );

    $response = $this->post(
        '/admin/login',
        ["username"=>"gnyr_admin","password"=>"CoreysGoryStory"]
    );

    $response
        ->assertStatus(302)
        ->assertHeader("Location", 'http://localhost/admin/home')
        ->assertHeader("Content-Type", "text/html; charset=utf-8");

    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>[["title"=>"Welcome to blah"]]
        ]);
});

test('get config from endpoint using BMLT no auth', function () {
    ConfigData::createServiceBodyConfiguration(
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        (object)["title"=>"Welcome to blah"]
    );

    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(302)
        ->assertHeader("Content-Type", "text/html; charset=utf-8")
        ->assertHeader("Location", 'http://localhost/admin');
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
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>[$configData]
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
            "id"=>1,
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
            "id"=>1,
            "service_body_id"=>$this->serviceBodyId,
            "parent_id"=>$this->parentServiceBodyId,
            "data"=>$serviceBodyConfigData
        ]);
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
