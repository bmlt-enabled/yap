<?php

use App\Services\SettingsService;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('version test', function($method) {

    $settings = new SettingsService();
    app()->instance(SettingsService::class, $settings);
    $response = $this->call($method, '/version');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            'version' => $settings->version(),
        ]);
})->with(['GET', 'POST']);;

test('version test as jsonp', function($method) {
    $settings = new SettingsService();
    app()->instance(SettingsService::class, $settings);
    $response = $this->call($method, '/version?callback=bro');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/javascript")
        ->assertSeeText(sprintf("bro({\"version\":\"%s\"})", $settings->version()), false);
})->with(['GET', 'POST']);;

//test('bad google maps api key', function () {
//    $settings = new SettingsService();
//    $GLOBALS['google_maps_endpoint'] = 'https://maps.googleapis.com/maps/api/geocode/json?key=bad_key';
//    app()->instance(SettingsService::class, $settings);
//    $response = $this->get('/upgrade-advisor.php');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "application/json")
//        ->assertJson([
//            'status' => false,
//            'message' => 'Your Google Maps API key came back with the following error. The provided API key is invalid.  Please make sure you have the Google Maps Geocoding API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/',
//            'warnings' => '',
//            'version' => $settings->version(),
//            'build' => 'local'
//        ]);
//});

//test('bad timezone api key', function () {
//    $GLOBALS['google_maps_endpoint'] = 'http://localhost:3100/yap/stub/geocode?key=stub';
//    $response = $this->get('/upgrade-advisor.php');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "application/json")
//        ->assertJson([
//            'status' => false,
//            'message' => 'Your Google Maps API key came back with the following error. The provided API key is invalid. Please make sure you have the Google Time Zone API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/',
//            'warnings' => '',
//            'version' => $GLOBALS['version'],
//            'build' => 'local'
//        ]);
//});
