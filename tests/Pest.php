<?php

use App\Repositories\DatabaseMigrationRepository;
use App\Repositories\GeocodingRepository;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;
use Tests\TwilioTestUtility;

// Called before each test.
uses(Tests\TestCase::class)->beforeEach(function () {
    $migrationsRepository = Mockery::mock(DatabaseMigrationRepository::class);
    $migrationsRepository->shouldReceive('getVersion')
        ->withNoArgs()->andReturn(100);
    app()->instance(DatabaseMigrationRepository::class, $migrationsRepository);

    $geocodingRepository = Mockery::mock(GeocodingRepository::class);
    $geocodingRepository->shouldReceive('getInfo')
        ->withArgs(["nowhere"])->andReturn(null);

    $response = json_encode([
        'status' => 'OK',
        'results' => array([
            'address_components' => array([
                'long_name' => '27592',
                'short_name' => '27592',
                'types' => array("postal_code")
            ],
                [
                    'long_name' => 'Willow Spring',
                    'short_name' => 'Willow Spring',
                    'types' => array("neighborhood", "political")
                ],
                [
                    'long_name' => 'North Carolina',
                    'short_name' => 'NC',
                    'types' => array("administrative_area_level_1", "political")
                ],
                [
                    'long_name' => 'United States',
                    'short_name' => 'US',
                    'types' => array("country", "political")
                ]),
            'formatted_address' => "Willow Spring, NC 27592, USA",
            'geometry' => [
                'bounds' => [
                    'northeast' => ['lat' => 35.61496, 'lng' => -78.559837],
                    'southwest' => ['lat' => 35.5099279, 'lng' => -78.773051]
                ],
                'location' => ['lat' => 35.5648713, 'lng' => -78.6682395],
                'location_type' => 'APPROXIMATE',
                'viewport' => [
                    'northeast' => ['lat' => 35.61496, 'lng' => -78.559837],
                    'southwest' => ['lat' => 35.5099279, 'lng' => -78.773051]
                ]
            ],
            'place_id' => 'ChIJ9_24SgGIrIkRjQxVxn7LHbk',
            'types' => array('postal_code')
        ])
    ]);

    $geocodingRepository->shouldReceive('getInfo')
        ->withArgs(['Raleigh, NC'])->andReturn($response);
    $geocodingRepository->shouldReceive('getInfo')
        ->withArgs(['27592'])->andReturn($response);
    $geocodingRepository->shouldReceive('getInfo')
        ->withArgs(['blah'])->andReturn(json_encode([
            'status' => 'OK',
            'results' => []
        ]));

    app()->instance(GeocodingRepository::class, $geocodingRepository);
})->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function setupTwilioService(): TwilioTestUtility
{
    $utility = new TwilioTestUtility();
    $fakeHttpClient = new FakeTwilioHttpClient();
    $utility->client = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $utility->twilio = mock(TwilioService::class)->makePartial();
    $utility->settings = new SettingsService();
    app()->instance(SettingsService::class, $utility->settings);
    app()->instance(TwilioService::class, $utility->twilio);
    $utility->twilio->shouldReceive("client")->withArgs([])->andReturn($utility->client);
    $utility->twilio->shouldReceive("settings")->andReturn($utility->settings);
    return $utility;
}
