<?php

use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
    include __DIR__ . '/../../lang/en-US.php';
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
    $this->from = "+15005550006";
    $this->to = "+15005550007";
    $this->message = "test message";
    $_REQUEST['To'] = $this->to;
    $_REQUEST['From'] = $this->from;

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ]);
});

test('meeting search with an error on meeting lookup', function () {
    $response = $this->get('/meeting-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">fallback.php</Redirect>',
            '</Response>',
    ], false);
});

test('meeting search with valid latitude and longitude', function () {
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(5);
    $this->twilioClient->messages = $messageListMock;


    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $response = $this->call('GET', '/meeting-search.php', [
        'Latitude' => '35.7796',
        'Longitude' => '-78.6382'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
});

test('meeting search with valid latitude and longitude different results count max', function () {
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with(is_string(""), is_array([]))->times(3);
    $this->twilioClient->messages = $messageListMock;


    $_SESSION['override_sms_combine'] = false;
    $_SESSION['override_sms_ask'] = false;
    $_SESSION['override_result_count_max'] = 3;
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $response = $this->call('GET', '/meeting-search.php', [
        'Latitude' => '35.7796',
        'Longitude' => '-78.6382'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
});

test('meeting search with valid latitude and longitude with sms ask', function () {
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $_SESSION['override_sms_ask'] = true;
    $response = $this->call('GET', '/meeting-search.php', [
        'Latitude' => '35.7796',
        'Longitude' => '-78.6382'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Gather numDigits="1" timeout="10" speechTimeout="auto" input="dtmf" action="post-call-action.php',
            '<Say voice="alice" language="en-US">press one if you would like these results to be texted to you.</Say></Gather>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
});

test('meeting search with valid latitude and longitude with sms combine', function () {
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $_SESSION['override_sms_combine'] = true;
    $_SESSION['override_sms_ask'] = false;
    $response = $this->call('GET', '/meeting-search.php', [
        'Latitude' => '35.7796',
        'Longitude' => '-78.6382'
    ]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 1</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 2</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 3</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 4</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">number 5</Say>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/>',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">thank you for calling, goodbye</Say>',
            '</Response>'
        ], false);
});
