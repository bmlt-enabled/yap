<?php

use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";

    $this->fakeCallSid = 'CA' . bin2hex(random_bytes(16));

    $fakeHttpClient = new FakeTwilioHttpClient();
    $this->twilioClient = mock('Twilio\Rest\Client', [
        "username" => "fake",
        "password" => "fake",
        "httpClient" => $fakeHttpClient
    ])->makePartial();
    $this->twilioService = mock(TwilioService::class)->makePartial();
});

test('status callback test', function ($method) {
    $response = $this->call(
        $method,
        '/status.php',
        ["TimestampNow"=>"123",
            "CallSid"=> $this->fakeCallSid,
            "Called"=>"+15005550006",
            "Caller"=>"+17325551212",
        "CallDuration"=>"120"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8");

    // Validate call record was created
    $record = \App\Models\Record::where('callsid', $this->fakeCallSid)->first();
    expect($record)->not->toBeNull()
        ->and($record->from_number)->toBe("+17325551212")
        ->and($record->to_number)->toBe("+15005550006")
        ->and($record->type)->toBe(\App\Structures\RecordType::PHONE)
        ->and($record->duration)->toBe(120);
})->with(['GET', 'POST']);

test('status callback test without timestamp', function ($method) {
    $settingsService = new SettingsService();
    app()->instance(SettingsService::class, instance: $settingsService);
    app()->instance(TwilioService::class, $this->twilioService);
    $this->twilioService->shouldReceive("client")->withArgs([])->andReturn($this->twilioClient);
    $this->twilioService->shouldReceive("settings")->andReturn($settingsService);

    $this->twilioService->client()->shouldReceive('getAccountSid')->andReturn("123");
    // mocking TwilioRestClient->calls()->fetch()
    $callInstance = mock('\Twilio\Rest\Api\V2010\Account\CallInstance');
    $callInstance->startTime = new DateTime('2023-01-26T18:00:00');
    $callInstance->endTime = new DateTime('2023-01-26T18:15:00');

    $callContext = mock('\Twilio\Rest\Api\V2010\Account\CallContext');
    $callContext->shouldReceive('fetch')->withNoArgs()->andReturn($callInstance);
    $this->twilioService->client()->shouldReceive('calls')
        ->withArgs([$this->fakeCallSid])->andReturn($callContext)->once();

    $response = $this->call(
        $method,
        '/status.php',
        [ "CallSid"=> $this->fakeCallSid,
            "Called"=>"+15005550006",
            "Caller"=>"+17325551212",
            "CallDuration"=>"120"]
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8");

    // Validate call record was created with Twilio timestamps
    $record = \App\Models\Record::where('callsid', $this->fakeCallSid)->first();
    expect($record)->not->toBeNull()
        ->and($record->from_number)->toBe("+17325551212")
        ->and($record->to_number)->toBe("+15005550006")
        ->and($record->type)->toBe(\App\Structures\RecordType::PHONE)
        ->and($record->duration)->toBe(120)
        ->and($record->start_time)->toBe("2023-01-26 18:00:00")
        ->and($record->end_time)->toBe("2023-01-26 18:15:00");
})->with(['GET', 'POST']);
