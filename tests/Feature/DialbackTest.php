<?php

use App\Repositories\ReportsRepository;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->fakeCallSid = "abcdefghij";
    $this->middleware = new \Tests\MiddlewareTests();
    $this->reportsRepository = $this->middleware->insertSession($this->fakeCallSid);
});

test('dialback initial', function ($method) {
    $response = $this->call($method, '/dialback.php');
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
})->with(['GET', 'POST']);

test('dialback dialer valid pin entry', function ($method) {
    $fakePin = "123456";
    $this->reportsRepository->shouldReceive("insertCallRecord")->withAnyArgs();
    $this->reportsRepository->shouldReceive("isDialbackPinValid")
        ->with("123456")
        ->andReturn(["123456"]);
    app()->instance(ReportsRepository::class, $this->reportsRepository);
    $called = "+12125551212";
    $response = $this->call(
        'GET',
        '/status.php',
        ["TimestampNow"=>"123",
            "CallSid"=> $this->fakeCallSid,
            "Called"=>"+12125551212",
            "Caller"=>"+17325551212",
            "CallDuration"=>"120"]
    );
    $response->assertStatus(200);
    $response = $this->call($method, '/dialback-dialer.php', ['Digits'=>$fakePin,'Called'=>$called]);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Say voice="alice" language="en-US">',
            'please wait while we connect your call',
            '</Say>',
            '<Dial callerId="'.$called.'">'.$fakePin.'</Dial>',
            '</Response>'
        ], false);
})->with(['GET', 'POST']);

test('dialback dialer invalid pin entry', function ($method) {
    $repository = Mockery::mock(ReportsRepository::class);
    $repository->shouldReceive("insertCallRecord")->withAnyArgs();
    $repository->shouldReceive("isDialbackPinValid")
        ->with(null)
        ->andReturn([]);
    app()->instance(ReportsRepository::class, $repository);
    $_REQUEST['Digits'] = 123;
    $response = $this->call($method, '/dialback-dialer.php');
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
})->with(['GET', 'POST']);
