<?php

use App\Repositories\ConfigRepository;
use App\Constants\DataType;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('get config', function () {
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbData")->with(
        '44',
        DataType::YAP_CONFIG_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('GET', '/api/v1/config', [
        "service_body_id" => "44",
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('get groups', function () {
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbDataById")->with(
        '200',
        DataType::YAP_GROUPS_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('GET', '/api/v1/config', [
        "id" => '200',
        "data_type" => DataType::YAP_GROUPS_V2
    ]);
    $response->assertStatus(200);
});

test('get by parent id', function () {
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("getDbDataByParentId")->with(
        '43',
        DataType::YAP_CONFIG_V2
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('GET', '/api/v1/config', [
        "parent_id" => '43',
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('save group', function () {
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("adminPersistDbConfigById")->with(
        '200',
        ''
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('POST', '/api/v1/config', [
        "id" => '200',
        "data_type" => DataType::YAP_GROUPS_V2
    ]);
    $response->assertStatus(200);
});

test('save config', function () {
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("adminPersistDbConfig")->with(
        '44',
        '',
        DataType::YAP_CONFIG_V2,
        '0'
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => '44',
        "data_type" => DataType::YAP_CONFIG_V2
    ]);
    $response->assertStatus(200);
});

test('save config with parent id', function () {
    $repository = Mockery::mock(ConfigRepository::class);
    $repository->shouldReceive("adminPersistDbConfig")->with(
        '44',
        '',
        DataType::YAP_CONFIG_V2,
        '43'
    )->andReturn([(object)[
        "service_body_id" => "44",
        "id" => "200",
        "parent_id" => "43",
        "data" => "{\"data\":{}}"
    ]]);
    app()->instance(ConfigRepository::class, $repository);

    $response = $this->call('POST', '/api/v1/config', [
        "service_body_id" => '44',
        "data_type" => DataType::YAP_CONFIG_V2,
        "parent_id" => '43'
    ]);
    $response->assertStatus(200);
});
