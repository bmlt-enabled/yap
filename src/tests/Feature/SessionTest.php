<?php

use Illuminate\Support\Facades\Session;

test('clear the session', function () {
    Session::put("override_blah", "test");
    $this->assertEquals("test", Session::get("override_blah"));
    $response = $this->get('/v1/session/delete');
    $response->assertStatus(200)
        ->assertHeader("Content-Type", "text/plain; charset=UTF-8")
        ->assertSeeText("OK");
    $this->assertNull(Session::get("override_blah"));
});

test('session initialize', function ($method) {
    $response = $this->call($method, "/", ["override_title" => "son"]);

    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">son</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);

    // Extract the `laravel_session` cookie from the response headers
    $ysk = getSessionCookieValue($response);
    $this->assertNotEmpty($ysk, "laravel_session cookie value is empty");

    // Start a new session, but don't abandon the existing one.
    Session::flush();

    $response = $this->call($method, "/");
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">Test Helpline</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);

    $response = $this->call($method, "/", ["ysk" => $ysk]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" numDigits="1" timeout="10" speechTimeout="auto" action="input-method.php" method="GET">',
            '<Pause length="2"/>',
            '<Say voice="alice" language="en-US">son</Say>',
            '<Say voice="alice" language="en-US">press one to find someone to talk to</Say>',
            '<Say voice="alice" language="en-US">press two to search for meetings</Say>',
            '</Gather>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
