<?php

use App\Services\HttpService;
use Tests\Stubs;

test('get a SPAD', function ($method) {
    $httpService = mock('App\Services\HttpService')->makePartial();
    $httpService->shouldReceive('get')
        ->withArgs(["https://www.spadna.org", 3600])
        ->once()
        ->andReturn(Stubs::spadEn());
    app()->instance(HttpService::class, $httpService);

    $response = $this->call($method, '/fetch-spad.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("All Rights Reserved", false);
})->with(['GET', 'POST']);
