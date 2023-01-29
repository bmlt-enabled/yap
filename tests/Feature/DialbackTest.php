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

//test('dialback dialer valid pin entry', function () {
//    $fakeCallSid = "abcdefghij";
//    $_REQUEST['CallSid'] = $fakeCallSid;
//    $_REQUEST['Called'] = '12125551212';
//    $_REQUEST['Caller'] = '17325551313';
//    $_REQUEST['CallDuration'] = '120';
//    $response = $this->get('/status.php');
//    $response->assertStatus(200);
//    insertSession($fakeCallSid);
//    $pin = lookupPinForCallSid($fakeCallSid)[0]['pin'];
//    $_REQUEST['Digits'] = $pin;
//    $response = $this->get('/dialback-dialer.php');
//    $response
//        ->assertStatus(200)
//        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
//        ->assertSeeInOrder([
/*            '<?xml version="1.0" encoding="UTF-8"?>',*/
//            '<Response>',
//            '<Say voice="alice" language="en-US">',
//            'Please wait while we connect your call',
//            '</Say>',
//            '<Pause length="2"/>',
//            '<Dial callerId=""/>12125551212</Dial>',
//            '</Response>'
//        ], false);
//});

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
