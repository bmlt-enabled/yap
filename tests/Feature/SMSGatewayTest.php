<?php

use App\Repositories\ReportsRepository;
use App\Services\SettingsService;
use App\Services\TwilioService;
use Tests\FakeTwilioHttpClient;

beforeAll(function () {
    putenv("ENVIRONMENT=test");
});

beforeEach(function () {
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    $this->utility = setupTwilioService();

    $this->from = '+19737771313';
    $this->to = '+12125551212';

    $this->callerIdInfo = [
        'SmsSid' => 'abc123',
        'To' => $this->to,
        'From' => $this->from
    ];

    $repository = Mockery::mock(ReportsRepository::class);
    $repository->shouldReceive("insertCallRecord")->withAnyArgs();
    $repository->shouldReceive("insertCallEventRecord")->withAnyArgs();
    app()->instance(ReportsRepository::class, $repository);
});

test('initial sms gateway default', function () {
    $_REQUEST['stub_google_maps_endpoint'] = true;
    $this->callerIdInfo['Body'] = '27592';
    $response = $this->call('GET', '/sms-gateway.php', $this->callerIdInfo);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">meeting-search.php?SearchType=1&amp;Latitude=35.5648713&amp;Longitude=-78.6682395</Redirect>',
            '</Response>',
    ], false);
});

test('initial sms gateway talk option', function () {
    $_REQUEST['stub_google_maps_endpoint'] = true;
    $this->callerIdInfo['Body'] = 'talk 27592';
    $response = $this->call('GET', '/sms-gateway.php', $this->callerIdInfo);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-sms.php?OriginalCallerId=+19737771313&amp;To=+12125551212&amp;Latitude=35.5648713&amp;Longitude=-78.6682395</Redirect>',
            '</Response>',
        ], false);
});

test('initial sms gateway talk option without location', function () {
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $messageListMock->shouldReceive('create')
        ->with($this->from, Mockery::on(function ($data) {
            return $data['from'] == $this->to
                && $data['body'] == 'please send a message formatting as talk, followed by your location as a city, county or zip code for someone to talk to';
        }));
    $this->utility->client->messages = $messageListMock;

    $_REQUEST['stub_google_maps_endpoint'] = true;
    $this->callerIdInfo['Body'] = 'talk';
    $response = $this->call('GET', '/sms-gateway.php', $this->callerIdInfo);
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
});

test('initial sms gateway talk option using a different keyword', function () {
    $_SESSION['override_sms_helpline_keyword'] = 'dude';
    $_REQUEST['stub_google_maps_endpoint'] = true;
    $this->callerIdInfo['Body'] = 'dude 27592';
    $response = $this->call(
        'GET',
        '/sms-gateway.php',
        $this->callerIdInfo
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '<Redirect method="GET">helpline-sms.php?OriginalCallerId=+19737771313&amp;To=+12125551212&amp;Latitude=35.5648713&amp;Longitude=-78.6682395</Redirect>',
            '</Response>',
        ], false);
});

test('initial sms gateway with a blackholed number', function () {
    $_SESSION['override_sms_blackhole'] = "+19737771313";
    $this->callerIdInfo['Body'] = '27592';
    $response = $this->call(
        'GET',
        '/sms-gateway.php',
        $this->callerIdInfo
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response>',
            '</Response>'
        ], false);
});

test('sms to deliver the jft', function () {
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->utility->client->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($this->from, Mockery::on(function ($data) {
            return $data['from'] == $this->to && !empty($data['body'][0]);
        }));

    $_SESSION['override_jft_option'] = true;
    $this->callerIdInfo['Body'] = 'jFt';
    $response = $this->call(
        'GET',
        '/sms-gateway.php',
        $this->callerIdInfo
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
});

test('sms to deliver the spad', function () {
    // mocking TwilioRestClient->messages->create()
    $messageListMock = mock('\Twilio\Rest\Api\V2010\Account\MessageList');
    $this->utility->client->messages = $messageListMock;
    $messageListMock->shouldReceive('create')
        ->with($this->from, Mockery::on(function ($data) {
            return $data['from'] == $this->to && !empty($data['body'][0]);
        }));

    $_SESSION['override_spad_option'] = true;
    $this->callerIdInfo['Body'] = 'spad';
    $response = $this->call(
        'GET',
        '/sms-gateway.php',
        $this->callerIdInfo
    );
    $response
        ->assertStatus(200)
        ->assertHeader("Content-Type", "text/xml; charset=UTF-8")
        ->assertSeeInOrder([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<Response/>'
        ], false);
});
