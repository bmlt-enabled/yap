<?php

use App\Repositories\ReportsRepository;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;
});

test('status callback test', function () {
    $reportsRepository = Mockery::mock(ReportsRepository::class);
    $reportsRepository->shouldReceive("insertCallRecord")->withAnyArgs()->once();
    app()->instance(ReportsRepository::class, $reportsRepository);
    $response = $this->call(
        'GET',
        '/status.php',
        ["TimestampNow"=>"123",
            "CallSid"=> "abcdefghij",
            "Called"=>"+15005550006",
            "Caller"=>"+17325551212",
        "CallDuration"=>"120"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8");
});
