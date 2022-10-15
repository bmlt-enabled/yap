<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('bad google maps api key', function () {
    $response = $this->get('/upgrade-advisor.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            'status' => false,
            'message' => 'Your Google Maps API key came back with the following error. The provided API key is invalid.  Please make sure you have the Google Maps Geocoding API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/',
            'warnings' => '',
            'version' => $GLOBALS['version'],
            'build' => 'local'
        ]);
});

test('bad timezone api key', function () {
    $GLOBALS['override_google_maps_endpoint'] = 'http://localhost:3100/yap/stub/geocode?key=stub';
    $response = $this->get('/upgrade-advisor.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "application/json")
        ->assertJson([
            'status' => false,
            'message' => 'Your Google Maps API key came back with the following error. The provided API key is invalid. Please make sure you have the Google Time Zone API enabled and that the API key is entered properly and has no referer restrictions. You can check your key at the Google API console here: https://console.cloud.google.com/apis/',
            'warnings' => '',
            'version' => $GLOBALS['version'],
            'build' => 'local'
        ]);
});
