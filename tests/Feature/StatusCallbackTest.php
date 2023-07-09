<?php

use App\Repositories\ReportsRepository;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->fakeCallSid = "abcdefghij";
    $this->middleware = new \Tests\MiddlewareTests();
    $this->reportsRepository = $this->middleware->insertSession($this->fakeCallSid);
});

test('status callback test', function () {
    $this->reportsRepository->shouldReceive("insertCallRecord")->withAnyArgs()->once();
    app()->instance(ReportsRepository::class, $this->reportsRepository);
    $response = $this->call(
        'GET',
        '/status.php',
        ["TimestampNow"=>"123",
            "CallSid"=> $this->fakeCallSid,
            "Called"=>"+15005550006",
            "Caller"=>"+17325551212",
        "CallDuration"=>"120"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8");
});
