<?php

use App\Repositories\ConfigRepository;
use App\Constants\DataType;
use App\Services\RootServerService;
use Tests\RootServerMocks;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
    $this->rootServerMocks = new RootServerMocks();
});

test('get service bodies', function () {
    app()->instance(RootServerService::class, $this->rootServerMocks->getService());
    $response = $this->call('GET', '/api/v1/rootServer/servicebodies');
    $response
        ->assertJsonIsArray()
        ->assertStatus(200);
});
