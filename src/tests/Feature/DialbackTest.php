<?php
use App\Models\Record;
use App\Models\Session;
use Carbon\Carbon;
use Tests\TwilioCallTestBuilder;
use App\Services\CallService;
use App\Constants\SmsDialbackOptions;
use App\Repositories\ReportsRepository;
use App\Services\TwilioService;
use App\Repositories\VoicemailRepository;
use App\Services\SettingsService;

beforeEach(function () {
    $this->fakeCallSid = "abcdefghij";
    $this->utility = setupTwilioService();
});

// TODO: add test for piglatin language selection
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

    $response = $this->call($method, '/dialback-dialer.php', ['Digits'=>123]);
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

test('dialback dialer valid pin entry and language selection', function ($method) {
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

    (new TwilioCallTestBuilder([
        "language_selections" => "en-US,es-US",
    ]))
        ->startCall($caller, $called)
        ->expectCallRedirect('lng-selector.php')
        ->followRedirect()
        ->expectTwimlContains('Gather', ['action' => 'index.php'])
        ->expectTwimlContains('Say', ['voice' => 'alice', 'language' => 'en-US'])
        ->expectTwimlContains('Say', ['voice' => 'alice', 'language' => 'es-US'])
        ->pressDigits('1')
        ->expectTwimlContains('Gather', ['language' => 'en-US'])
        ->pressDigits('9')
        ->expectCallRedirect('dialback.php')
        ->followRedirect()
        ->expectTwimlContains('Gather', ['action' => 'dialback-dialer.php'])
        ->pressDigits(sprintf("%s#", $fakePin))
        ->expectTwimlContains('Say', ['voice' => 'alice', 'language' => 'en-US'], 'please wait while we connect your call')
        ->expectTwimlContains('Dial', ['callerId' => $called])
        ->expectTwimlContains('Number', [], $dialbackNumber);
})->with(['GET', 'POST']);

test('dialback dialer invalid pin entry and language selection', function ($method) {
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

    (new TwilioCallTestBuilder([
        "language_selections" => "en-US,es-US",
    ]))
        ->startCall($caller, $called)
        ->expectCallRedirect('lng-selector.php')
        ->followRedirect()
        ->expectTwimlContains('Gather', ['action' => 'index.php'])
        ->expectTwimlContains('Say', ['voice' => 'alice', 'language' => 'en-US'])
        ->expectTwimlContains('Say', ['voice' => 'alice', 'language' => 'es-US'])
        ->pressDigits('1')
        ->expectTwimlContains('Gather', ['language' => 'en-US'])
        ->pressDigits('9')
        ->expectCallRedirect('dialback.php')
        ->followRedirect()
        ->expectTwimlContains('Gather', ['action' => 'dialback-dialer.php'])
        ->pressDigits(sprintf("%s#", "123457"))  // invalid pin
        ->expectTwimlContains('Say', ['voice' => 'alice', 'language' => 'en-US'], 'Invalid pin entry')
        ->expectCallRedirect('index.php');
})->with(['GET', 'POST']);

test('check dialback string generated is correct for call', function ($method) {
    $fakePin = "123456";
    $called = "+12125551212";
    $caller = "+17325551212";
    $dialbackNumber = "+19732129999";

    $this->utility->settings->set('sms_dialback_options', SmsDialbackOptions::VOLUNTEER_NOTIFICATION);

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

    $settingsService = new SettingsService();
    $reportsRepository = new ReportsRepository($settingsService);
    $twilioService = new TwilioService();
    $voicemailRepository = new VoicemailRepository();

    $callService = new CallService($reportsRepository, $twilioService, $voicemailRepository);
    $dialbackString = $callService->getDialbackString($this->fakeCallSid, $dialbackNumber, SmsDialbackOptions::VOLUNTEER_NOTIFICATION);
    $this->assertEquals("Tap to dialback: +19732129999,,,9,,,123456#.  PIN: 123456", $dialbackString);
})->with(['GET', 'POST']);

test('check dialback string with language selection generated is correct for call', function ($method) {
    $fakePin = "123456";
    $called = "+12125551212";
    $caller = "+17325551212";
    $dialbackNumber = "+19732129999";

    $this->utility->settings->set('sms_dialback_options', SmsDialbackOptions::VOLUNTEER_NOTIFICATION);
    $this->utility->settings->set('language_selections', 'en-US,es-US');
    $this->utility->settings->set('language', 'es-US');

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

    $settingsService = new SettingsService();
    $reportsRepository = new ReportsRepository($settingsService);
    $twilioService = new TwilioService();
    $voicemailRepository = new VoicemailRepository();

    $callService = new CallService($reportsRepository, $twilioService, $voicemailRepository);
    $dialbackString = $callService->getDialbackString($this->fakeCallSid, $dialbackNumber, SmsDialbackOptions::VOLUNTEER_NOTIFICATION);
    $this->assertEquals("Tap to dialback: +19732129999,,,2,,,9,,,123456#.  PIN: 123456", $dialbackString);
})->with(['GET', 'POST']);
