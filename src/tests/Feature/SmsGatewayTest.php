<?php

use App\Services\SettingsService;
use Tests\MiddlewareTests;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_SESSION = null;

    $this->utility = setupTwilioService();
    $this->middleware = new MiddlewareTests();

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
})->with(['GET', 'POST']);

test('initial sms gateway with a blackholed number', function ($method) {
    $_SESSION['override_sms_blackhole'] = "+19737771313";
    $this->callerIdInfo['Body'] = '27592';
    $response = $this->call(
        $method,
        '/sms-gateway.php',
        $this->callerIdInfo
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=utf-8")
        ->assertSeeInOrderExact([
            '<?xml version="1.0" encoding="UTF-8"?>',
        ], false);
})->with(['GET', 'POST']);
