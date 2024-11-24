<?php

use App\Services\SettingsService;

beforeEach(function () {
    $this->utility = setupTwilioService();

    $this->settings = new SettingsService();
    app()->instance(SettingsService::class, $this->settings);
    $this->from = '+19737771313';
    $this->to = '+12125551212';
    $this->callerIdInfo = [
        'SmsSid' => 'abc123',
        'To' => $this->to,
        'From' => $this->from
    ];
});

test('initial sms gateway default', function ($method) {
    $this->callerIdInfo['Body'] = '27592';
    $response = $this->call($method, '/sms-gateway.php', $this->callerIdInfo);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=35.5648713&amp;Longitude=-78.6682395</Redirect>',
            '</Response>',
    ], false);

    // Validate call record was created
    $record = \App\Models\Record::where('callsid', $this->callerIdInfo['SmsSid'])->first();
    expect($record)->not->toBeNull()
        ->and($record->from_number)->toBe($this->from)
        ->and($record->to_number)->toBe($this->to)
        ->and($record->type)->toBe(\App\Structures\RecordType::SMS)
        ->and($record->duration)->toBe(0);
})->with(['GET', 'POST']);

test('initial sms gateway talk option without location', function ($method) {
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with($this->from, Mockery::on(function ($data) {
            return $data['from'] == $this->to
                && $data['body'] == 'please send a message formatting as talk, followed by your location as a city, county or zip code for someone to talk to';
        }));
    $this->utility->client->messages = $messageListMock;

    $this->callerIdInfo['Body'] = 'talk';
    $response = $this->call($method, '/sms-gateway.php', $this->callerIdInfo);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);

    // Validate call record was created
    $record = \App\Models\Record::where('callsid', $this->callerIdInfo['SmsSid'])->first();
    expect($record)->not->toBeNull()
        ->and($record->from_number)->toBe($this->from)
        ->and($record->to_number)->toBe($this->to)
        ->and($record->type)->toBe(\App\Structures\RecordType::SMS)
        ->and($record->duration)->toBe(0);
})->with(['GET', 'POST']);

test('initial sms gateway with a blackholed number', function ($method) {
    session()->put('override_sms_blackhole', "+19737771313");
    $this->callerIdInfo['Body'] = '27592';
    $response = $this->call(
        $method,
        '/sms-gateway.php',
        array_merge($this->callerIdInfo, ['SmsSid' => 'SM' . bin2hex(random_bytes(16))])
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
        ], false);

    // Validate no call record was created for blackholed numbers
    $record = \App\Models\Record::where('callsid', $this->callerIdInfo['SmsSid'])->first();
    expect($record)->toBeNull();
    $_SESSION['override_jft_option'] = true;
})->with(['GET', 'POST']);
    $_SESSION['override_spad_option'] = true;
