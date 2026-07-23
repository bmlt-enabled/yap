<?php

use Twilio\Security\RequestValidator;

beforeEach(function () {
    @session_start();
    $_SERVER['REQUEST_URI'] = "/";
    $_REQUEST = null;
    $_SESSION = null;

    // Exercise the real middleware: turn the non-production dev bypass off so
    // signature validation actually runs for the tests in this file.
    config(['twilio.disable_signature_validation' => false]);
});

test('rejects a Twilio route with a missing signature', function ($method) {
    $_SESSION["override_twilio_auth_token"] = "testtoken";

    $response = $this->call($method, '/');

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('rejects a Twilio route with an invalid signature', function ($method) {
    $_SESSION["override_twilio_auth_token"] = "testtoken";

    $response = $this->call($method, '/', [], [], [], ['HTTP_X_TWILIO_SIGNATURE' => 'not-a-valid-signature']);

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('fails closed when no auth token is configured', function ($method) {
    // No override_twilio_auth_token set -> SettingsService returns '' (empty),
    // so the middleware must reject rather than skip validation.
    $response = $this->call($method, '/');

    $response->assertStatus(403);
})->with(['GET', 'POST']);

test('allows a genuinely signed Twilio request', function ($method) {
    $_SESSION["override_twilio_auth_token"] = "testtoken";

    // The middleware validates against $request->fullUrl(); in the test harness
    // (no forwarded headers) that is http://localhost for "/", with no params.
    $signature = (new RequestValidator("testtoken"))->computeSignature('http://localhost', []);

    $response = $this->call($method, '/', [], [], [], ['HTTP_X_TWILIO_SIGNATURE' => $signature]);

    $response->assertStatus(200);
})->with(['GET', 'POST']);

test('allows a genuinely signed Twilio POST that carries body params', function () {
    $_SESSION["override_twilio_auth_token"] = "testtoken";

    // A real Twilio webhook always POSTs body params (CallSid, From, ...). The
    // middleware signs against $request->post() (POST body only) — never
    // $request->all() — so a request bearing these params must still validate.
    // This guards the exact regression where body-param handling breaks.
    $params = [
        'CallSid' => 'CA00000000000000000000000000000000',
        'From' => '+15555550123',
        'To' => '+15555550100',
        'Digits' => '1',
    ];

    $signature = (new RequestValidator("testtoken"))->computeSignature('http://localhost', $params);

    $response = $this->call('POST', '/', $params, [], [], ['HTTP_X_TWILIO_SIGNATURE' => $signature]);

    $response->assertStatus(200);
});

test('dev bypass skips validation for unsigned requests in non-production', function ($method) {
    config(['twilio.disable_signature_validation' => true]);

    $response = $this->call($method, '/');

    $response->assertStatus(200);
})->with(['GET', 'POST']);

// Regression coverage for the disclosed PoC (issue #1566): forged, unsigned
// POSTs to the SMS gateway and the dialback dialer previously reached the
// controller and drove call/SMS logic (persisting attacker-controlled rows).
// With an auth token configured, the middleware must now reject these before
// the controller runs. A 403 here means the controller never executed and
// nothing was persisted.
test('forged unsigned PoC POST to /sms-gateway is rejected', function () {
    $_SESSION["override_twilio_auth_token"] = "testtoken";

    $response = $this->call('POST', '/sms-gateway.php', [
        'SmsSid' => 'SMforgedATTACKER0001',
        'From' => '+15005550001',
        'To' => '+15005550002',
        'Body' => 'FORGED-NO-SIGNATURE-PAYLOAD',
    ]);

    $response->assertStatus(403);
});

test('forged unsigned PoC POST to /dialback-dialer is rejected', function () {
    $_SESSION["override_twilio_auth_token"] = "testtoken";

    $response = $this->call('POST', '/dialback-dialer.php', [
        'Digits' => '0000',
        'Called' => '+15005550002',
    ]);

    $response->assertStatus(403);
});
