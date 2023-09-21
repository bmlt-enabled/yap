<?php

use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();

    $repository = Mockery::mock(TwilioService::class);
    $repository->shouldReceive("client")
        ->andReturn($this->twilioClient);
    app()->instance(TwilioService::class, $repository);
});

test('zip input for helpline lookup', function ($method) {
    $response = $this->call($method, '/zip-input.php?SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="5" timeout="10" action="helpline-search.php?SearchType=1" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please enter your five digit zip code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('zip input for 4 digit postal code', function ($method) {
    $settingsService = new SettingsService();
    $settingsService->setWord("override_please_enter_your_digit", "please enter your four digit");
    $settingsService->setWord("override_zip_code", "postal code");
    $settingsService->set("postal_code_length", 4);
    app()->instance(SettingsService::class, $settingsService);
    $response = $this->call($method, '/zip-input.php', ['SearchType'=>'2']);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="4" timeout="10" action="address-lookup.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please enter your four digit postal code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('zip input for address lookup', function ($method) {
    $response = $this->call($method, '/zip-input.php?SearchType=2');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="5" timeout="10" action="address-lookup.php?SearchType=2" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please enter your five digit zip code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('zip input for address lookup with speech gathering', function ($method) {
    $_SESSION["override_speech_gathering"] = true;
    $response = $this->call($method, '/zip-input.php?SearchType=2');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="speech dtmf" numDigits="5" timeout="10" action="address-lookup.php?SearchType=2" method="GET" speechTimeout="auto">',
            '<Say voice="alice" language="en-US">',
            'please enter or say your five digit zip code',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('city or county voice input', function ($method) {
    $_REQUEST["SearchType"] = "1";
    $response = $this->call($method, '/city-or-county-voice-input.php?SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="speech" hints="" timeout="10" speechTimeout="auto" action="voice-input-result.php?SearchType=1&amp;Province=" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please say the name of the city or county',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('city or county voice input with hints', function ($method) {
    $_REQUEST["SearchType"] = "1";
    $_SESSION['override_gather_hints'] = "Raleigh,Lillington,Benson,Dunn";
    $response = $this->call($method, '/city-or-county-voice-input.php?SearchType=1');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="speech" hints="Raleigh,Lillington,Benson,Dunn" timeout="10" speechTimeout="auto" action="voice-input-result.php?SearchType=1&amp;Province=" method="GET">',
            '<Say voice="alice" language="en-US">',
            'please say the name of the city or county',
            '</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
