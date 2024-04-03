<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('service body extension response', function ($method) {
    $response = $this->call($method, '/service-body-ext-response.php', [
        "Digits" => "1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?override_service_body_id=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voice input result for someone to talk to', function ($method) {
    $response = $this->call($method, '/voice-input-result.php', [
        "SpeechResult" => "Raleigh",
        "SearchType" => "1",
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Digits=Raleigh%2C+&amp;SearchType=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voice input result for someone to talk to with a New York phone number', function ($method) {
    $response = $this->call($method, '/voice-input-result.php', [
        "SpeechResult" => "Raleigh",
        "SearchType" => "1",
        "ToState" => "NY"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Digits=Raleigh%2C+NY&amp;SearchType=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voice input result for someone to talk to with a New York phone number but North Carolina toll phone number bias', function ($method) {
    $_SESSION['override_toll_province_bias'] = "NC";
    $response = $this->call($method, '/voice-input-result.php', [
        "SpeechResult" => "Raleigh",
        "SearchType" => "1",
        "ToState" => "NY"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Digits=Raleigh%2C+NC&amp;SearchType=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voice input result for someone to talk to with a toll free phone number bias', function ($method) {
    $_SESSION['override_toll_free_province_bias'] = "NC";
    $response = $this->call($method, '/voice-input-result.php', [
        "SpeechResult" => "Raleigh",
        "SearchType" => "1"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-search.php?Digits=Raleigh%2C+NC&amp;SearchType=1</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('voice input result for meeting lookup', function ($method) {
    $_REQUEST['SpeechResult'] = "Raleigh";
    $_REQUEST['SearchType'] = "2";
    $response = $this->call($method, '/voice-input-result.php', [
        "SpeechResult" => "Raleigh",
        "SearchType" => "2"
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">address-lookup.php?Digits=Raleigh%2C+&amp;SearchType=2</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
