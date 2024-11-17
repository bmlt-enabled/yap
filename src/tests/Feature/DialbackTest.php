<?php

use App\Models\Record;
use App\Models\Session;
use Carbon\Carbon;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->fakeCallSid = "abcdefghij";
    $this->utility = setupTwilioService();
//    $this->reportsRepository = $this->middleware->insertSession($this->fakeCallSid);
});

test('dialback initial', function ($method) {
    $response = $this->call($method, '/dialback.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Gather language="en-US" input="dtmf" timeout="15" finishOnKey="#" action="dialback-dialer.php" method="GET">',
            '<Say voice="alice" language="en-US">',
            'Please enter the dialback pin, followed by the pound sign.',
            '</Say>',
            '</Gather>',
            '</Response>'
    ], false);
})->with(['GET', 'POST']);

test('dialback dialer valid pin entry', function ($method) {
    $fakePin = "123456";
    $called = "+12125551212";
    $caller = "+17325551212";
    $dialbackNumber = "+19732129999";

    Record::generate(
        $this->fakeCallSid,
        Carbon::now(),
        Carbon::now(),
        $dialbackNumber,
        $called,
        "",
        60,
        1
    );
    Session::generate($this->fakeCallSid, $fakePin);

    $responseDialbackDialer = $this->call($method, '/dialback-dialer.php', ['Digits'=>$fakePin,'Called'=>$called]);
    $responseDialbackDialer
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'please wait while we connect your call',
            '</Say>',
            '<Dial callerId="'.$called.'">',
            '<Number>'. $dialbackNumber. '</Number>',
            '</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('dialback dialer invalid pin entry', function ($method) {
    $fakePin = "123456";
    $called = "+12125551212";
    $dialbackNumber = "+19732129999";

    Record::generate(
        $this->fakeCallSid,
        Carbon::now(),
        Carbon::now(),
        $dialbackNumber,
        $called,
        "",
        60,
        1
    );
    Session::generate($this->fakeCallSid, $fakePin);

    $_REQUEST['Digits'] = 123;
    $response = $this->call($method, '/dialback-dialer.php');
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'Invalid pin entry',
            '</Say>',
            '<Pause length="2"/>',
            '<Redirect>index.php</Redirect>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);
