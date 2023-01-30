<?php
beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('dialback initial', function () {
    $response = $this->get('/dialback.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" timeout="15" finishOnKey="#" action="dialback-dialer.php" method="GET">',
            '<Say voice="alice" language="en-US">',
            'Please enter the dialback pin, followed by the pound sign.',
            '</Say>',
            '</Gather>',
            '</Response>'
    ], false);
});

test('dialback dialer valid pin entry', function () {
    $fakeCallSid = "abcdefghij";
    $called = "+12125551212";
    $response = $this->call(
        'GET',
        '/status.php',
        ["TimestampNow"=>"123",
            "CallSid"=> $fakeCallSid,
            "Called"=>"+12125551212",
            "Caller"=>"+17325551212",
            "CallDuration"=>"120"]
    );
    $response->assertStatus(200);
    insertSession($fakeCallSid);
    $pin = lookupPinForCallSid($fakeCallSid)[0]['pin'];
    $response = $this->call('GET', '/dialback-dialer.php', ['Digits'=>$pin,'Called'=>$called]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'please wait while we connect your call',
            '</Say>',
            '<Dial callerId="'.$called.'">'.$pin.'</Dial>',
            '</Response>'
        ], false);
});

test('dialback dialer invalid pin entry', function () {
    $_REQUEST['Digits'] = 123;
    $response = $this->get('/dialback-dialer.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'Invalid pin entry',
            '</Say>',
            '<Pause length="2"/>',
            '<Redirect>index.php</Redirect>',
            '</Response>'
        ], false);
});
