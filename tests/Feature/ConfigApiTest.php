<?php

use App\Constants\AuthMechanism;
use App\Repositories\ConfigRepository;
use App\Constants\DataType;
use App\Services\RootServerService;
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

    $this->middleware = new MiddlewareTests();
    $this->id = "200";
    $this->serviceBodyId = "44";
    $this->parentServiceBodyId = "43";
    $this->data =  "{\"data\":{}}";
    $this->rootServerMocks = new RootServerMocks();
    $this->configRepository = $this->middleware->getAllDbData(
        $this->id,
        $this->serviceBodyId,
        $this->parentServiceBodyId,
        $this->data
    );
});

test('get config no auth', function () {
    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response
        ->assertHeader("Location", "http://localhost/admin")
        ->assertHeader("Content-Type", "text/html; charset=UTF-8")
        ->assertStatus(302);
});

test('get config', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $this->configRepository->shouldReceive("getDbData")->with(
        $this->serviceBodyId,
        DataType::YAP_CONFIG_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => $this->data
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('get config for invalid service body', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $this->configRepository->shouldReceive("getDbData")->with(
        99,
        DataType::YAP_CONFIG_V2
    )->andReturn([]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => 99,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('get groups', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $this->configRepository->shouldReceive("getDbDataById")->with(
        $this->id,
        DataType::YAP_GROUPS_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => $this->data
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('GET', '/api/v1/config', [
        "id" => $this->id,
        "data_type" => DataType::YAP_GROUPS_V2
    ]);
    $response->assertStatus(200);
});

test('get by parent id', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $this->configRepository->shouldReceive("getDbDataByParentId")->with(
        $this->parentServiceBodyId,
        DataType::YAP_CONFIG_V2
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => $this->data
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('GET', '/api/v1/config', [
        "parent_id" => $this->parentServiceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('save group', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $this->configRepository->shouldReceive("adminPersistDbConfigById")->with(
        $this->id,
        ''
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => $this->data
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('POST', '/api/v1/config', [
        "id" => $this->id,
        "data_type" => DataType::YAP_GROUPS_V2
    ]);
    $response->assertStatus(200);
});

test('save config', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $this->configRepository->shouldReceive("adminPersistDbConfig")->with(
        $this->serviceBodyId,
        '',
        DataType::YAP_CONFIG_V2,
        '0'
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => $this->data
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('save config with parent id', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());

    $this->configRepository->shouldReceive("adminPersistDbConfig")->with(
        $this->serviceBodyId,
        '',
        DataType::YAP_CONFIG_V2,
        $this->parentServiceBodyId
    )->andReturn([(object)[
        "service_body_id" => $this->serviceBodyId,
        "id" => $this->id,
        "parent_id" => $this->parentServiceBodyId,
        "data" => $this->data
    ]]);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => $this->serviceBodyId,
        "data_type" => DataType::YAP_CONFIG_V2,
        "parent_id" => $this->parentServiceBodyId
    ]);
    $response->assertStatus(200);
});

test('delete group', function () {
    $_SESSION['auth_mechanism'] = AuthMechanism::V2;
    $this->configRepository->shouldReceive("deleteDbConfigById")->with(
        $this->id,
    )->andReturn(1);
    app()->instance(ConfigRepository::class, $this->configRepository);

    $response = $this->call('DELETE', sprintf('/api/v1/config/%s', $this->id));
    $response->assertStatus(200);
});
