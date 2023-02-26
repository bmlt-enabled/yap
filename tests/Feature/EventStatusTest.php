<?php

use App\Models\EventStatus;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('returns data', function () {
    app()->instance(EventStatus::class, Mockery::mock(EventStatus::class, function ($mock) {
        $mock->shouldReceive('all')->once();
    }));

    $response = $this->get('/api/v1/events/status');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json");
});
