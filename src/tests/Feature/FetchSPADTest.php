<?php

use App\Services\ReadingService;
use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

test('get a SPAD in English', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->set("word_language", "en-US");
    app()->instance(SettingsService::class, $settingsService);

    // ReadingService reaches na.org through bmlt/fetch-meditation, which builds
    // its own Guzzle client rather than using the Http facade - so
    // Http::preventStrayRequests() cannot see it and cannot fake it. Mock the
    // service instead, the way FetchJFTTest already does.
    $readingService = Mockery::mock(ReadingService::class);
    $readingService->shouldReceive('get')->andReturn([
        'Spiritual Principle a Day',
        'NA World Services, Inc. All Rights Reserved'
    ]);
    app()->instance(ReadingService::class, $readingService);

    $response = $this->call($method, '/fetch-spad.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeText("All Rights Reserved", false);
})->with(['GET', 'POST']);
