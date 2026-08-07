<?php

use Tests\SignedCallScenario;

beforeEach(function () {
    config(['twilio.disable_signature_validation' => false]);
});

test('a signed inbound call reaches the greeting with validation enabled', function () {
    $scenario = new SignedCallScenario();
    $scenario->startCall('+15551234567', '+15559876543');

    expect($scenario->lastTwiml())
        ->toContain('<Gather')
        ->toContain('Test Helpline');
});

test('unsigned inbound call is rejected when signature validation is enabled', function () {
    $callSid = 'CA' . \Illuminate\Support\Str::uuid()->toString();

    $response = test()->call('GET', '/index.php', [
        'CallSid' => $callSid,
        'Called' => '+15559876543',
        'From' => '+15551234567',
        'To' => '+15559876543',
    ]);

    $response->assertStatus(403);
});
