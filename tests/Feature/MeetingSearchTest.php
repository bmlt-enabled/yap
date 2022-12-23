<?php

use function PHPUnit\Framework\assertMatchesRegularExpression;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
    $_REQUEST['To'] = "+15005550006";
    $_REQUEST['From'] = "+15005550006";
});

test('meeting search with an error on meeting lookup', function () {
    $response = $this->get('/meeting-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>    <Response>',
            '<Redirect method="GET">fallback.php</Redirect>',
            '</Response>',
    ], false);
});

test('meeting search with valid latitude and longitude', function () {
    $_REQUEST['Latitude'] = '35.7796';
    $_REQUEST['Longitude'] = '-78.6382';
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $response = $this->get('/meeting-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?><Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results</Say>',
            '<Say voice="alice" language="en-US">',
            '<Pause length="1"/><Say voice="alice" language="en-US">number 1</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 2</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 3</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 4</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 5</Say><Say voice="alice" language="en-US">',
            'thank you for calling, goodbye        </Say>',
            '</Response>'
        ], false);
});

test('meeting search with valid latitude and longitude different results count max', function () {
    $_REQUEST['Latitude'] = '35.7796';
    $_REQUEST['Longitude'] = '-78.6382';
    $_SESSION['override_result_count_max'] = 3;
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $response = $this->get('/meeting-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertDontSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?><Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 3 results</Say>',
            '<Say voice="alice" language="en-US">',
            '<Pause length="1"/><Say voice="alice" language="en-US">number 1</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 2</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 3</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            'thank you for calling, goodbye        </Say>',
            '</Response>'
        ], false);
});

test('meeting search with valid latitude and longitude with sms ask', function () {
    $_REQUEST['Latitude'] = '35.7796';
    $_REQUEST['Longitude'] = '-78.6382';
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $_SESSION['override_sms_ask'] = true;
    $response = $this->get('/meeting-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee("post-call-action.php")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?><Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results</Say>',
            '<Say voice="alice" language="en-US">',
            '<Pause length="1"/><Say voice="alice" language="en-US">number 1</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 2</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 3</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 4</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 5</Say><Say voice="alice" language="en-US">',
            'thank you for calling, goodbye        </Say>',
            '</Response>'
        ], false);
});

test('meeting search with valid latitude and longitude with sms combine', function () {
    $_REQUEST['Latitude'] = '35.7796';
    $_REQUEST['Longitude'] = '-78.6382';
    $_SESSION['override_timezone_default'] = '{"timeZoneId": "America/New_York"}';
    $_SESSION['override_sms_combine'] = true;
    $_SESSION['override_sms_ask'] = false;
    $response = $this->get('/meeting-search.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSee([
            '<?xml version="1.0" encoding="UTF-8"?><Response>',
            '<Say voice="alice" language="en-US">meeting information found, listing the top 5 results</Say>',
            '<Say voice="alice" language="en-US">',
            '<Pause length="1"/><Say voice="alice" language="en-US">number 1</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 2</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 3</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 4</Say><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">',
            '</Say><Pause length="1"/><Say voice="alice" language="en-US">number 5</Say><Say voice="alice" language="en-US">',
            'thank you for calling, goodbye        </Say>',
            '</Response>'
        ], false);
});
