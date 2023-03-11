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

test('get service bodies', function () {
    $response = $this->call('GET', '/api/v1/rootServer/servicebodies');
    $response
        ->assertJsonIsArray()
        ->assertStatus(200);
});
