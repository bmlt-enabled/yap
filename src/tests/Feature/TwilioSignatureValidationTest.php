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

test('dev bypass skips validation for unsigned requests in non-production', function ($method) {
    config(['twilio.disable_signature_validation' => true]);

    $response = $this->call($method, '/');

    $response->assertStatus(200);
})->with(['GET', 'POST']);
